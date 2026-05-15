<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvestmentExpenseCategoryResource\Pages;
use App\Models\InvestmentExpenseCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rules\Unique;

class InvestmentExpenseCategoryResource extends Resource
{
    protected static ?string $model = InvestmentExpenseCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationGroup = 'Catálogos';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Categoría de Gasto de Inversión';

    protected static ?string $pluralModelLabel = 'Categorías de Gastos de Inversión';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Categorías')
                            ->required()
                            ->maxLength(255)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule, callable $get) => $rule->where(
                                    'super_category',
                                    $get('super_category') !== null && $get('super_category') !== ''
                                        ? mb_strtoupper(trim($get('super_category')))
                                        : null,
                                ),
                            ),
                        Forms\Components\Select::make('super_category')
                            ->label('Super Categoría')
                            ->options(fn () => InvestmentExpenseCategory::query()
                                ->whereNotNull('super_category')
                                ->distinct()
                                ->pluck('super_category', 'super_category')
                                ->toArray())
                            ->searchable()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('value')
                                    ->label('Nueva Super Categoría')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionUsing(function (array $data): string {
                                return mb_strtoupper(trim($data['value']));
                            })
                            ->nullable(),
                        Forms\Components\Select::make('departments')
                            ->label('Departamentos')
                            ->relationship('departments', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->minItems(1),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true)
                            ->helperText('Las categorías inactivas no estarán disponibles para nuevos conceptos de gasto.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Categorías')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('super_category')
                    ->label('Super Categoría')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('departments.name')
                    ->label('Departamentos')
                    ->badge()
                    ->separator(',')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Estado')
                    ->sortable(),
                Tables\Columns\TextColumn::make('investment_expense_concepts_count')
                    ->label('Conceptos')
                    ->counts('investmentExpenseConcepts')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('departments')
                    ->label('Departamentos')
                    ->relationship('departments', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
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
            'index' => Pages\ListInvestmentExpenseCategories::route('/'),
            'create' => Pages\CreateInvestmentExpenseCategory::route('/create'),
            'edit' => Pages\EditInvestmentExpenseCategory::route('/{record}/edit'),
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
