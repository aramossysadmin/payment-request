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

class InvestmentRequestLevel2Rejected extends Notification implements ShouldQueue
{
    use IncludesRequestDetails;
    use Queueable;

    public function __construct(
        public InvestmentRequest $investmentRequest,
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
            : url('/admin/investment-requests/'.$this->investmentRequest->uuid.'/edit');

        $actionText = $this->approvalToken
            ? 'Revisar y Autorizar / Rechazar'
            : 'Ver Solicitud';

        $details = [
            ['label' => 'Motivo del rechazo', 'value' => $this->comments],
            ['label' => 'Solicitante', 'value' => $this->investmentRequest->user->name ?? '-'],
            ...$this->getMinimalDetails($this->investmentRequest),
        ];

        $footerLines = [];

        if ($this->approvalToken) {
            $footerLines[] = 'Este enlace es válido por 48 horas.';
            $footerLines[] = '[Ver solicitud en el panel de administración]('.url('/admin/investment-requests/'.$this->investmentRequest->uuid.'/edit').')';
        }

        return $this->buildMailMessage(
            'Solicitud de Inversión #'.$this->investmentRequest->folio_number.' - Rechazada por Nivel 2',
            [
                'sectionTitle' => 'Detalles de la Solicitud',
                'greeting' => 'Hola '.$notifiable->name,
                'description' => 'El Autorizador Nivel 2 ('.$this->rejector->name.') ha rechazado la solicitud de inversión y requiere tu revisión nuevamente.',
                'details' => $details,
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
            ->title('Solicitud de Inversión Rechazada por Nivel 2')
            ->body($this->rejector->name.' rechazó la solicitud #'.$this->investmentRequest->folio_number.'. Requiere tu revisión: '.$this->comments)
            ->icon('heroicon-o-arrow-uturn-left')
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
