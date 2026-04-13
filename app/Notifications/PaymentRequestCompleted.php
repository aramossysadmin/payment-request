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

class PaymentRequestCompleted extends Notification implements ShouldQueue
{
    use IncludesRequestDetails;
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
        return $this->buildMailMessage(
            'Solicitud de Pago #'.$this->paymentRequest->folio_number.' - Finalizada',
            [
                'sectionTitle' => 'Detalles de la Solicitud',
                'greeting' => 'Hola '.$notifiable->name,
                'description' => 'Tu solicitud de pago ha completado todas las etapas de aprobación.',
                'details' => $this->getMinimalDetails($this->paymentRequest),
                'stageInfo' => $this->getStageInfo($this->paymentRequest),
                'documents' => $this->getDocuments($this->paymentRequest),
                'actionUrl' => url('/admin/payment-requests/'.$this->paymentRequest->uuid.'/edit'),
                'actionText' => 'Ver Solicitud',
                'footerLines' => [],
                'salutation' => 'Saludos, '.config('app.name'),
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(User $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Solicitud Finalizada')
            ->body('La solicitud #'.$this->paymentRequest->folio_number.' ha completado todas las aprobaciones.')
            ->icon('heroicon-o-check-badge')
            ->success()
            ->actions([
                Action::make('view')
                    ->label('Ver Solicitud')
                    ->url('/admin/payment-requests/'.$this->paymentRequest->uuid.'/edit')
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
