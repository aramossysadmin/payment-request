<?php

namespace App\Notifications;

use App\Models\InvestmentRequest;
use App\Models\User;
use App\Notifications\Concerns\IncludesRequestDetails;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvestmentRequestLevel2Rejected extends Notification implements ShouldQueue
{
    use IncludesRequestDetails;
    use Queueable;

    public function __construct(
        public InvestmentRequest $investmentRequest,
        public User $rejector,
        public string $comments,
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
        $mail = (new MailMessage)
            ->subject('Solicitud de Inversión #'.$this->investmentRequest->folio_number.' - Rechazada por Nivel 2')
            ->greeting('Hola '.$notifiable->name)
            ->salutation('Saludos, '.config('app.name'))
            ->line('El Autorizador Nivel 2 ('.$this->rejector->name.') ha rechazado la solicitud de inversión y requiere tu revisión nuevamente.')
            ->line('**Motivo del rechazo:** '.$this->comments)
            ->line('**Solicitante:** '.$this->investmentRequest->user->name)
            ->line('**Proveedor:** '.$this->investmentRequest->provider)
            ->line('**Total:** $ '.number_format($this->investmentRequest->total, 2).' '.($this->investmentRequest->currency->prefix ?? 'MXN'));

        $this->appendStageInfo($mail, $this->investmentRequest);
        $this->appendDocumentLinks($mail, $this->investmentRequest);

        if ($this->approvalToken) {
            $mail->action('Revisar y Autorizar / Rechazar', url('/approval/'.$this->approvalToken))
                ->line('Este enlace es válido por 48 horas.')
                ->line('[Ver solicitud en el panel de administración]('.url('/admin/investment-requests/'.$this->investmentRequest->uuid.'/edit').')');
        } else {
            $mail->action('Ver Solicitud', url('/admin/investment-requests/'.$this->investmentRequest->uuid.'/edit'));
        }

        return $mail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(User $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Solicitud de Inversión Rechazada por Nivel 2')
            ->body($this->rejector->name.' rechazó la solicitud #'.$this->investmentRequest->folio_number.'. Requiere tu revisión: '.$this->comments)
            ->icon('heroicon-o-arrow-uturn-left')
            ->warning()
            ->actions([
                Action::make('view')
                    ->label('Ver Solicitud')
                    ->url('/admin/investment-requests/'.$this->investmentRequest->uuid.'/edit')
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
