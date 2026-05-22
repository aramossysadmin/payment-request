<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class InvestmentBatchRequesterSummaryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Collection $approvedPayments,
        public Collection $rejectedPayments,
        public ?string $rejectionReason = null,
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
        $this->approvedPayments->loadMissing(['investmentRequest.investmentExpenseConcept', 'currency']);
        $this->rejectedPayments->loadMissing(['investmentRequest.investmentExpenseConcept', 'currency']);

        $approvedCount = $this->approvedPayments->count();
        $rejectedCount = $this->rejectedPayments->count();

        [$subject, $description] = $this->resolveSubjectAndDescription($approvedCount, $rejectedCount);

        $approvedItems = $this->approvedPayments->map(fn ($p) => $this->mapPayment($p))->toArray();
        $rejectedItems = $this->rejectedPayments->map(fn ($p) => $this->mapPayment($p))->toArray();

        return (new MailMessage)
            ->subject($subject)
            ->markdown('emails.investment-batch-requester-summary', [
                'greeting' => 'Hola '.$notifiable->name,
                'description' => $description,
                'approvedItems' => $approvedItems,
                'rejectedItems' => $rejectedItems,
                'rejectionReason' => $this->rejectionReason,
                'actionUrl' => url('/investment-sheets/consolidated'),
                'actionText' => 'Ver mis pagos',
                'salutation' => 'Saludos, '.config('app.name'),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private function resolveSubjectAndDescription(int $approvedCount, int $rejectedCount): array
    {
        if ($approvedCount > 0 && $rejectedCount === 0) {
            return [
                "Tus pagos de inversión fueron aprobados ({$approvedCount})",
                'El CEO ha aprobado tus pagos. Sigue el flujo de revisión del Project Manager y la aprobación final.',
            ];
        }

        if ($approvedCount === 0 && $rejectedCount > 0) {
            return [
                "Tus pagos de inversión fueron rechazados ({$rejectedCount})",
                'El CEO ha rechazado tus pagos. El presupuesto fue liberado y puedes capturar nuevos si lo requieres.',
            ];
        }

        return [
            "Resultado de tus pagos de inversión: {$approvedCount} aprobados, {$rejectedCount} rechazados",
            'El CEO ha revisado tus pagos. Aquí está el resumen consolidado del lote.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapPayment($payment): array
    {
        return [
            'folio' => $payment->folio_number,
            'provider' => $payment->provider,
            'concept' => $payment->investmentRequest?->investmentExpenseConcept?->name ?? '-',
            'description' => $payment->description ?? '-',
            'total' => number_format((float) $payment->total, 2),
            'currency' => $payment->currency?->prefix ?? 'MXN',
        ];
    }
}
