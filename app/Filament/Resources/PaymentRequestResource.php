<?php

namespace App\Filament\Resources;

use App\Enums\PaymentRequestStatus;
use App\Enums\PaymentType;
use App\Filament\Resources\PaymentRequestResource\Pages;
use App\Models\PaymentRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentRequestResource extends Resource
{
    protected static ?string $model = PaymentRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static ?string $navigationGroup = 'Solicitudes';

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

                Forms\Components\Section::make('Montos')
                    ->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->numeric()
                            ->required()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('iva')
                            ->label('IVA')
                            ->numeric()
                            ->required()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('retention')
                            ->label('Retención')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->prefix('$'),
                        Forms\Components\TextInput::make('total')
                            ->label('Total')
                            ->numeric()
                            ->required()
                            ->prefix('$'),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Estado')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options(collect(PaymentRequestStatus::cases())->mapWithKeys(
                                fn (PaymentRequestStatus $status) => [$status->value => $status->label()]
                            ))
                            ->default(PaymentRequestStatus::Pending->value)
                            ->required(),
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
                    ->color(fn (PaymentRequestStatus $state): string => match ($state) {
                        PaymentRequestStatus::Pending => 'warning',
                        PaymentRequestStatus::Approved => 'success',
                        PaymentRequestStatus::Rejected => 'danger',
                        PaymentRequestStatus::Paid => 'info',
                    })
                    ->formatStateUsing(fn (PaymentRequestStatus $state): string => $state->label()),
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
                    ->options(collect(PaymentRequestStatus::cases())->mapWithKeys(
                        fn (PaymentRequestStatus $status) => [$status->value => $status->label()]
                    )),
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
            //
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
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
