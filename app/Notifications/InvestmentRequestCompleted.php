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

class InvestmentRequestCompleted extends Notification implements ShouldQueue
{
    use IncludesRequestDetails;
    use Queueable;

    public function __construct(public InvestmentRequest $investmentRequest) {}

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
            'Solicitud de Inversión #'.$this->investmentRequest->folio_number.' - Finalizada',
            [
                'sectionTitle' => 'Detalles de la Solicitud',
                'greeting' => 'Hola '.$notifiable->name,
                'description' => 'Tu solicitud de inversión ha completado la aprobación.',
                'details' => $this->getMinimalDetails($this->investmentRequest),
                'stageInfo' => $this->getStageInfo($this->investmentRequest),
                'documents' => $this->getDocuments($this->investmentRequest),
                'actionUrl' => url('/admin/investment-requests/'.$this->investmentRequest->uuid.'/edit'),
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
            ->title('Solicitud de Inversión Finalizada')
            ->body('La solicitud #'.$this->investmentRequest->folio_number.' ha completado todas las aprobaciones.')
            ->icon('heroicon-o-check-badge')
            ->success()
            ->actions([
                Action::make('view')
                    ->label('Ver Solicitud')
                    ->url('/admin/investment-requests/'.$this->investmentRequest->uuid.'/edit')
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
