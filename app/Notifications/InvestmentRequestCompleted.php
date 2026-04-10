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
        $mail = (new MailMessage)
            ->subject('Solicitud de Inversión #'.$this->investmentRequest->folio_number.' - Finalizada')
            ->greeting('Hola '.$notifiable->name)
            ->salutation('Saludos, '.config('app.name'))
            ->line('Tu solicitud de inversión ha completado todas las etapas de aprobación.')
            ->line('**Proveedor:** '.$this->investmentRequest->provider)
            ->line('**Total:** $ '.number_format($this->investmentRequest->total, 2).' '.($this->investmentRequest->currency->prefix ?? 'MXN'));

        $this->appendStageInfo($mail, $this->investmentRequest);
        $this->appendDocumentLinks($mail, $this->investmentRequest);

        $mail->action('Ver Solicitud', url('/admin/investment-requests/'.$this->investmentRequest->uuid.'/edit'));

        return $mail;
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
