<?php

namespace App\Notifications;

use App\Models\InvestmentPaymentBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvestmentBatchFinalApprovalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public InvestmentPaymentBatch $batch) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->batch->loadMissing([
            'department',
            'project',
            'user',
            'paymentRequests' => fn ($q) => $q->where('status', 'projectmanager_approved'),
            'paymentRequests.investmentRequest.investmentExpenseConcept',
            'paymentRequests.currency',
        ]);

        $items = $this->batch->paymentRequests->map(function ($p) {
            $originalTotal = (float) $p->total;
            $approvedAmount = (float) ($p->approved_amount ?? $p->total);
            $adjusted = $approvedAmount < $originalTotal;
            $description = $p->description ?? '-';
            if ($adjusted) {
                $description = sprintf('Ajustado por PM: solicitado $%s → aprobado $%s', number_format($originalTotal, 2), number_format($approvedAmount, 2));
            }

            return [
                'included' => true,
                'folio' => $p->folio_number,
                'provider' => $p->provider,
                'concept' => $p->investmentRequest?->investmentExpenseConcept?->name ?? '-',
                'description' => $description,
                'total' => number_format($approvedAmount, 2),
                'currency' => $p->currency?->prefix ?? 'MXN',
            ];
        })->toArray();

        $batchTotal = $this->batch->paymentRequests->sum(fn ($p) => (float) ($p->approved_amount ?? $p->total));

        $details = [
            ['label' => 'Solicitante', 'value' => $this->batch->user->name ?? '-'],
            ['label' => 'Departamento', 'value' => $this->batch->department->name ?? '-'],
            ['label' => 'Proyecto', 'value' => $this->batch->project->name ?? '-'],
            ['label' => 'Semana / Año', 'value' => 'Semana '.$this->batch->week_number.' ('.$this->batch->year.')'],
            ['label' => 'Cantidad de pagos', 'value' => (string) $this->batch->paymentRequests->count()],
            ['label' => 'Total final del lote', 'value' => '$ '.number_format($batchTotal, 2).' MXN'],
        ];

        $actionUrl = url('/investment-batch-final-approval/'.$this->batch->final_ceo_approval_token);

        return (new MailMessage)
            ->subject('Aprobación FINAL de pagos de inversión — Lote revisado por PM')
            ->markdown('emails.request-notification', [
                'sectionTitle' => 'Aprobación Final del Lote',
                'greeting' => 'Hola '.$notifiable->name,
                'description' => 'El Project Manager ya revisó este lote de pagos. Algunos montos pudieron haber sido ajustados. Esta es la aprobación final antes de proceder con los pagos.',
                'details' => $details,
                'paymentItems' => $items,
                'documents' => [],
                'actionUrl' => $actionUrl,
                'actionText' => 'Revisar y Autorizar Pagos (Final)',
                'footerLines' => ['Este enlace es válido por 48 horas.'],
                'salutation' => 'Saludos, '.config('app.name'),
            ]);
    }
}
