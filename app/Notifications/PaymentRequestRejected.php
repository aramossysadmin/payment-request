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
        $details = [
            ['label' => 'Motivo', 'value' => $this->comments],
            ...$this->getMinimalDetails($this->paymentRequest),
        ];

        return $this->buildMailMessage(
            'Solicitud de Pago #'.$this->paymentRequest->folio_number.' - Rechazada',
            [
                'sectionTitle' => 'Detalles de la Solicitud',
                'greeting' => 'Hola '.$notifiable->name,
                'description' => $this->rejector->name.' ha rechazado la solicitud de pago.',
                'details' => $details,
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
