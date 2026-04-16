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

        $isAddendum = (bool) $this->investmentRequest->is_addendum;
        $subject = $isAddendum
            ? 'ADITIVA — Concepto de Inversión #'.$this->investmentRequest->folio_number
            : 'Nuevo Concepto de Inversión #'.$this->investmentRequest->folio_number;

        return $this->buildMailMessage(
            $subject,
            [
                'banner' => $isAddendum ? '⚠ ADITIVA AL PRESUPUESTO' : null,
                'sectionTitle' => 'Detalles de la Solicitud',
                'greeting' => 'Hola '.$notifiable->name,
                'description' => $isAddendum
                    ? 'Se ha creado una aditiva al presupuesto de inversión que requiere tu autorización.'
                    : 'Se ha creado un nuevo concepto de inversión que requiere tu autorización.',
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
        $isAddendum = (bool) $this->investmentRequest->is_addendum;
        $title = $isAddendum ? 'ADITIVA — Concepto de Inversión' : 'Nuevo Concepto de Inversión';

        return FilamentNotification::make()
            ->title($title)
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
