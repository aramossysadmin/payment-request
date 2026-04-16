<?php

namespace App\Notifications;

use App\Models\InvestmentPaymentRequest;
use App\Models\User;
use App\Notifications\Concerns\IncludesRequestDetails;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvestmentPaymentRequestCreated extends Notification implements ShouldQueue
{
    use IncludesRequestDetails;
    use Queueable;

    public function __construct(
        public InvestmentPaymentRequest $paymentRequest,
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
            : url('/investment-sheets/consolidated');

        $actionText = $this->approvalToken
            ? 'Autorizar / Rechazar Solicitud'
            : 'Ver Solicitud';

        $footerLines = [];

        if ($this->approvalToken) {
            $footerLines[] = 'Este enlace es válido por 48 horas.';
        }

        $investmentRequest = $this->paymentRequest->investmentRequest;

        $details = [
            ['label' => 'Solicitante', 'value' => $this->paymentRequest->user->name ?? '-'],
            ['label' => 'Concepto de Inversión', 'value' => '#'.str_pad((string) $investmentRequest->folio_number, 5, '0', STR_PAD_LEFT).' - '.$investmentRequest->provider],
            ['label' => 'Proveedor', 'value' => $this->paymentRequest->provider],
            ['label' => 'Sucursal', 'value' => $this->paymentRequest->branch->name ?? '-'],
            ['label' => 'Total', 'value' => '$ '.number_format($this->paymentRequest->total, 2).' '.($this->paymentRequest->currency->prefix ?? 'MXN')],
        ];

        if ($this->paymentRequest->description) {
            $details[] = ['label' => 'Descripción', 'value' => $this->paymentRequest->description];
        }

        return $this->buildMailMessage(
            'Nueva Solicitud de Pago de Inversión #'.$this->paymentRequest->folio_number,
            [
                'sectionTitle' => 'Detalles de la Solicitud de Pago',
                'greeting' => 'Hola '.$notifiable->name,
                'description' => 'Se ha creado una nueva solicitud de pago de inversión que requiere tu autorización.',
                'details' => $details,
                'stageInfo' => [
                    'department' => $this->paymentRequest->department->name ?? '-',
                    'stage' => null,
                ],
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
            ->title('Nueva Solicitud de Pago de Inversión')
            ->body('Solicitud #'.$this->paymentRequest->folio_number.' de '.$this->paymentRequest->user->name.' por $'.number_format($this->paymentRequest->total, 2))
            ->icon('heroicon-o-banknotes')
            ->warning()
            ->actions([
                Action::make('view')
                    ->label('Ver Solicitud')
                    ->url('/investment-sheets/consolidated')
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
