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

class PaymentRequestCreated extends Notification implements ShouldQueue
{
    use IncludesRequestDetails;
    use Queueable;

    public function __construct(
        public PaymentRequest $paymentRequest,
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
            ? 'Autorizar / Rechazar Solicitud'
            : 'Ver Solicitud';

        $footerLines = [];

        if ($this->approvalToken) {
            $footerLines[] = 'Este enlace es válido por 48 horas.';
            $footerLines[] = '[Ver solicitud en el panel de administración]('.url('/admin/payment-requests/'.$this->paymentRequest->uuid.'/edit').')';
        }

        return $this->buildMailMessage(
            'Nueva Solicitud de Pago #'.$this->paymentRequest->folio_number,
            [
                'sectionTitle' => 'Detalles de la Solicitud',
                'greeting' => 'Hola '.$notifiable->name,
                'description' => 'Se ha creado una nueva solicitud de pago que requiere tu autorización.',
                'details' => $this->getFullDetails($this->paymentRequest),
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
