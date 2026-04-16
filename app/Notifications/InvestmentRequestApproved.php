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

class InvestmentRequestApproved extends Notification implements ShouldQueue
{
    use IncludesRequestDetails;
    use Queueable;

    public function __construct(
        public InvestmentRequest $investmentRequest,
        public User $approver,
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
            : url('/admin/investment-requests/'.$this->investmentRequest->uuid.'/edit');

        $actionText = $this->approvalToken
            ? 'Autorizar / Rechazar Solicitud'
            : 'Ver Solicitud';

        $footerLines = [];

        if ($this->approvalToken) {
            $footerLines[] = 'Este enlace es válido por 48 horas.';
            $footerLines[] = '[Ver solicitud en el panel de administración]('.url('/admin/investment-requests/'.$this->investmentRequest->uuid.'/edit').')';
        }

        return $this->buildMailMessage(
            'Concepto de Inversión #'.$this->investmentRequest->folio_number.' - Requiere tu Autorización',
            [
                'sectionTitle' => 'Detalles de la Solicitud',
                'greeting' => 'Hola '.$notifiable->name,
                'description' => 'La concepto de inversión fue aprobada por '.$this->approver->name.' y ahora requiere tu autorización.',
                'details' => $this->getFullDetails($this->investmentRequest),
                'stageInfo' => $this->getStageInfo($this->investmentRequest),
                'documents' => $this->getDocuments($this->investmentRequest),
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
            ->title('Concepto de Inversión Requiere tu Autorización')
            ->body('Solicitud #'.$this->investmentRequest->folio_number.' aprobada por '.$this->approver->name.'. Requiere tu autorización.')
            ->icon('heroicon-o-check-circle')
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
