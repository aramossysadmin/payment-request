<?php

namespace App\Notifications;

use App\Models\InvestmentPaymentBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class InvestmentBatchFinalApprovalSessionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Collection<int, InvestmentPaymentBatch>  $batches  Todos los batches del mismo proyecto compartiendo session_token
     */
    public function __construct(public Collection $batches, public string $sessionToken) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->batches->loadMissing([
            'department',
            'project',
            'paymentRequests' => fn ($q) => $q->where('status', 'projectmanager_approved'),
            'paymentRequests.investmentRequest.investmentExpenseConcept',
            'paymentRequests.currency',
        ]);

        $firstBatch = $this->batches->first();
        $projectName = $firstBatch->project->name ?? 'Sin proyecto';

        // Agrupar por departamento + semana + año. Si el mismo solicitante envió
        // varios batches del mismo depto en la misma semana, se muestran unificados
        // (los batches siguen separados en BD, sólo la presentación se consolida).
        $departmentGroups = $this->batches
            ->groupBy(fn ($batch) => ($batch->department_id ?? 0).'|'.$batch->week_number.'|'.$batch->year)
            ->map(function ($groupBatches) {
                $firstBatch = $groupBatches->first();
                $departmentName = $firstBatch->department->name ?? 'Sin departamento';

                $payments = $groupBatches
                    ->flatMap(fn ($batch) => $batch->paymentRequests)
                    ->map(function ($p) {
                        $originalTotal = (float) $p->total;
                        $approvedAmount = (float) ($p->approved_amount ?? $p->total);
                        $adjusted = $approvedAmount < $originalTotal;

                        return [
                            'folio' => $p->folio_number,
                            'provider' => $p->provider,
                            'concept' => $p->investmentRequest?->investmentExpenseConcept?->name ?? '-',
                            'amount' => number_format($approvedAmount, 2),
                            'currency' => $p->currency?->prefix ?? 'MXN',
                            'adjusted' => $adjusted,
                            'original_total' => $adjusted ? number_format($originalTotal, 2) : null,
                        ];
                    })
                    ->toArray();

                $subtotal = $groupBatches->sum(
                    fn ($batch) => $batch->paymentRequests->sum(fn ($p) => (float) ($p->approved_amount ?? $p->total)),
                );

                return [
                    'department' => $departmentName,
                    'week' => $firstBatch->week_number,
                    'year' => $firstBatch->year,
                    'payments' => $payments,
                    'subtotal' => number_format($subtotal, 2),
                ];
            })
            ->values()
            ->toArray();

        $totalPayments = $this->batches->sum(fn ($b) => $b->paymentRequests->count());
        $grandTotal = $this->batches->sum(
            fn ($b) => $b->paymentRequests->sum(fn ($p) => (float) ($p->approved_amount ?? $p->total)),
        );
        $batchesCount = $this->batches->count();

        $actionUrl = url('/investment-final-approval-session/'.$this->sessionToken);

        return (new MailMessage)
            ->subject("Aprobación FINAL — {$projectName} ({$batchesCount} lotes, {$totalPayments} pagos)")
            ->markdown('emails.investment-batch-final-approval-session', [
                'greeting' => 'Hola '.$notifiable->name,
                'projectName' => $projectName,
                'batchesCount' => $batchesCount,
                'totalPayments' => $totalPayments,
                'grandTotal' => number_format($grandTotal, 2),
                'departmentGroups' => $departmentGroups,
                'actionUrl' => $actionUrl,
                'actionText' => 'Revisar todos los lotes',
                'salutation' => 'Saludos, '.config('app.name'),
            ]);
    }
}
