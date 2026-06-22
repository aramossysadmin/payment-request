<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Department;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Accesos';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Usuario';

    protected static ?string $pluralModelLabel = 'Usuarios';

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
                            ->autocomplete('off'),
                        Forms\Components\TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->autocomplete('off'),
                        Forms\Components\TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->minLength(8)
                            ->confirmed()
                            ->autocomplete('new-password'),
                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Confirmar contraseña')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(false)
                            ->autocomplete('new-password'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Asignación')
                    ->schema([
                        Forms\Components\Select::make('department_id')
                            ->label('Departamento Principal')
                            ->helperText('El departamento al que pertenece principalmente el usuario. Opcional solo para roles privilegiados (super_admin, ceo, project_manager).')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state, $old): void {
                                // Si admin cambia el principal en vivo, marcar automáticamente el viejo
                                // como adicional (decisión #11). El admin puede desmarcarlo si quiere
                                // quitarlo (sujeto a validación de pendientes activos en beforeSave).
                                if ($old && $state && (int) $old !== (int) $state) {
                                    $current = collect($get('additional_departments') ?? [])
                                        ->map(fn ($id) => (int) $id);
                                    if (! $current->contains((int) $old)) {
                                        $set('additional_departments', $current->push((int) $old)->all());
                                    }
                                }
                            })
                            ->required(fn (Forms\Get $get): bool => ! collect($get('roles') ?? [])->intersect(
                                Role::whereIn('name', ['super_admin', 'ceo', 'project_manager'])->pluck('id')->all()
                            )->isNotEmpty()),
                        Forms\Components\Select::make('position_id')
                            ->label('Posición')
                            ->relationship('position', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\CheckboxList::make('additional_departments')
                            ->label('Departamentos Adicionales')
                            ->helperText('Departamentos secundarios donde el usuario también puede operar. El principal se incluye automáticamente y no aparece aquí. Si cambias el principal, el anterior se marca automáticamente como adicional (puedes desmarcarlo si ya no aplica).')
                            ->options(function (Forms\Get $get) {
                                $principalId = $get('department_id');

                                return Department::query()
                                    ->when($principalId, fn ($q) => $q->where('id', '!=', $principalId))
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all();
                            })
                            ->columns(3)
                            ->bulkToggleable()
                            ->afterStateHydrated(function (Forms\Components\CheckboxList $component, ?User $record) {
                                if (! $record) {
                                    $component->state([]);

                                    return;
                                }
                                $allIds = $record->departments()->pluck('departments.id')->all();
                                $additionalIds = array_values(array_diff($allIds, array_filter([$record->department_id])));
                                $component->state($additionalIds);
                            })
                            ->dehydrated(false)
                            ->live()
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Roles y estado')
                    ->schema([
                        Forms\Components\CheckboxList::make('roles')
                            ->relationship(
                                'roles',
                                'name',
                                fn (Builder $query) => auth()->user()?->hasRole('super_admin')
                                    ? $query
                                    : $query->where('name', '!=', 'super_admin'),
                            )
                            ->label('Roles')
                            ->columns(2)
                            ->saveRelationshipsUsing(function (Forms\Components\CheckboxList $component, User $record, array $state): void {
                                $user = auth()->user();

                                if ($user && $record->id === $user->id && $record->hasRole('super_admin') && ! in_array($record->roles()->where('name', 'super_admin')->value('id'), array_map('intval', $state))) {
                                    Notification::make()
                                        ->title('No puedes quitarte el rol de Super Admin a ti mismo.')
                                        ->danger()
                                        ->send();

                                    return;
                                }

                                $record->roles()->withoutGlobalScopes()->sync(array_map('intval', $state));
                            }),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),
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
                Tables\Columns\TextColumn::make('email')
                    ->label('Correo electrónico')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Departamento')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('position.name')
                    ->label('Posición')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Activo')
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
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->label('Rol')
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
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
