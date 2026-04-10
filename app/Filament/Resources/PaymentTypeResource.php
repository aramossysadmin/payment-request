<?php

namespace App\Filament\Resources;

use App\Enums\DocumentMode;
use App\Enums\PaymentTypeCategory;
use App\Filament\Resources\PaymentTypeResource\Pages;
use App\Models\PaymentType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentTypeResource extends Resource
{
    protected static ?string $model = PaymentType::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Catálogos';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Tipo de Pago';

    protected static ?string $pluralModelLabel = 'Tipos de Pago';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('invoice_documents_mode')
                            ->label('Documentos de Factura (PDF + XML)')
                            ->options(collect(DocumentMode::cases())->mapWithKeys(
                                fn (DocumentMode $mode) => [$mode->value => $mode->label()]
                            ))
                            ->required()
                            ->default(DocumentMode::Disabled->value)
                            ->helperText('Controla si el usuario debe subir 1 archivo PDF y 1 archivo XML.'),
                        Forms\Components\Select::make('additional_documents_mode')
                            ->label('Documentos Adicionales')
                            ->options(collect(DocumentMode::cases())->mapWithKeys(
                                fn (DocumentMode $mode) => [$mode->value => $mode->label()]
                            ))
                            ->required()
                            ->default(DocumentMode::Optional->value)
                            ->helperText('Controla si el usuario puede o debe subir documentos adicionales.'),
                        Forms\Components\Select::make('category')
                            ->label('Categoría')
                            ->options(collect(PaymentTypeCategory::cases())->mapWithKeys(
                                fn (PaymentTypeCategory $category) => [$category->value => $category->label()]
                            ))
                            ->required()
                            ->default(PaymentTypeCategory::Payment->value)
                            ->helperText('Define en qué recurso estará disponible este tipo de pago.'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true)
                            ->helperText('Los tipos de pago inactivos no estarán disponibles para nuevas solicitudes.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_documents_mode')
                    ->label('Factura PDF+XML')
                    ->badge()
                    ->formatStateUsing(fn (DocumentMode $state): string => $state->label())
                    ->color(fn (DocumentMode $state): string => match ($state) {
                        DocumentMode::Disabled => 'gray',
                        DocumentMode::Optional => 'warning',
                        DocumentMode::Required => 'success',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('additional_documents_mode')
                    ->label('Docs Adicionales')
                    ->badge()
                    ->formatStateUsing(fn (DocumentMode $state): string => $state->label())
                    ->color(fn (DocumentMode $state): string => match ($state) {
                        DocumentMode::Disabled => 'gray',
                        DocumentMode::Optional => 'warning',
                        DocumentMode::Required => 'success',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Categoría')
                    ->badge()
                    ->formatStateUsing(fn (PaymentTypeCategory $state): string => $state->label())
                    ->color(fn (PaymentTypeCategory $state): string => match ($state) {
                        PaymentTypeCategory::Payment => 'info',
                        PaymentTypeCategory::Investment => 'purple',
                    })
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Estado')
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
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos')
                    ->placeholder('Todos'),
                Tables\Filters\TrashedFilter::make(),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentTypes::route('/'),
            'create' => Pages\CreatePaymentType::route('/create'),
            'edit' => Pages\EditPaymentType::route('/{record}/edit'),
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
