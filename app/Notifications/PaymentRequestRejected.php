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

class PaymentRequestRejected extends Notification implements ShouldQueue
{
    use IncludesRequestDetails;
    use Queueable;

    public function __construct(
        public PaymentRequest $paymentRequest,
        public User $rejector,
        public string $comments,
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
            ->subject('Solicitud de Pago #'.$this->paymentRequest->folio_number.' - Rechazada')
            ->greeting('Hola '.$notifiable->name)
            ->salutation('Saludos, '.config('app.name'))
            ->line($this->rejector->name.' ha rechazado la solicitud de pago.')
            ->line('**Motivo:** '.$this->comments)
            ->line('**Proveedor:** '.$this->paymentRequest->provider)
            ->line('**Total:** $ '.number_format($this->paymentRequest->total, 2).' '.($this->paymentRequest->currency->prefix ?? 'MXN'));

        $this->appendStageInfo($mail, $this->paymentRequest);
        $this->appendDocumentLinks($mail, $this->paymentRequest);

        $mail->action('Ver Solicitud', url('/admin/payment-requests/'.$this->paymentRequest->uuid.'/edit'));

        return $mail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(User $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Solicitud Rechazada')
            ->body($this->rejector->name.' rechazó la solicitud #'.$this->paymentRequest->folio_number.': '.$this->comments)
            ->icon('heroicon-o-x-circle')
            ->danger()
            ->actions([
                Action::make('view')
                    ->label('Ver Solicitud')
                    ->url('/admin/payment-requests/'.$this->paymentRequest->uuid.'/edit')
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
