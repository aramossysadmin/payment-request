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

    /**
     * @param  'ceo'|'final'  $stage
     */
    public function __construct(
        public Collection $approvedPayments,
        public Collection $rejectedPayments,
        public ?string $rejectionReason = null,
        public string $stage = 'ceo',
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
        // Cargar por elemento en lugar de sobre la collection: `loadMissing` como método de
        // collection sólo existe en Eloquent Collection, y aquí las notifications se llaman
        // con Support Collection también (patrón `collect()->push()` en varios callers).
        // Model::loadMissing es idempotente — no re-consulta si ya está eager-loaded.
        $relations = ['investmentRequest.investmentExpenseConcept', 'investmentRequest.project', 'currency'];
        $this->approvedPayments->each(fn ($p) => $p->loadMissing($relations));
        $this->rejectedPayments->each(fn ($p) => $p->loadMissing($relations));

        $approvedCount = $this->approvedPayments->count();
        $rejectedCount = $this->rejectedPayments->count();

        $firstPayment = $this->approvedPayments->first() ?? $this->rejectedPayments->first();
        $projectName = $firstPayment?->investmentRequest?->project?->name ?? 'Sin proyecto';

        [$subject, $description] = $this->resolveSubjectAndDescription($approvedCount, $rejectedCount, $projectName);

        $approvedItems = $this->approvedPayments->map(fn ($p) => $this->mapPayment($p))->toArray();
        $rejectedItems = $this->rejectedPayments->map(fn ($p) => $this->mapPayment($p))->toArray();

        return (new MailMessage)
            ->subject($subject)
            ->markdown('emails.investment-batch-requester-summary', [
                'greeting' => 'Hola '.$notifiable->name,
                'description' => $description,
                'projectName' => $projectName,
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
    private function resolveSubjectAndDescription(int $approvedCount, int $rejectedCount, string $projectName): array
    {
        return match ($this->stage) {
            'final' => $this->finalCopy($approvedCount, $rejectedCount, $projectName),
            default => $this->ceoCopy($approvedCount, $rejectedCount, $projectName),
        };
    }

    /**
     * @return array<int, string>
     */
    private function ceoCopy(int $approvedCount, int $rejectedCount, string $projectName): array
    {
        if ($approvedCount > 0 && $rejectedCount === 0) {
            return [
                "Tus pagos de inversión en {$projectName} fueron aprobados ({$approvedCount})",
                "El CEO ha aprobado tus pagos del proyecto {$projectName}. Sigue con la revisión final del Project Manager.",
            ];
        }

        if ($approvedCount === 0 && $rejectedCount > 0) {
            return [
                "Tus pagos de inversión en {$projectName} fueron rechazados ({$rejectedCount})",
                "El CEO ha rechazado tus pagos del proyecto {$projectName}. El presupuesto fue liberado y puedes capturar nuevos si lo requieres.",
            ];
        }

        return [
            "Resultado de tus pagos en {$projectName}: {$approvedCount} aprobados, {$rejectedCount} rechazados",
            "El CEO ha revisado tus pagos del proyecto {$projectName}. Aquí está el resumen consolidado del lote.",
        ];
    }

    /**
     * @return array<int, string>
     */
    private function finalCopy(int $approvedCount, int $rejectedCount, string $projectName): array
    {
        if ($approvedCount > 0 && $rejectedCount === 0) {
            return [
                "Aprobación final de tus pagos en {$projectName} ({$approvedCount})",
                "El Project Manager ha dado la aprobación final a tus pagos del proyecto {$projectName}. Ahora debes subir los documentos correspondientes para finalizar el proceso.",
            ];
        }

        if ($approvedCount === 0 && $rejectedCount > 0) {
            return [
                "Tus pagos en {$projectName} fueron rechazados en la aprobación final ({$rejectedCount})",
                "El Project Manager ha rechazado tus pagos del proyecto {$projectName} en la aprobación final. El presupuesto fue liberado.",
            ];
        }

        return [
            "Aprobación final en {$projectName}: {$approvedCount} aprobados, {$rejectedCount} rechazados",
            "El Project Manager ha completado la aprobación final de tus pagos del proyecto {$projectName}. Aquí está el resumen.",
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapPayment($payment): array
    {
        // Respetar el monto ajustado por el PM si existe; si no, usar el original.
        // El bug previo mostraba siempre `total` y confundía al solicitante cuando
        // el PM ajustaba el pago (ej. pidió $5,000 y el PM aprobó $3,000).
        $finalAmount = $payment->approved_amount !== null
            ? (float) $payment->approved_amount
            : (float) $payment->total;

        $wasAdjusted = $payment->approved_amount !== null
            && (float) $payment->approved_amount !== (float) $payment->total;

        return [
            'folio' => $payment->folio_number,
            'provider' => $payment->provider,
            'concept' => $payment->investmentRequest?->investmentExpenseConcept?->name ?? '-',
            'description' => $payment->description ?? '-',
            'total' => number_format($finalAmount, 2),
            'was_adjusted' => $wasAdjusted,
            'original_total' => $wasAdjusted ? number_format((float) $payment->total, 2) : null,
            'currency' => $payment->currency?->prefix ?? 'MXN',
        ];
    }
}
