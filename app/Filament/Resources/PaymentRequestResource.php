<?php

namespace App\Filament\Resources;

use App\Enums\PaymentType;
use App\Filament\Resources\PaymentRequestResource\Pages;
use App\Filament\Resources\PaymentRequestResource\RelationManagers\ApprovalsRelationManager;
use App\Models\PaymentRequest;
use App\Services\ApprovalService;
use App\States\PaymentRequest\PaymentRequestState;
use App\States\PaymentRequest\PendingAdministration;
use App\States\PaymentRequest\PendingDepartment;
use App\States\PaymentRequest\PendingTreasury;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentRequestResource extends Resource
{
    protected static ?string $model = PaymentRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static ?string $navigationGroup = 'Operaciones';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Solicitud de Pago';

    protected static ?string $pluralModelLabel = 'Solicitudes de Pago';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información General')
                    ->schema([
                        Forms\Components\TextInput::make('provider')
                            ->label('Proveedor')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('invoice_folio')
                            ->label('Folio Factura')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('currency_id')
                            ->label('Moneda')
                            ->relationship('currency', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('branch_id')
                            ->label('Sucursal')
                            ->relationship('branch', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('expense_concept_id')
                            ->label('Concepto de Gasto')
                            ->relationship('expenseConcept', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(2),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3)
                            ->columnSpan(2),
                        Forms\Components\Select::make('payment_type')
                            ->label('Tipo de Pago')
                            ->placeholder('Seleccionar Tipo de Pago')
                            ->options(collect(PaymentType::cases())->mapWithKeys(
                                fn (PaymentType $type) => [$type->value => $type->label()]
                            ))
                            ->required()
                            ->live(),
                        Forms\Components\FileUpload::make('advance_documents')
                            ->label('Documentos de Anticipo')
                            ->helperText('Máximo 1 archivo XML y 1 archivo PDF.')
                            ->acceptedFileTypes(['application/xml', 'text/xml', 'application/pdf'])
                            ->multiple()
                            ->maxFiles(2)
                            ->directory('advance-documents')
                            ->visibility('private')
                            ->visible(fn (Forms\Get $get): bool => $get('payment_type') === PaymentType::Advance->value)
                            ->validationMessages([
                                'advance_documents_no_duplicates' => 'No se pueden subir dos archivos con la misma extensión.',
                            ])
                            ->rules([
                                fn (): \Closure => function (string $attribute, mixed $value, \Closure $fail): void {
                                    if (! is_array($value) || count($value) <= 1) {
                                        return;
                                    }

                                    $extensions = [];
                                    foreach ($value as $file) {
                                        $extension = strtolower(
                                            $file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile
                                                ? $file->getClientOriginalExtension()
                                                : pathinfo($file, PATHINFO_EXTENSION)
                                        );

                                        if (in_array($extension, $extensions)) {
                                            $fail('No se pueden subir dos archivos con la misma extensión.');

                                            return;
                                        }

                                        $extensions[] = $extension;
                                    }
                                },
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Folios SAP')
                    ->schema([
                        Forms\Components\TextInput::make('number_purchase_invoices')
                            ->label('Folio SAP Factura Proveedores')
                            ->numeric()
                            ->minValue(1)
                            ->disabled(fn (): bool => ! self::canEditSapField('administration')),
                        Forms\Components\TextInput::make('number_vendor_payments')
                            ->label('Folio SAP Pago Efectuado')
                            ->numeric()
                            ->minValue(1)
                            ->disabled(fn (): bool => ! self::canEditSapField('treasury')),
                    ])
                    ->columns(2)
                    ->visibleOn('edit'),

                Forms\Components\Section::make('Montos')
                    ->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->numeric()
                            ->required()
                            ->prefix('$')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set): void {
                                $subtotal = (float) $get('subtotal');
                                $iva = round($subtotal * 0.16, 2);
                                $set('iva', number_format($iva, 2, '.', ''));
                                $set('total', number_format($subtotal + $iva, 2, '.', ''));
                            }),
                        Forms\Components\TextInput::make('iva')
                            ->label('IVA (16%)')
                            ->numeric()
                            ->required()
                            ->prefix('$')
                            ->readOnly(),
                        Forms\Components\Checkbox::make('retention')
                            ->label('Aplica retención')
                            ->default(false),
                        Forms\Components\TextInput::make('total')
                            ->label('Total')
                            ->numeric()
                            ->required()
                            ->prefix('$')
                            ->readOnly(),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Estado')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options(
                                collect(PaymentRequestState::all())
                                    ->mapWithKeys(fn (string $stateClass) => [
                                        $stateClass::$name => (new $stateClass(new PaymentRequest))->label(),
                                    ])
                            )
                            ->default(PendingDepartment::$name)
                            ->required()
                            ->disabled(fn (): bool => ! auth()->user()?->hasRole('super_admin')),
                        Forms\Components\Select::make('user_id')
                            ->label('Solicitante')
                            ->relationship('user', 'name')
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('folio_number')
                    ->label('Folio')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('provider')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_folio')
                    ->label('Folio Factura')
                    ->searchable(),
                Tables\Columns\TextColumn::make('currency.name')
                    ->label('Moneda')
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Sucursal')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('expenseConcept.name')
                    ->label('Concepto de Gasto')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_type')
                    ->label('Tipo de Pago')
                    ->badge()
                    ->color(fn (PaymentType $state): string => match ($state) {
                        PaymentType::Full => 'success',
                        PaymentType::Advance => 'warning',
                    })
                    ->formatStateUsing(fn (PaymentType $state): string => $state->label())
                    ->sortable(),
                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (PaymentRequestState $state): string => $state->color())
                    ->formatStateUsing(fn (PaymentRequestState $state): string => $state->label()),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Solicitante')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(
                        collect(PaymentRequestState::all())
                            ->mapWithKeys(fn (string $stateClass) => [
                                $stateClass::$name => (new $stateClass(new PaymentRequest))->label(),
                            ])
                    ),
                Tables\Filters\SelectFilter::make('currency')
                    ->relationship('currency', 'name')
                    ->label('Moneda')
                    ->preload(),
                Tables\Filters\SelectFilter::make('branch')
                    ->relationship('branch', 'name')
                    ->label('Sucursal')
                    ->preload(),
                Tables\Filters\SelectFilter::make('expenseConcept')
                    ->relationship('expenseConcept', 'name')
                    ->label('Concepto de Gasto')
                    ->preload(),
                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->label('Solicitante')
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Aprobar Solicitud')
                    ->modalDescription('¿Estás seguro de que deseas aprobar esta solicitud de pago?')
                    ->form(fn (PaymentRequest $record): array => self::getSapFieldsForApproval($record))
                    ->visible(fn (PaymentRequest $record): bool => self::canApproveOrReject($record))
                    ->action(function (PaymentRequest $record, array $data): void {
                        app(ApprovalService::class)->approve($record, auth()->user(), $data);

                        Notification::make()
                            ->title('Solicitud aprobada')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Rechazar Solicitud')
                    ->form([
                        Forms\Components\Textarea::make('comments')
                            ->label('Motivo del rechazo')
                            ->required()
                            ->rows(3),
                    ])
                    ->visible(fn (PaymentRequest $record): bool => self::canApproveOrReject($record))
                    ->action(function (PaymentRequest $record, array $data): void {
                        app(ApprovalService::class)->reject($record, auth()->user(), $data['comments']);

                        Notification::make()
                            ->title('Solicitud rechazada')
                            ->danger()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ApprovalsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentRequests::route('/'),
            'create' => Pages\CreatePaymentRequest::route('/create'),
            'edit' => Pages\EditPaymentRequest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        $user = auth()->user();

        if (! $user) {
            return $query;
        }

        if ($user->hasRole('super_admin')) {
            return $query;
        }

        $authorizedDepartmentIds = $user->authorizedDepartments()->pluck('departments.id');

        if ($authorizedDepartmentIds->isNotEmpty()) {
            return $query->where(function (Builder $q) use ($user, $authorizedDepartmentIds): void {
                $q->whereIn('department_id', $authorizedDepartmentIds)
                    ->orWhereHas('approvals', function (Builder $q) use ($user): void {
                        $q->where('user_id', $user->id);
                    });
            });
        }

        return $query->where('user_id', $user->id);
    }

    /**
     * @return array<int, Forms\Components\Component>
     */
    private static function getSapFieldsForApproval(PaymentRequest $record): array
    {
        if ($record->status->equals(PendingAdministration::class)) {
            return [
                Forms\Components\TextInput::make('number_purchase_invoices')
                    ->label('Folio SAP Factura Proveedores')
                    ->numeric()
                    ->minValue(1),
            ];
        }

        if ($record->status->equals(PendingTreasury::class)) {
            return [
                Forms\Components\TextInput::make('number_vendor_payments')
                    ->label('Folio SAP Pago Efectuado')
                    ->numeric()
                    ->minValue(1),
            ];
        }

        return [];
    }

    private static function canEditSapField(string $stage): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        return \App\Models\PaymentRequestApproval::query()
            ->where('user_id', $user->id)
            ->where('stage', $stage)
            ->where('status', 'approved')
            ->exists();
    }

    private static function canApproveOrReject(PaymentRequest $record): bool
    {
        if (! $record->status->equals(PendingDepartment::class)
            && ! $record->status->equals(PendingAdministration::class)
            && ! $record->status->equals(PendingTreasury::class)
        ) {
            return false;
        }

        $user = auth()->user();

        if (! $user) {
            return false;
        }

        $stageMap = [
            PendingDepartment::class => 'department',
            PendingAdministration::class => 'administration',
            PendingTreasury::class => 'treasury',
        ];

        $currentStage = null;
        foreach ($stageMap as $stateClass => $stage) {
            if ($record->status->equals($stateClass)) {
                $currentStage = $stage;
                break;
            }
        }

        if (! $currentStage) {
            return false;
        }

        return $record->approvals()
            ->where('user_id', $user->id)
            ->where('stage', $currentStage)
            ->where('status', 'pending')
            ->exists();
    }
}
