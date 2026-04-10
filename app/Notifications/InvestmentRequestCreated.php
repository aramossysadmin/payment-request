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

class InvestmentRequestCreated extends Notification implements ShouldQueue
{
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
        $mail = (new MailMessage)
            ->subject('Nueva Solicitud de Inversión #'.$this->investmentRequest->folio_number)
            ->greeting('Hola '.$notifiable->name)
            ->salutation('Saludos, '.config('app.name'))
            ->line('Se ha creado una nueva solicitud de inversión que requiere tu autorización.')
            ->line('**Solicitante:** '.$this->investmentRequest->user->name)
            ->line('**Sucursal:** '.($this->investmentRequest->branch->name ?? '-'))
            ->line('**Concepto de Gasto:** '.($this->investmentRequest->expenseConcept->name ?? '-'))
            ->line('**Tipo de Pago:** '.($this->investmentRequest->paymentType->name ?? '-'))
            ->line('**Proveedor:** '.$this->investmentRequest->provider)
            ->line('**Folio:** '.$this->investmentRequest->invoice_folio)
            ->line('**Total:** $ '.number_format($this->investmentRequest->total, 2).' '.($this->investmentRequest->currency->prefix ?? 'MXN'));

        if ($this->approvalToken) {
            $mail->action('Autorizar / Rechazar Solicitud', url('/approval/'.$this->approvalToken))
                ->line('Este enlace es válido por 48 horas.')
                ->line('[Ver solicitud en el panel de administración]('.url('/admin/investment-requests/'.$this->investmentRequest->uuid.'/edit').')');
        } else {
            $mail->action('Ver Solicitud', url('/admin/investment-requests/'.$this->investmentRequest->uuid.'/edit'));
        }

        return $mail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(User $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Nueva Solicitud de Inversión')
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
