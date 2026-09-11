<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Currency;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Catálogos';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Proyecto';

    protected static ?string $pluralModelLabel = 'Proyectos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del Proyecto')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('branch_id')
                            ->label('Sucursal')
                            ->relationship('branch', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('currency_id')
                            ->label('Moneda por Defecto')
                            ->relationship('currency', 'name')
                            ->default(fn (): ?int => Currency::where('prefix', 'MXN')->value('id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Moneda predeterminada del proyecto.'),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Fecha de Inicio')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->helperText('Cuándo arranca formalmente el proyecto.'),
                                Forms\Components\DatePicker::make('opening_date')
                                    ->label('Fecha de Apertura')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->helperText('Cuándo se abre operativamente al equipo.'),
                            ]),
                        Forms\Components\TextInput::make('authorized_budget')
                            ->label('Presupuesto Autorizado')
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0)
                            ->helperText('Monto autorizado para el proyecto. Se utiliza como referencia en el dashboard de Hojas de Inversión.'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Abierto para captura')
                            ->default(true)
                            ->disabled(fn (): bool => ! auth()->user()?->hasAnyRole(['super_admin', 'project_manager']))
                            ->helperText('Un proyecto cerrado bloquea capturas y cambios para todos los usuarios. Solo PM y super admin pueden verlo en modo lectura. El cierre y la reapertura quedan registrados en el historial de actividad. Solo super admin y PM pueden cambiar este estado.'),
                        Forms\Components\Toggle::make('requires_pm_approval')
                            ->label('Requiere autorización del PM')
                            ->default(false)
                            ->helperText('Cuando está activo, las nuevas Solicitudes de Inversión de este proyecto requieren aprobación del Project Manager antes de tomar efecto.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre del Proyecto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Sucursal')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency.prefix')
                    ->label('Moneda')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Estado')
                    ->sortable()
                    ->disabled(fn (): bool => ! auth()->user()?->hasAnyRole(['super_admin', 'project_manager']))
                    ->tooltip(fn (Project $record): string => $record->is_active
                        ? 'Abierto para captura'
                        : 'Cerrado — solo lectura para PM y super admin'),
                Tables\Columns\TextColumn::make('closed_at')
                    ->label('Cerrado desde')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('requires_pm_approval')
                    ->label('Requiere PM')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->trueLabel('Abiertos')
                    ->falseLabel('Cerrados')
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
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
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
