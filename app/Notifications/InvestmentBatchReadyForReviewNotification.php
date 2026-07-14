<?php

namespace App\Notifications;

use App\Models\InvestmentPaymentBatch;
use App\Models\InvestmentPaymentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvestmentBatchReadyForReviewNotification extends Notification implements ShouldQueue
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
        $projectId = $this->batch->project_id;
        $projectName = $this->batch->project->name ?? 'Sin proyecto';

        // Solo pagos del MISMO proyecto que el batch que dispara este correo.
        // Cada proyecto tiene su propio flujo de autorizaciones, no se mezclan.
        $allPending = InvestmentPaymentRequest::query()
            ->whereIn('status', ['ceo_approved', 'projectmanager_review'])
            ->whereHas('investmentRequest', fn ($q) => $q->where('project_id', $projectId))
            ->with([
                'investmentRequest.investmentExpenseConcept',
                'department',
                'currency',
            ])
            ->orderBy('department_id')
            ->orderBy('folio_number')
            ->get();

        $newPayments = $allPending->where('batch_id', $this->batch->id);
        $historicalPayments = $allPending->where('batch_id', '!=', $this->batch->id);

        $newGroups = $this->groupByDepartment($newPayments);
        $historicalGroups = $this->groupByDepartment($historicalPayments);

        $newTotal = $newPayments->sum(fn ($p) => (float) $p->total);
        $historicalTotal = $historicalPayments->sum(fn ($p) => (float) $p->total);
        $grandTotal = $newTotal + $historicalTotal;

        $newCount = $newPayments->count();
        $historicalCount = $historicalPayments->count();

        $subject = $historicalCount > 0
            ? "{$newCount} pagos nuevos en {$projectName} (+ {$historicalCount} pendientes anteriores del mismo proyecto)"
            : "{$newCount} pagos nuevos en {$projectName}";

        $description = $historicalCount > 0
            ? "El CEO aprobó nuevos pagos del proyecto {$projectName}. Aquí está el panorama de tu cola de revisión, separado entre lo que llegó ahora y lo pendiente de revisiones anteriores del mismo proyecto."
            : "El CEO aprobó nuevos pagos del proyecto {$projectName}. Están listos para tu revisión y aprobación final.";

        return (new MailMessage)
            ->subject($subject)
            ->markdown('emails.investment-batch-ready-for-review', [
                'greeting' => 'Hola '.$notifiable->name,
                'description' => $description,
                'projectName' => $projectName,
                'newGroups' => $newGroups,
                'newCount' => $newCount,
                'newTotal' => number_format($newTotal, 2),
                'historicalGroups' => $historicalGroups,
                'historicalCount' => $historicalCount,
                'historicalTotal' => number_format($historicalTotal, 2),
                'grandTotal' => number_format($grandTotal, 2),
                'actionUrl' => url('/investment-payment-review'),
                'actionText' => 'Revisar pagos',
                'salutation' => 'Saludos, '.config('app.name'),
            ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function groupByDepartment($payments): array
    {
        return $payments
            ->groupBy(fn ($p) => $p->department?->name ?? 'Sin departamento')
            ->map(fn ($groupPayments, string $departmentName) => [
                'department' => $departmentName,
                'payments' => $groupPayments->map(fn ($p) => [
                    'folio' => $p->folio_number,
                    'provider' => $p->provider,
                    'concept' => $p->investmentRequest?->investmentExpenseConcept?->name ?? '-',
                    'total' => number_format((float) $p->total, 2),
                    'currency' => $p->currency?->prefix ?? 'MXN',
                ])->values()->toArray(),
            ])
            ->values()
            ->toArray();
    }
}
