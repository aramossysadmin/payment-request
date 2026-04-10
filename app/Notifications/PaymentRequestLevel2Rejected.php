<?php

namespace App\Notifications;

use App\Models\PaymentRequest;
use App\Models\User;
use App\Notifications\Concerns\IncludesRequestDetails;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentRequestLevel2Rejected extends Notification implements ShouldQueue
{
    use IncludesRequestDetails;
    use Queueable;

    public function __construct(
        public PaymentRequest $paymentRequest,
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
            ->subject('Solicitud de Pago #'.$this->paymentRequest->folio_number.' - Rechazada por Nivel 2')
            ->greeting('Hola '.$notifiable->name)
            ->salutation('Saludos, '.config('app.name'))
            ->line('El Autorizador Nivel 2 ('.$this->rejector->name.') ha rechazado la solicitud de pago y requiere tu revisión nuevamente.')
            ->line('**Motivo del rechazo:** '.$this->comments)
            ->line('**Solicitante:** '.$this->paymentRequest->user->name)
            ->line('**Proveedor:** '.$this->paymentRequest->provider)
            ->line('**Total:** $ '.number_format($this->paymentRequest->total, 2).' '.($this->paymentRequest->currency->prefix ?? 'MXN'));

        $this->appendStageInfo($mail, $this->paymentRequest);
        $this->appendDocumentLinks($mail, $this->paymentRequest);

        if ($this->approvalToken) {
            $mail->action('Revisar y Autorizar / Rechazar', url('/approval/'.$this->approvalToken))
                ->line('Este enlace es válido por 48 horas.')
                ->line('[Ver solicitud en el panel de administración]('.url('/admin/payment-requests/'.$this->paymentRequest->uuid.'/edit').')');
        } else {
            $mail->action('Ver Solicitud', url('/admin/payment-requests/'.$this->paymentRequest->uuid.'/edit'));
        }

        return $mail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(User $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Solicitud Rechazada por Nivel 2')
            ->body($this->rejector->name.' rechazó la solicitud #'.$this->paymentRequest->folio_number.'. Requiere tu revisión: '.$this->comments)
            ->icon('heroicon-o-arrow-uturn-left')
            ->warning()
            ->actions([
                Action::make('view')
                    ->label('Ver Solicitud')
                    ->url('/admin/payment-requests/'.$this->paymentRequest->uuid.'/edit')
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
