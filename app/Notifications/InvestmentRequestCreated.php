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

class InvestmentRequestCreated extends Notification implements ShouldQueue
{
    use IncludesRequestDetails;
    use Queueable;

    public function __construct(
        public InvestmentRequest $investmentRequest,
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
            'Nuevo Concepto de Inversión #'.$this->investmentRequest->folio_number,
            [
                'sectionTitle' => 'Detalles de la Solicitud',
                'greeting' => 'Hola '.$notifiable->name,
                'description' => 'Se ha creado una nueva concepto de inversión que requiere tu autorización.',
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
            ->title('Nuevo Concepto de Inversión')
            ->body('Solicitud #'.$this->investmentRequest->folio_number.' de '.$this->investmentRequest->user->name.' por $'.number_format($this->investmentRequest->total, 2))
            ->icon('heroicon-o-document-plus')
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
