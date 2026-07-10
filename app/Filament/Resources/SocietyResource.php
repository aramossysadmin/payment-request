<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SocietyResource\Pages;
use App\Models\Society;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SocietyResource extends Resource
{
    protected static ?string $model = Society::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationGroup = 'Organización';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Sociedad';

    protected static ?string $pluralModelLabel = 'Sociedades';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información general')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                    ]),

                Forms\Components\Section::make('Información Fiscal')
                    ->description('RFC (México) / CIF (España) y Constancia de Situación Fiscal para compartir con proveedores.')
                    ->icon('heroicon-o-document-text')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('rfc')
                            ->label('RFC / CIF')
                            ->prefixIcon('heroicon-o-identification')
                            ->maxLength(20)
                            ->rules(['nullable', 'string', 'alpha_num', 'min:9', 'max:13'])
                            ->helperText('RFC (México, 12-13 chars) o CIF (España, 9 chars). Alfanumérico.'),
                        Forms\Components\FileUpload::make('constancia_situacion_fiscal')
                            ->label('Constancia de Situación Fiscal')
                            ->disk('local')
                            ->directory('society-fiscal-documents')
                            ->visibility('private')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(10240)
                            ->downloadable()
                            ->openable()
                            ->helperText('PDF de la Constancia (SAT México) o Certificado Censal (AEAT España). Máx 10 MB.'),
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
                Tables\Columns\TextColumn::make('rfc')
                    ->label('RFC / CIF')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),
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
            'index' => Pages\ListSocieties::route('/'),
            'create' => Pages\CreateSociety::route('/create'),
            'edit' => Pages\EditSociety::route('/{record}/edit'),
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
