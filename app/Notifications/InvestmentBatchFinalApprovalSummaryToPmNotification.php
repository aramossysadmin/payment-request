<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class InvestmentBatchFinalApprovalSummaryToPmNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Collection $approvedPayments,
        public Project $project,
        public User $approverCeo,
        public CarbonInterface $approvedAt,
        public int $rejectedCount = 0,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $byDepartment = $this->approvedPayments
            ->groupBy(fn ($p) => $p->department?->name ?? '—')
            ->sortKeys()
            ->map(fn (Collection $items) => [
                'count' => $items->count(),
                'totalByCurrency' => $items
                    ->groupBy(fn ($p) => $p->currency?->prefix ?? 'MXN')
                    ->map(fn (Collection $byCcy) => (float) $byCcy->sum(fn ($p) => (float) ($p->approved_amount ?? $p->total)))
                    ->all(),
                'items' => $items,
            ]);

        $grandTotalByCurrency = $this->approvedPayments
            ->groupBy(fn ($p) => $p->currency?->prefix ?? 'MXN')
            ->map(fn (Collection $byCcy) => (float) $byCcy->sum(fn ($p) => (float) ($p->approved_amount ?? $p->total)))
            ->all();

        $viewData = [
            'pm' => $notifiable,
            'project' => $this->project,
            'approverCeo' => $this->approverCeo,
            'approvedAt' => $this->approvedAt,
            'byDepartment' => $byDepartment,
            'grandTotalByCurrency' => $grandTotalByCurrency,
            'totalPayments' => $this->approvedPayments->count(),
            'rejectedCount' => $this->rejectedCount,
            'portalUrl' => route('investment-sheets.consolidated', $this->project),
        ];

        $pdf = Pdf::loadView('pdf.final-approval-summary-pm', $viewData)
            ->setPaper('letter', 'landscape')
            ->output();

        $filename = sprintf(
            'aprobacion-final-ceo-%s-%s.pdf',
            Str::slug($this->project->name),
            $this->approvedAt->format('Ymd-Hi')
        );

        return (new MailMessage)
            ->subject("Aprobación final CEO — {$this->project->name} ({$this->approvedPayments->count()} pagos)")
            ->markdown('emails.final-approval-summary-pm', $viewData)
            ->attachData($pdf, $filename, ['mime' => 'application/pdf']);
    }
}
