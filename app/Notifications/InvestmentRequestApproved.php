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
        $mail = (new MailMessage)
            ->subject('Solicitud de Inversión #'.$this->investmentRequest->folio_number.' - Requiere tu Autorización')
            ->greeting('Hola '.$notifiable->name)
            ->salutation('Saludos, '.config('app.name'))
            ->line('La solicitud de inversión fue aprobada por '.$this->approver->name.' y ahora requiere tu autorización.')
            ->line('**Solicitante:** '.$this->investmentRequest->user->name)
            ->line('**Sucursal:** '.($this->investmentRequest->branch->name ?? '-'))
            ->line('**Concepto de Gasto:** '.($this->investmentRequest->expenseConcept->name ?? '-'))
            ->line('**Tipo de Pago:** '.($this->investmentRequest->paymentType->name ?? '-'))
            ->line('**Proveedor:** '.$this->investmentRequest->provider)
            ->line('**Total:** $ '.number_format($this->investmentRequest->total, 2).' '.($this->investmentRequest->currency->prefix ?? 'MXN'));

        $this->appendStageInfo($mail, $this->investmentRequest);
        $this->appendDocumentLinks($mail, $this->investmentRequest);

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
            ->title('Solicitud de Inversión Requiere tu Autorización')
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
