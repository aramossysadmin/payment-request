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
        $actionUrl = $this->approvalToken
            ? url('/approval/'.$this->approvalToken)
            : url('/admin/payment-requests/'.$this->paymentRequest->uuid.'/edit');

        $actionText = $this->approvalToken
            ? 'Revisar y Autorizar / Rechazar'
            : 'Ver Solicitud';

        $details = [
            ['label' => 'Motivo del rechazo', 'value' => $this->comments],
            ['label' => 'Solicitante', 'value' => $this->paymentRequest->user->name ?? '-'],
            ...$this->getMinimalDetails($this->paymentRequest),
        ];

        $footerLines = [];

        if ($this->approvalToken) {
            $footerLines[] = 'Este enlace es válido por 48 horas.';
            $footerLines[] = '[Ver solicitud en el panel de administración]('.url('/admin/payment-requests/'.$this->paymentRequest->uuid.'/edit').')';
        }

        return $this->buildMailMessage(
            'Solicitud de Pago #'.$this->paymentRequest->folio_number.' - Rechazada por Nivel 2',
            [
                'sectionTitle' => 'Detalles de la Solicitud',
                'greeting' => 'Hola '.$notifiable->name,
                'description' => 'El Autorizador Nivel 2 ('.$this->rejector->name.') ha rechazado la solicitud de pago y requiere tu revisión nuevamente.',
                'details' => $details,
                'stageInfo' => $this->getStageInfo($this->paymentRequest),
                'documents' => $this->getDocuments($this->paymentRequest),
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
