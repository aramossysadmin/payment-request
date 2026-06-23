<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvestmentExpenseConceptResource\Pages;
use App\Models\InvestmentExpenseConcept;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvestmentExpenseConceptResource extends Resource
{
    protected static ?string $model = InvestmentExpenseConcept::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationGroup = 'Catálogos';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'Concepto de Inversión';

    protected static ?string $pluralModelLabel = 'Conceptos de Inversión';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Concepto')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('investment_expense_category_id')
                            ->label('Categoría de Gasto')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('sap_item_code')
                            ->label('Número de artículo SAP')
                            ->helperText('ItemCode del artículo en SAP B1. Identificador único.')
                            ->prefixIcon('heroicon-o-hashtag')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true)
                            ->helperText('Los conceptos inactivos no estarán disponibles para nuevas hojas de inversión.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Concepto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoría')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sap_item_code')
                    ->label('Cód. SAP')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (?string $state): string => $state ? 'success' : 'warning')
                    ->formatStateUsing(fn (?string $state): string => $state ?? 'Sin SAP'),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Estado')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('investment_expense_category_id')
                    ->label('Categoría')
                    ->relationship('category', 'name'),
                Tables\Filters\TernaryFilter::make('sap_item_code')
                    ->label('Mapeado a SAP')
                    ->trueLabel('Con código SAP')
                    ->falseLabel('Sin código SAP')
                    ->placeholder('Todos')
                    ->queries(
                        true: fn ($q) => $q->whereNotNull('sap_item_code'),
                        false: fn ($q) => $q->whereNull('sap_item_code'),
                        blank: fn ($q) => $q,
                    ),
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
            'index' => Pages\ListInvestmentExpenseConcepts::route('/'),
            'create' => Pages\CreateInvestmentExpenseConcept::route('/create'),
            'edit' => Pages\EditInvestmentExpenseConcept::route('/{record}/edit'),
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
