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

class PaymentRequestCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public PaymentRequest $paymentRequest) {}

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
            ->subject('Solicitud de Pago #'.$this->paymentRequest->id.' - Finalizada')
            ->greeting('Hola '.$notifiable->name)
            ->line('Tu solicitud de pago ha completado todas las etapas de aprobación.')
            ->line('**Proveedor:** '.$this->paymentRequest->provider)
            ->line('**Total:** $'.number_format($this->paymentRequest->total, 2))
            ->action('Ver Solicitud', url('/admin/payment-requests/'.$this->paymentRequest->id.'/edit'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(User $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Solicitud Finalizada')
            ->body('La solicitud #'.$this->paymentRequest->id.' ha completado todas las aprobaciones.')
            ->icon('heroicon-o-check-badge')
            ->success()
            ->actions([
                Action::make('view')
                    ->label('Ver Solicitud')
                    ->url('/admin/payment-requests/'.$this->paymentRequest->id.'/edit')
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
