<?php

namespace App\Filament\Resources;

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
                        Forms\Components\Toggle::make('requires_invoice_documents')
                            ->label('Requiere documentos de factura (PDF + XML)')
                            ->helperText('Si se activa, el usuario deberá subir exactamente 1 archivo PDF y 1 archivo XML al crear una solicitud con este tipo de pago.')
                            ->default(false),
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
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('requires_invoice_documents')
                    ->label('Requiere Factura')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
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
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos')
                    ->placeholder('Todos'),
            ])
            ->actions([
                Tables\Actions\Action::make('toggleActive')
                    ->label(fn (PaymentType $record): string => $record->is_active ? 'Desactivar' : 'Activar')
                    ->icon(fn (PaymentType $record): string => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (PaymentType $record): string => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(fn (PaymentType $record) => $record->update(['is_active' => ! $record->is_active])),
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
