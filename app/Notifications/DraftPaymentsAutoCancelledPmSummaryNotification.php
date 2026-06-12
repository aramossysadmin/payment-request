<?php

namespace App\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class DraftPaymentsAutoCancelledPmSummaryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Collection $allCancelledPayments,
        public CarbonInterface $windowClosedAt,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $byDepartment = $this->allCancelledPayments
            ->groupBy(fn ($p) => $p->department?->name ?? '—')
            ->sortKeys()
            ->map(fn (Collection $items) => [
                'count' => $items->count(),
                'totalByCurrency' => $items
                    ->groupBy(fn ($p) => $p->currency?->prefix ?? 'MXN')
                    ->map(fn (Collection $byCcy) => (float) $byCcy->sum('total'))
                    ->all(),
                'items' => $items,
            ]);

        $grandTotalByCurrency = $this->allCancelledPayments
            ->groupBy(fn ($p) => $p->currency?->prefix ?? 'MXN')
            ->map(fn (Collection $byCcy) => (float) $byCcy->sum('total'))
            ->all();

        return (new MailMessage)
            ->subject('Resumen de cancelaciones automáticas — '.$this->windowClosedAt->locale('es_MX')->translatedFormat('l j M'))
            ->markdown('emails.draft-payments-auto-cancelled-pm-summary', [
                'pm' => $notifiable,
                'byDepartment' => $byDepartment,
                'grandTotalByCurrency' => $grandTotalByCurrency,
                'totalCancelled' => $this->allCancelledPayments->count(),
                'windowClosedAt' => $this->windowClosedAt,
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'draft_payments_auto_cancelled_pm_summary',
            'total_cancelled' => $this->allCancelledPayments->count(),
            'window_closed_at' => $this->windowClosedAt->toIso8601String(),
            'by_department_count' => $this->allCancelledPayments
                ->groupBy(fn ($p) => $p->department?->name ?? '—')
                ->map(fn (Collection $items) => $items->count())
                ->all(),
        ];
    }
}
