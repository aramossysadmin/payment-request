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
        $allPending = InvestmentPaymentRequest::query()
            ->whereIn('status', ['ceo_approved', 'projectmanager_review'])
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
            ? "{$newCount} pagos nuevos listos para revisión (+ {$historicalCount} pendientes anteriores)"
            : "{$newCount} pagos nuevos listos para revisión";

        $description = $historicalCount > 0
            ? 'El CEO aprobó nuevos pagos. Aquí está el panorama completo de tu cola de revisión, separado entre lo que llegó ahora y lo que sigue pendiente de revisiones anteriores.'
            : 'El CEO aprobó nuevos pagos. Están listos para tu revisión antes de la aprobación final del CEO.';

        return (new MailMessage)
            ->subject($subject)
            ->markdown('emails.investment-batch-ready-for-review', [
                'greeting' => 'Hola '.$notifiable->name,
                'description' => $description,
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
