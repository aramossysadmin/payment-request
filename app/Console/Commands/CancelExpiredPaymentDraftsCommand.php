<?php

namespace App\Console\Commands;

use App\Models\InvestmentPaymentBatch;
use App\Models\InvestmentPaymentRequest;
use App\Models\User;
use App\Notifications\DraftPaymentsAutoCancelledNotification;
use App\Notifications\DraftPaymentsAutoCancelledPmSummaryNotification;
use App\Services\PaymentRequestPolicyService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CancelExpiredPaymentDraftsCommand extends Command
{
    protected $signature = 'payment-drafts:cancel-expired
                            {--dry-run : Solo muestra qué se cancelaría, sin tocar BD ni enviar correos}';

    protected $description = 'Cancela drafts no enviados al cierre de la ventana de submit. Idempotente.';

    public function handle(): int
    {
        $service = PaymentRequestPolicyService::fromCurrent();

        if (! $service->isSubmitWindowActive()) {
            $this->info('Ventana de submit inactiva. No hay cancelación automática.');

            return self::SUCCESS;
        }

        $policy = $service->policy;
        $now = CarbonImmutable::now(config('app.timezone'));

        if ($service->isWithinSubmitWindow($now)) {
            $this->info('Ventana de submit abierta. No cancelo nada todavía.');

            return self::SUCCESS;
        }

        $lastClose = $service->getCurrentSubmitClosesAt($now);
        if ($lastClose->gt($now)) {
            $lastClose = $lastClose->subWeek();
        }

        $cancellationActivatedAt = $policy->cancellation_activated_at;
        if (! $cancellationActivatedAt) {
            $this->info('cancellation_activated_at no está marcado. Ningún draft elegible.');

            return self::SUCCESS;
        }

        $expired = InvestmentPaymentRequest::query()
            ->where('status', 'draft')
            ->where('created_at', '>=', $cancellationActivatedAt)
            ->where('created_at', '<=', $lastClose)
            ->with(['user', 'department', 'currency', 'investmentRequest.investmentExpenseConcept.category', 'batch'])
            ->get();

        if ($expired->isEmpty()) {
            $this->info('No hay drafts expirados que cancelar.');

            return self::SUCCESS;
        }

        $isDryRun = $this->option('dry-run');
        $this->info(($isDryRun ? '[DRY-RUN] ' : '').'Drafts expirados: '.$expired->count());

        if ($isDryRun) {
            foreach ($expired as $payment) {
                $this->line("  #{$payment->folio_number} {$payment->provider} — {$payment->user?->name} — \${$payment->total}");
            }

            return self::SUCCESS;
        }

        DB::transaction(function () use ($expired, $lastClose) {
            foreach ($expired as $payment) {
                $payment->update([
                    'status' => 'auto_cancelled',
                    'auto_cancelled_at' => now(),
                    'auto_cancellation_reason' => "Ventana de envío cerró el {$lastClose->locale('es_MX')->translatedFormat('l j M H:i')} sin que el draft fuera enviado al CEO.",
                ]);
            }

            $batchIds = $expired->pluck('batch_id')->unique()->filter();
            foreach ($batchIds as $batchId) {
                $remainingActive = InvestmentPaymentRequest::query()
                    ->where('batch_id', $batchId)
                    ->whereNotIn('status', ['rejected', 'ceo_rejected', 'projectmanager_rejected', 'final_rejected', 'auto_cancelled'])
                    ->exists();

                if (! $remainingActive) {
                    InvestmentPaymentBatch::where('id', $batchId)
                        ->where('status', 'draft')
                        ->update(['status' => 'auto_cancelled']);
                }
            }
        });

        $expired->groupBy('user_id')->each(function ($userPayments) use ($lastClose) {
            $user = $userPayments->first()->user;
            if (! $user) {
                return;
            }
            $user->notify(new DraftPaymentsAutoCancelledNotification(
                cancelledPayments: $userPayments,
                windowClosedAt: $lastClose,
            ));
        });

        $pms = User::role('project_manager')->get();
        $pms->each(function ($pm) use ($expired, $lastClose) {
            $pm->notify(new DraftPaymentsAutoCancelledPmSummaryNotification(
                allCancelledPayments: $expired,
                windowClosedAt: $lastClose,
            ));
        });

        $this->info("✅ Cancelados: {$expired->count()} drafts. Notificaciones encoladas.");

        return self::SUCCESS;
    }
}
