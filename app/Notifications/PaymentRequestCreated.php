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

class PaymentRequestCreated extends Notification implements ShouldQueue
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
            ->subject('Nueva Solicitud de Pago #'.$this->paymentRequest->folio_number)
            ->greeting('Hola '.$notifiable->name)
            ->line('Se ha creado una nueva solicitud de pago que requiere tu autorización.')
            ->line('**Proveedor:** '.$this->paymentRequest->provider)
            ->line('**Folio:** '.$this->paymentRequest->invoice_folio)
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
            ->title('Nueva Solicitud de Pago')
            ->body('Solicitud #'.$this->paymentRequest->folio_number.' de '.$this->paymentRequest->user->name.' por $'.number_format($this->paymentRequest->total, 2))
            ->icon('heroicon-o-document-plus')
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
