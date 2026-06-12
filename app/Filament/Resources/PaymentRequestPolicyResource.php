<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentRequestPolicyResource\Pages;
use App\Models\PaymentRequestPolicy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;

class PaymentRequestPolicyResource extends Resource
{
    protected static ?string $model = PaymentRequestPolicy::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Política de Ventanas';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?string $modelLabel = 'Política de Pagos';

    protected static ?string $pluralModelLabel = 'Política de Pagos';

    public static function form(Form $form): Form
    {
        $days = [
            'monday' => 'Lunes',
            'tuesday' => 'Martes',
            'wednesday' => 'Miércoles',
            'thursday' => 'Jueves',
            'friday' => 'Viernes',
            'saturday' => 'Sábado',
            'sunday' => 'Domingo',
        ];

        $roles = [
            'super_admin' => 'Super Admin',
            'ceo' => 'CEO',
            'project_manager' => 'Project Manager',
        ];

        return $form->schema([
            Forms\Components\Section::make('Ventana de Captura (Solicitar/Editar pagos)')
                ->description('Define cuándo los usuarios pueden CREAR y EDITAR drafts.')
                ->schema([
                    Forms\Components\Toggle::make('capture_window_is_active')
                        ->label('Ventana activa')
                        ->helperText('Si está apagado: los usuarios pueden capturar cualquier día.')
                        ->default(true),
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Select::make('capture_open_day')->label('Día de apertura')->options($days)->required()->default('monday'),
                        Forms\Components\TimePicker::make('capture_open_time')->label('Hora de apertura')->seconds(false)->required()->default('08:00'),
                        Forms\Components\Select::make('capture_close_day')->label('Día de cierre')->options($days)->required()->default('wednesday'),
                        Forms\Components\TimePicker::make('capture_close_time')->label('Hora de cierre')->seconds(false)->required()->default('18:00'),
                    ]),
                ])->columns(1),

            Forms\Components\Section::make('Ventana de Envío a Autorización')
                ->description('Define cuándo los usuarios pueden ENVIAR sus pagos al CEO. Si se activa, también habilita cancelación automática de drafts no enviados al cierre.')
                ->schema([
                    Forms\Components\Toggle::make('submit_window_is_active')
                        ->label('Ventana activa')
                        ->helperText('Apagado = los usuarios pueden enviar cualquier día (default actual).')
                        ->default(false),
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Select::make('submit_open_day')->label('Día de apertura')->options($days)->required()->default('monday'),
                        Forms\Components\TimePicker::make('submit_open_time')->label('Hora de apertura')->seconds(false)->required()->default('08:00'),
                        Forms\Components\Select::make('submit_close_day')->label('Día de cierre')->options($days)->required()->default('wednesday'),
                        Forms\Components\TimePicker::make('submit_close_time')->label('Hora de cierre')->seconds(false)->required()->default('18:00'),
                    ]),
                ])->columns(1),

            Forms\Components\Section::make('Fecha de Pago')
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Select::make('provision_target_day')->label('Día objetivo del pago')->options($days)->required()->default('tuesday'),
                        Forms\Components\TextInput::make('provision_target_week_offset')->label('Semanas adelante')->helperText('1 = siguiente semana. 2 = dos semanas. Etc.')->numeric()->minValue(1)->maxValue(8)->required()->default(1),
                    ]),
                ])->columns(1),

            Forms\Components\Section::make('Permisos y UX')
                ->schema([
                    Forms\Components\Select::make('override_role_names')->label('Roles con override (saltan ventanas)')->multiple()->options($roles)->required()->default(['super_admin']),
                    Forms\Components\Select::make('editor_role_names')->label('Roles que pueden editar esta política')->multiple()->options($roles)->required()->default(['super_admin', 'project_manager']),
                    Forms\Components\TextInput::make('warning_minutes_before_close')->label('Minutos antes del cierre para mostrar warning en el modal')->numeric()->minValue(1)->maxValue(30)->required()->default(5),
                ])->columns(1),

            Forms\Components\Section::make('Snooze (extensión temporal por emergencia)')
                ->description('Extiende temporalmente una ventana. Vuelve a horario normal al pasar el datetime configurado.')
                ->schema([
                    Forms\Components\DateTimePicker::make('capture_window_snoozed_until')->label('Captura extendida hasta')->seconds(false)->nullable(),
                    Forms\Components\Textarea::make('capture_window_snooze_reason')->label('Motivo (captura)')->nullable()->rows(2),
                    Forms\Components\DateTimePicker::make('submit_window_snoozed_until')->label('Envío extendido hasta')->seconds(false)->nullable(),
                    Forms\Components\Textarea::make('submit_window_snooze_reason')->label('Motivo (envío)')->nullable()->rows(2),
                ])->columns(2)->collapsed(),

            Forms\Components\Hidden::make('cancellation_activated_at'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\EditPaymentRequestPolicy::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'project_manager']) ?? false;
    }

    public static function canEdit($record): bool
    {
        $editors = PaymentRequestPolicy::current()->editor_role_names ?? ['super_admin', 'project_manager'];

        return auth()->user()?->hasAnyRole($editors) ?? false;
    }
}
