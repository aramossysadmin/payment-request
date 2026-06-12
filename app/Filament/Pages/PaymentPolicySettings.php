<?php

namespace App\Filament\Pages;

use App\Models\PaymentRequestPolicy;
use App\Services\PaymentRequestPolicyService;
use Filament\Actions\Action;
use Filament\Forms\Components;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class PaymentPolicySettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Política de Ventanas';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?string $title = 'Política de Ventanas de Pagos';

    protected static string $view = 'filament.pages.payment-policy-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $policy = PaymentRequestPolicy::current();
        $this->form->fill($policy->toArray());
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'project_manager']) ?? false;
    }

    public function form(Form $form): Form
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

        return $form
            ->statePath('data')
            ->schema([
                Components\Section::make('Ventana de Captura (Solicitar/Editar pagos)')
                    ->description('Define cuándo los usuarios pueden CREAR y EDITAR drafts.')
                    ->schema([
                        Components\Toggle::make('capture_window_is_active')
                            ->label('Ventana activa')
                            ->helperText('Si está apagado: los usuarios pueden capturar cualquier día.'),
                        Components\Grid::make(2)->schema([
                            Components\Select::make('capture_open_day')->label('Día de apertura')->options($days)->required(),
                            Components\TimePicker::make('capture_open_time')->label('Hora de apertura')->seconds(false)->required(),
                            Components\Select::make('capture_close_day')->label('Día de cierre')->options($days)->required(),
                            Components\TimePicker::make('capture_close_time')->label('Hora de cierre')->seconds(false)->required(),
                        ]),
                    ])->columns(1),

                Components\Section::make('Ventana de Envío a Autorización')
                    ->description('Define cuándo los usuarios pueden ENVIAR sus pagos al CEO. Si se activa, también habilita cancelación automática de drafts no enviados al cierre.')
                    ->schema([
                        Components\Toggle::make('submit_window_is_active')
                            ->label('Ventana activa')
                            ->helperText('Apagado = los usuarios pueden enviar cualquier día (default actual).'),
                        Components\Grid::make(2)->schema([
                            Components\Select::make('submit_open_day')->label('Día de apertura')->options($days)->required(),
                            Components\TimePicker::make('submit_open_time')->label('Hora de apertura')->seconds(false)->required(),
                            Components\Select::make('submit_close_day')->label('Día de cierre')->options($days)->required(),
                            Components\TimePicker::make('submit_close_time')->label('Hora de cierre')->seconds(false)->required(),
                        ]),
                    ])->columns(1),

                Components\Section::make('Fecha de Pago')
                    ->schema([
                        Components\Grid::make(2)->schema([
                            Components\Select::make('provision_target_day')->label('Día objetivo del pago')->options($days)->required(),
                            Components\TextInput::make('provision_target_week_offset')->label('Semanas adelante')->helperText('1 = siguiente semana. 2 = dos semanas.')->numeric()->minValue(1)->maxValue(8)->required(),
                        ]),
                    ])->columns(1),

                Components\Section::make('Permisos y UX')
                    ->schema([
                        Components\Select::make('override_role_names')->label('Roles con override (saltan ventanas)')->multiple()->options($roles)->required(),
                        Components\Select::make('editor_role_names')->label('Roles que pueden editar esta política')->multiple()->options($roles)->required(),
                        Components\TextInput::make('warning_minutes_before_close')->label('Minutos antes del cierre para mostrar warning en el modal')->numeric()->minValue(1)->maxValue(30)->required(),
                    ])->columns(1),

                Components\Section::make('Snooze (extensión temporal por emergencia)')
                    ->description('Extiende temporalmente una ventana. Vuelve a horario normal al pasar el datetime configurado.')
                    ->schema([
                        Components\DateTimePicker::make('capture_window_snoozed_until')->label('Captura extendida hasta')->seconds(false)->nullable(),
                        Components\Textarea::make('capture_window_snooze_reason')->label('Motivo (captura)')->nullable()->rows(2),
                        Components\DateTimePicker::make('submit_window_snoozed_until')->label('Envío extendido hasta')->seconds(false)->nullable(),
                        Components\Textarea::make('submit_window_snooze_reason')->label('Motivo (envío)')->nullable()->rows(2),
                    ])->columns(2)->collapsed(),
            ]);
    }

    public function save(): void
    {
        $policy = PaymentRequestPolicy::current();
        $data = $this->form->getState();

        // Si activamos submit_window_is_active por primera vez, marcar timestamp.
        if (! empty($data['submit_window_is_active']) && empty($policy->cancellation_activated_at)) {
            $data['cancellation_activated_at'] = now();
        }

        $editors = $policy->editor_role_names ?? ['super_admin', 'project_manager'];
        abort_unless(auth()->user()->hasAnyRole($editors), 403, 'No tienes permisos para editar la política.');

        $policy->update($data);
        PaymentRequestPolicyService::flushCache();

        Notification::make()
            ->title('Política actualizada')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Guardar')
                ->submit('save'),
        ];
    }
}
