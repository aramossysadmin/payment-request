<?php

namespace App\Notifications;

use App\Models\PaymentRequest;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentRequestApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PaymentRequest $paymentRequest,
        public User $approver,
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
        return (new MailMessage)
            ->subject('Solicitud de Pago #'.$this->paymentRequest->folio_number.' - Requiere tu Autorización')
            ->greeting('Hola '.$notifiable->name)
            ->line('La solicitud de pago fue aprobada por '.$this->approver->name.' y ahora requiere tu autorización.')
            ->line('**Proveedor:** '.$this->paymentRequest->provider)
            ->line('**Total:** $'.number_format($this->paymentRequest->total, 2))
            ->line('**Solicitante:** '.$this->paymentRequest->user->name)
            ->action('Ver Solicitud', url('/admin/payment-requests/'.$this->paymentRequest->uuid.'/edit'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(User $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Solicitud Requiere tu Autorización')
            ->body('Solicitud #'.$this->paymentRequest->folio_number.' aprobada por '.$this->approver->name.'. Requiere tu autorización.')
            ->icon('heroicon-o-check-circle')
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
