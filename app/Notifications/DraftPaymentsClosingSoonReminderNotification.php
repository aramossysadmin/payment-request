<?php

namespace App\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class DraftPaymentsClosingSoonReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Collection $pendingDrafts,
        public CarbonInterface $closesAt,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = $this->pendingDrafts->count();
        $totalByCcy = $this->pendingDrafts
            ->groupBy(fn ($p) => $p->currency?->prefix ?? 'MXN')
            ->map(fn (Collection $rows) => (float) $rows->sum('total'))
            ->all();

        return (new MailMessage)
            ->subject("Tienes {$count} ".($count === 1 ? 'pago' : 'pagos').' sin enviar — cierra en 2 horas')
            ->markdown('emails.draft-payments-closing-soon-reminder', [
                'user' => $notifiable,
                'pendingDrafts' => $this->pendingDrafts,
                'totalByCcy' => $totalByCcy,
                'closesAt' => $this->closesAt,
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'draft_payments_closing_soon_reminder',
            'count' => $this->pendingDrafts->count(),
            'closes_at' => $this->closesAt->toIso8601String(),
        ];
    }
}
