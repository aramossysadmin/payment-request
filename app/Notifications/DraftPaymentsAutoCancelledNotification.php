<?php

namespace App\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class DraftPaymentsAutoCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Collection $cancelledPayments,
        public CarbonInterface $windowClosedAt,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = $this->cancelledPayments->count();
        $totalByCcy = $this->cancelledPayments
            ->groupBy(fn ($p) => $p->currency?->prefix ?? 'MXN')
            ->map(fn (Collection $rows) => (float) $rows->sum('total'))
            ->all();

        return (new MailMessage)
            ->subject("Tus {$count} ".($count === 1 ? 'pago no enviado fue cancelado' : 'pagos no enviados fueron cancelados'))
            ->markdown('emails.draft-payments-auto-cancelled', [
                'user' => $notifiable,
                'cancelledPayments' => $this->cancelledPayments,
                'totalByCcy' => $totalByCcy,
                'windowClosedAt' => $this->windowClosedAt,
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'draft_payments_auto_cancelled',
            'count' => $this->cancelledPayments->count(),
            'window_closed_at' => $this->windowClosedAt->toIso8601String(),
            'payments' => $this->cancelledPayments->map(fn ($p) => [
                'uuid' => $p->uuid,
                'folio_number' => $p->folio_number,
                'provider' => $p->provider,
                'total' => $p->total,
                'currency_prefix' => $p->currency?->prefix ?? 'MXN',
            ])->all(),
        ];
    }
}
