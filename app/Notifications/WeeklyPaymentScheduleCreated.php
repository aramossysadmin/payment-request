<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\WeeklyPaymentSchedule;
use App\Notifications\Concerns\IncludesRequestDetails;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklyPaymentScheduleCreated extends Notification implements ShouldQueue
{
    use IncludesRequestDetails;
    use Queueable;

    public function __construct(
        public WeeklyPaymentSchedule $schedule,
        public ?string $approvalToken = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $schedule = $this->schedule->load(['items.investmentPaymentRequest.currency', 'creator']);

        $includedItems = $schedule->items->where('included', true);
        $excludedItems = $schedule->items->where('included', false);

        $totalAmount = $includedItems->sum(fn ($item) => (float) ($item->investmentPaymentRequest?->total ?? 0));

        $actionUrl = $this->approvalToken
            ? url('/approval/'.$this->approvalToken)
            : url('/weekly-payment-schedule');

        $actionText = $this->approvalToken
            ? 'Autorizar / Rechazar Programación'
            : 'Ver Programación';

        $footerLines = [];
        if ($this->approvalToken) {
            $footerLines[] = 'Este enlace es válido por 48 horas.';
        }

        $details = [
            ['label' => 'Creado por', 'value' => $schedule->creator?->name ?? '-'],
            ['label' => 'Semana', 'value' => (string) $schedule->week_number],
            ['label' => 'Año', 'value' => (string) $schedule->year],
            ['label' => 'Pagos incluidos', 'value' => $includedItems->count().' de '.$schedule->items->count()],
            ['label' => 'Pagos excluidos', 'value' => (string) $excludedItems->count()],
            ['label' => 'Monto total a procesar', 'value' => '$ '.number_format($totalAmount, 2)],
        ];

        return $this->buildMailMessage(
            'Programación de Pagos Semanal - Semana '.$schedule->week_number.'/'.$schedule->year,
            [
                'sectionTitle' => 'Detalles de la Programación',
                'greeting' => 'Hola '.$notifiable->name,
                'description' => 'Se ha creado una programación de pagos semanal que requiere tu autorización para proceder con el envío a bancos.',
                'details' => $details,
                'stageInfo' => [
                    'department' => 'Inversiones',
                    'stage' => null,
                ],
                'documents' => [],
                'actionUrl' => $actionUrl,
                'actionText' => $actionText,
                'footerLines' => $footerLines,
                'salutation' => 'Saludos, '.config('app.name'),
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(User $notifiable): array
    {
        $includedCount = $this->schedule->items->where('included', true)->count();
        $totalAmount = $this->schedule->items->where('included', true)
            ->sum(fn ($item) => (float) ($item->investmentPaymentRequest?->total ?? 0));

        return FilamentNotification::make()
            ->title('Programación de Pagos Semanal')
            ->body('Semana '.$this->schedule->week_number.'/'.$this->schedule->year.' · '.$includedCount.' pagos · $'.number_format($totalAmount, 2))
            ->icon('heroicon-o-calendar')
            ->warning()
            ->actions([
                Action::make('view')
                    ->label('Ver Programación')
                    ->url('/weekly-payment-schedule')
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
