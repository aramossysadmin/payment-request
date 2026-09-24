<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StrategyResource\Pages;
use App\Models\Strategy;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StrategyResource extends Resource
{
    protected static ?string $model = Strategy::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Catálogos';

    protected static ?int $navigationSort = 7;

    protected static ?string $modelLabel = 'Estrategia';

    protected static ?string $pluralModelLabel = 'Estrategias';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->description(fn (string $operation): ?string => $operation === 'edit'
                        ? 'Los datos de una estrategia son inmutables una vez creada. Solo puedes activarla o desactivarla. Para corregir, desactívala y crea una nueva.'
                        : null)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->placeholder('Se genera a partir de la tasa')
                            ->helperText('Se asigna automáticamente para evitar errores de captura.')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('rate')
                            ->label('Tasa')
                            ->numeric()
                            ->required()
                            ->minValue(0.001)
                            ->maxValue(100)
                            ->step(0.001)
                            ->suffix('%')
                            ->helperText('Porcentaje de recargo aplicado sobre el Monto Estrategia. Ej. 4.5')
                            ->live(debounce: 400)
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                $isValidRate = is_numeric($state) && (float) $state > 0;
                                $set('factor', $isValidRate ? Strategy::trimZeros(Strategy::factorFromRate($state)) : null);
                                $set('name', $isValidRate ? Strategy::nameFromRate($state) : null);
                            })
                            ->formatStateUsing(fn (?string $state): ?string => filled($state) ? Strategy::trimZeros($state) : $state)
                            ->disabledOn('edit')
                            ->dehydrated(fn (string $operation): bool => $operation === 'create')
                            ->rules(fn (string $operation): array => $operation === 'create' ? [self::duplicateRateRule()] : []),
                        Forms\Components\TextInput::make('factor')
                            ->label('Factor de operación')
                            ->helperText('Se calcula automáticamente (tasa ÷ 100). Total = Monto Estrategia × (1 + factor).')
                            ->placeholder('Se llena al capturar la tasa')
                            ->formatStateUsing(fn (?string $state): ?string => filled($state) ? Strategy::trimZeros($state) : $state)
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activa')
                            ->default(true)
                            ->helperText('Las estrategias inactivas no aparecen al solicitar un pago.'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('rate')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rate')
                    ->label('Tasa')
                    ->formatStateUsing(fn (string $state): string => Strategy::trimZeros($state).'%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('factor')
                    ->label('Factor')
                    ->formatStateUsing(fn (string $state): string => Strategy::trimZeros($state))
                    ->fontFamily('mono'),
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
                    ->trueLabel('Activas')
                    ->falseLabel('Inactivas')
                    ->placeholder('Todas'),
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
            'index' => Pages\ListStrategies::route('/'),
            'create' => Pages\CreateStrategy::route('/create'),
            'edit' => Pages\EditStrategy::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    /**
     * Rechaza una tasa repetida contra TODAS las estrategias (activas, inactivas y en papelera)
     * y, si la existente está inactiva o eliminada, ofrece un acceso directo para reactivarla.
     */
    private static function duplicateRateRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_numeric($value)) {
                return;
            }

            $existing = Strategy::withTrashed()->where('rate', round((float) $value, 3))->first();

            if ($existing === null) {
                return;
            }

            if ($existing->trashed()) {
                $fail("Ya existe la {$existing->name} en la papelera. Restáurala en lugar de crear una nueva.");
                self::notifyExisting($existing, 'Estrategia en papelera', 'Restaurar estrategia');

                return;
            }

            if (! $existing->is_active) {
                $fail("Ya existe la {$existing->name} pero está inactiva. Actívala en lugar de crear una nueva.");
                self::notifyExisting($existing, 'Estrategia inactiva', 'Activar estrategia');

                return;
            }

            $fail("Ya existe la {$existing->name} y está activa.");
        };
    }

    private static function notifyExisting(Strategy $existing, string $title, string $actionLabel): void
    {
        Notification::make()
            ->warning()
            ->title($title)
            ->body("La estrategia {$existing->name} ya existe. No se puede crear otra igual.")
            ->actions([
                NotificationAction::make('open')
                    ->label($actionLabel)
                    ->button()
                    ->url(self::getUrl('edit', ['record' => $existing])),
            ])
            ->persistent()
            ->send();
    }
}
