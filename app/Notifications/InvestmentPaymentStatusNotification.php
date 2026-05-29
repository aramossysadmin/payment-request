<?php

namespace App\Notifications;

use App\Models\InvestmentPaymentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvestmentPaymentStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  'ceo_approved'|'ceo_rejected'|'projectmanager_approved'|'projectmanager_rejected'|'final_approved'|'final_rejected'  $stage
     */
    public function __construct(
        public InvestmentPaymentRequest $paymentRequest,
        public string $stage,
        public ?string $reason = null,
        public ?string $adjustedAmount = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->paymentRequest->loadMissing([
            'investmentRequest.investmentExpenseConcept',
            'investmentRequest.project',
            'currency',
        ]);

        [$stageLabel, $resultLabel, $description, $banner] = $this->resolveStageInfo();

        $projectName = $this->paymentRequest->investmentRequest?->project?->name ?? 'Sin proyecto';

        $details = [
            ['label' => 'Proyecto', 'value' => $projectName],
            ['label' => 'Folio', 'value' => '#'.str_pad((string) $this->paymentRequest->folio_number, 5, '0', STR_PAD_LEFT)],
            ['label' => 'Concepto', 'value' => $this->paymentRequest->investmentRequest?->investmentExpenseConcept?->name ?? '-'],
            ['label' => 'Proveedor', 'value' => $this->paymentRequest->provider],
            ['label' => 'Monto solicitado', 'value' => '$ '.number_format((float) $this->paymentRequest->total, 2).' '.($this->paymentRequest->currency?->prefix ?? 'MXN')],
            ['label' => 'Etapa', 'value' => $stageLabel],
            ['label' => 'Resultado', 'value' => $resultLabel],
        ];

        if ($this->adjustedAmount !== null) {
            $details[] = ['label' => 'Monto aprobado', 'value' => '$ '.number_format((float) $this->adjustedAmount, 2).' '.($this->paymentRequest->currency?->prefix ?? 'MXN')];
        }

        if ($this->reason) {
            $details[] = ['label' => 'Motivo', 'value' => $this->reason];
        }

        return (new MailMessage)
            ->subject("Actualización de pago #{$this->paymentRequest->folio_number} en {$projectName}")
            ->markdown('emails.request-notification', [
                'banner' => $banner,
                'sectionTitle' => "Estado de tu Solicitud — {$projectName}",
                'greeting' => 'Hola '.$notifiable->name,
                'description' => $description,
                'details' => $details,
                'documents' => [],
                'actionUrl' => url('/investment-sheets/consolidated'),
                'actionText' => 'Ver Mis Solicitudes',
                'footerLines' => [],
                'salutation' => 'Saludos, '.config('app.name'),
            ]);
    }

    /**
     * @return array{0: string, 1: string, 2: string, 3: ?string}
     */
    private function resolveStageInfo(): array
    {
        return match ($this->stage) {
            'ceo_approved' => [
                'CEO — Primera revisión',
                'Aprobado ✓',
                'Tu solicitud de pago fue aprobada por el CEO en la primera revisión. Sigue su curso al siguiente paso del flujo.',
                null,
            ],
            'ceo_rejected' => [
                'CEO — Primera revisión',
                'Rechazado ✗',
                'Tu solicitud de pago fue rechazada por el CEO. El presupuesto fue liberado y puedes capturar un nuevo pago si es necesario.',
                'Solicitud Rechazada',
            ],
            'projectmanager_approved' => [
                'Project Manager — Revisión',
                'Aprobado ✓',
                'Tu solicitud de pago fue aprobada por el Project Manager. Sigue al siguiente paso del flujo.',
                null,
            ],
            'projectmanager_rejected' => [
                'Project Manager — Revisión',
                'Rechazado ✗',
                'Tu solicitud de pago fue rechazada por el Project Manager. El presupuesto fue liberado.',
                'Solicitud Rechazada',
            ],
            'final_approved' => [
                'CEO — Aprobación final',
                'Aprobado definitivamente ✓',
                'Tu solicitud de pago fue aprobada definitivamente. Ahora debes subir los documentos correspondientes para finalizar el proceso.',
                null,
            ],
            'final_rejected' => [
                'CEO — Aprobación final',
                'Rechazado ✗',
                'Tu solicitud de pago fue rechazada en la aprobación final. El presupuesto fue liberado.',
                'Solicitud Rechazada',
            ],
            default => [
                'Actualización',
                'Cambio de estado',
                'Hay una actualización en tu solicitud de pago.',
                null,
            ],
        };
    }
}
