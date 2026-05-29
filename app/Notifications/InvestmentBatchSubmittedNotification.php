<?php

namespace App\Notifications;

use App\Models\InvestmentPaymentBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvestmentBatchSubmittedNotification extends Notification implements ShouldQueue
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
            'paymentRequests.investmentRequest.investmentExpenseConcept',
            'paymentRequests.currency',
        ]);

        $items = $this->batch->paymentRequests->map(fn ($p) => [
            'included' => true,
            'folio' => $p->folio_number,
            'provider' => $p->provider,
            'concept' => $p->investmentRequest?->investmentExpenseConcept?->name ?? '-',
            'description' => $p->description ?? '-',
            'total' => number_format((float) $p->total, 2),
            'currency' => $p->currency?->prefix ?? 'MXN',
        ])->toArray();

        $batchTotal = $this->batch->paymentRequests->sum(fn ($p) => (float) $p->total);

        $details = [
            ['label' => 'Solicitante', 'value' => $this->batch->user->name ?? '-'],
            ['label' => 'Departamento', 'value' => $this->batch->department->name ?? '-'],
            ['label' => 'Proyecto', 'value' => $this->batch->project->name ?? '-'],
            ['label' => 'Semana / Año', 'value' => 'Semana '.$this->batch->week_number.' ('.$this->batch->year.')'],
            ['label' => 'Cantidad de pagos', 'value' => (string) $this->batch->paymentRequests->count()],
            ['label' => 'Total del lote', 'value' => '$ '.number_format($batchTotal, 2).' MXN'],
        ];

        $actionUrl = url('/investment-batch-approval/'.$this->batch->ceo_approval_token);

        $projectName = $this->batch->project->name ?? 'Sin proyecto';
        $departmentName = $this->batch->department->name ?? 'Sin departamento';
        $paymentsCount = $this->batch->paymentRequests->count();

        return (new MailMessage)
            ->subject("Lote pendiente — {$projectName} · {$departmentName} ({$paymentsCount} pagos)")
            ->markdown('emails.request-notification', [
                'sectionTitle' => "Proyecto: {$projectName}",
                'greeting' => 'Hola '.$notifiable->name,
                'description' => 'Tienes un lote de pagos de inversión pendiente de tu autorización. Puedes revisar cada pago y decidir cuáles aprobar o rechazar.',
                'details' => $details,
                'paymentItems' => $items,
                'documents' => [],
                'actionUrl' => $actionUrl,
                'actionText' => 'Revisar y Autorizar Pagos',
                'footerLines' => ['Este enlace es válido por 96 horas.'],
                'salutation' => 'Saludos, '.config('app.name'),
            ]);
    }
}
