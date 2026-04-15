<?php

namespace App\Filament\Resources;

use App\Enums\IvaRate;
use App\Filament\Resources\InvestmentRequestResource\Pages;
use App\Filament\Resources\InvestmentRequestResource\RelationManagers\ApprovalsRelationManager;
use App\Models\InvestmentRequest;
use App\Models\InvestmentRequestApproval;
use App\Services\InvestmentApprovalService;
use App\States\InvestmentRequest\InvestmentRequestState;
use App\States\InvestmentRequest\PendingAdministration;
use App\States\InvestmentRequest\PendingDepartment;
use App\States\InvestmentRequest\PendingTreasury;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvestmentRequestResource extends Resource
{
    protected static ?string $model = InvestmentRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Operaciones';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Solicitud de Inversión';

    protected static ?string $pluralModelLabel = 'Solicitudes de Inversión';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información General')
                    ->schema([
                        Forms\Components\TextInput::make('provider')
                            ->label('Razón Social')
                            ->required()
                            ->maxLength(255)
                            ->datalist(fn (): array => InvestmentRequest::query()
                                ->select('provider')
                                ->whereNotNull('provider')
                                ->where('provider', '!=', '')
                                ->distinct()
                                ->orderBy('provider')
                                ->pluck('provider')
                                ->toArray()
                            )
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state): void {
                                if ($state) {
                                    $rfc = InvestmentRequest::where('provider', $state)
                                        ->whereNotNull('rfc')
                                        ->where('rfc', '!=', '')
                                        ->value('rfc');
                                    if ($rfc) {
                                        $set('rfc', $rfc);
                                    }
                                }
                            }),
                        Forms\Components\TextInput::make('rfc')
                            ->label('RFC')
                            ->alphaNum()
                            ->minLength(12)
                            ->maxLength(13)
                            ->datalist(fn (): array => InvestmentRequest::query()
                                ->select('rfc')
                                ->whereNotNull('rfc')
                                ->where('rfc', '!=', '')
                                ->distinct()
                                ->orderBy('rfc')
                                ->pluck('rfc')
                                ->toArray()
                            )
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state): void {
                                if ($state) {
                                    $provider = InvestmentRequest::where('rfc', $state)
                                        ->whereNotNull('provider')
                                        ->value('provider');
                                    if ($provider) {
                                        $set('provider', $provider);
                                    }
                                }
                            }),
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
                        Forms\Components\Select::make('payment_type_id')
                            ->label('Tipo de Pago')
                            ->placeholder('Seleccionar Tipo de Pago')
                            ->relationship('paymentType', 'name', fn (Builder $query) => $query->forInvestments()->active())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                        Forms\Components\FileUpload::make('advance_documents')
                            ->label('Documentos Solicitudes de Inversión')
                            ->multiple()
                            ->disk('local')
                            ->directory('investment-advance-documents')
                            ->visibility('private')
                            ->acceptedFileTypes(['application/pdf', 'text/xml', 'application/xml', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
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
                                $rate = (float) ($get('iva_rate') ?? '0.16');
                                $iva = round($subtotal * $rate, 2);
                                $set('iva', number_format($iva, 2, '.', ''));
                                $set('total', number_format($subtotal + $iva, 2, '.', ''));
                            }),
                        Forms\Components\Select::make('iva_rate')
                            ->label('Tasa de IVA')
                            ->options(collect(IvaRate::cases())->mapWithKeys(
                                fn (IvaRate $rate) => [$rate->value => $rate->label()]
                            ))
                            ->required()
                            ->default('0.16')
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set): void {
                                $subtotal = (float) $get('subtotal');
                                $rate = (float) ($get('iva_rate') ?? '0.16');
                                $iva = round($subtotal * $rate, 2);
                                $set('iva', number_format($iva, 2, '.', ''));
                                $set('total', number_format($subtotal + $iva, 2, '.', ''));
                            }),
                        Forms\Components\TextInput::make('iva')
                            ->label('IVA')
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
                                collect(InvestmentRequestState::all())
                                    ->mapWithKeys(fn (string $stateClass) => [
                                        $stateClass::$name => (new $stateClass(new InvestmentRequest))->label(),
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
            ->defaultSort('folio_number', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('folio_number')
                    ->label('Folio')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('provider')
                    ->label('Razón Social')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rfc')
                    ->label('RFC')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('invoice_folio')
                    ->label('Folio Factura')
                    ->searchable()
                    ->sortable(),
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
                Tables\Columns\TextColumn::make('paymentType.name')
                    ->label('Tipo de Pago')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->numeric(decimalPlaces: 2, thousandsSeparator: ',', decimalSeparator: '.')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->numeric(decimalPlaces: 2, thousandsSeparator: ',', decimalSeparator: '.')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (InvestmentRequestState $state): string => $state->color())
                    ->formatStateUsing(fn (InvestmentRequestState $state): string => $state->label())
                    ->sortable(),
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
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(
                        collect(InvestmentRequestState::all())
                            ->mapWithKeys(fn (string $stateClass) => [
                                $stateClass::$name => (new $stateClass(new InvestmentRequest))->label(),
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
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Aprobar Solicitud')
                    ->modalDescription('¿Estás seguro de que deseas aprobar esta solicitud de inversión?')
                    ->form(fn (InvestmentRequest $record): array => self::getSapFieldsForApproval($record))
                    ->visible(fn (InvestmentRequest $record): bool => self::canApproveOrReject($record))
                    ->action(function (InvestmentRequest $record, array $data): void {
                        app(InvestmentApprovalService::class)->approve($record, auth()->user(), $data);

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
                    ->visible(fn (InvestmentRequest $record): bool => self::canApproveOrReject($record))
                    ->action(function (InvestmentRequest $record, array $data): void {
                        app(InvestmentApprovalService::class)->reject($record, auth()->user(), $data['comments']);

                        Notification::make()
                            ->title('Solicitud rechazada')
                            ->danger()
                            ->send();
                    }),
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->visible(fn (InvestmentRequest $record): bool => $record->status::$name === 'completed')
                    ->url(fn (InvestmentRequest $record): string => route('investment-requests.pdf', $record))
                    ->openUrlInNewTab(),
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
            'index' => Pages\ListInvestmentRequests::route('/'),
            'create' => Pages\CreateInvestmentRequest::route('/create'),
            'edit' => Pages\EditInvestmentRequest::route('/{record}/edit'),
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

        $authorizedDepartmentIds = $user->authorizedDepartments()->pluck('id');

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
    private static function getSapFieldsForApproval(InvestmentRequest $record): array
    {
        if ($record->status->equals(PendingAdministration::class)) {
            return [
                Forms\Components\TextInput::make('number_purchase_invoices')
                    ->label('Folio SAP Factura Proveedores')
                    ->numeric()
                    ->minValue(1)
                    ->default($record->number_purchase_invoices),
            ];
        }

        if ($record->status->equals(PendingTreasury::class)) {
            return [
                Forms\Components\TextInput::make('number_vendor_payments')
                    ->label('Folio SAP Pago Efectuado')
                    ->numeric()
                    ->minValue(1)
                    ->default($record->number_vendor_payments),
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

        return InvestmentRequestApproval::query()
            ->where('user_id', $user->id)
            ->where('stage', $stage)
            ->where('status', 'approved')
            ->exists();
    }

    private static function canApproveOrReject(InvestmentRequest $record): bool
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
