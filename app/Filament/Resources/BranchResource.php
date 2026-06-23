<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchResource\Pages;
use App\Models\Branch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Organización';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Sucursal';

    protected static ?string $pluralModelLabel = 'Sucursales';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información general')
                    ->description('Nombre de la sucursal para identificación interna.')
                    ->icon('heroicon-o-building-office')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->prefixIcon('heroicon-o-building-office')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('society_id')
                            ->label('Sociedad')
                            ->relationship('society', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activa')
                            ->helperText('Toggle de control reservado. El filtro en selectores del portal y la exclusión del envío a SAP se aplicarán cuando se active la integración SAP. Por ahora marca la sucursal como inactiva en el catálogo administrativo.')
                            ->default(true)
                            ->inline(false),
                    ]),

                Forms\Components\Section::make('Conexión SAP')
                    ->description('Credenciales y configuración para la conexión con SAP Business One.')
                    ->icon('heroicon-o-circle-stack')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('sap_database')
                            ->label('Base de datos SAP')
                            ->helperText('Nombre de la base de datos en SAP.')
                            ->prefixIcon('heroicon-o-circle-stack')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('sap_branch_id')
                            ->label('Sucursal SAP')
                            ->helperText('Identificador de la sucursal en SAP (BPLId).')
                            ->maxLength(50),
                        Forms\Components\TextInput::make('sap_user')
                            ->label('Usuario SAP')
                            ->helperText('Usuario de conexión al Service Layer.')
                            ->prefixIcon('heroicon-o-user')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('sap_password')
                            ->label('Contraseña SAP')
                            ->password()
                            ->revealable()
                            ->helperText('Déjalo vacío para mantener la contraseña actual.')
                            ->dehydrated(fn ($state) => filled($state))
                            ->maxLength(255),
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
                Tables\Columns\TextColumn::make('society.name')
                    ->label('Sociedad')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sap_configured')
                    ->label('SAP')
                    ->state(fn (Branch $record): string => $record->isSapConfigured() ? 'Configurado' : 'Sin SAP')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Configurado' ? 'success' : 'warning'),
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
                Tables\Filters\SelectFilter::make('society')
                    ->relationship('society', 'name')
                    ->label('Sociedad')
                    ->preload(),
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranch::route('/create'),
            'edit' => Pages\EditBranch::route('/{record}/edit'),
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
