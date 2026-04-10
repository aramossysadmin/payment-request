<?php

namespace App\Notifications;

use App\Models\InvestmentRequest;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvestmentRequestRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public InvestmentRequest $investmentRequest,
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
        return (new MailMessage)
            ->subject('Solicitud de Inversión #'.$this->investmentRequest->folio_number.' - Rechazada')
            ->greeting('Hola '.$notifiable->name)
            ->salutation('Saludos, '.config('app.name'))
            ->line($this->rejector->name.' ha rechazado la solicitud de inversión.')
            ->line('**Motivo:** '.$this->comments)
            ->line('**Proveedor:** '.$this->investmentRequest->provider)
            ->line('**Total:** $ '.number_format($this->investmentRequest->total, 2).' '.($this->investmentRequest->currency->prefix ?? 'MXN'))
            ->action('Ver Solicitud', url('/admin/investment-requests/'.$this->investmentRequest->uuid.'/edit'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(User $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Solicitud de Inversión Rechazada')
            ->body($this->rejector->name.' rechazó la solicitud #'.$this->investmentRequest->folio_number.': '.$this->comments)
            ->icon('heroicon-o-x-circle')
            ->danger()
            ->actions([
                Action::make('view')
                    ->label('Ver Solicitud')
                    ->url('/admin/investment-requests/'.$this->investmentRequest->uuid.'/edit')
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
