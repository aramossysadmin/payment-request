<?php

namespace App\Console\Commands;

use App\Models\InvestmentPaymentRequest;
use App\Notifications\DraftPaymentsClosingSoonReminderNotification;
use App\Services\PaymentRequestPolicyService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class NotifyClosingSoonReminderCommand extends Command
{
    protected $signature = 'payment-drafts:notify-closing-soon';

    protected $description = 'Recordatorio 2h antes del cierre de la ventana de submit. Idempotente.';

    public function handle(): int
    {
        $service = PaymentRequestPolicyService::fromCurrent();

        if (! $service->isSubmitWindowActive()) {
            return self::SUCCESS;
        }

        $now = CarbonImmutable::now(config('app.timezone'));

        if (! $service->isWithinSubmitWindow($now)) {
            return self::SUCCESS;
        }

        $closesAt = $service->getCurrentSubmitClosesAt($now);
        $minutesUntilClose = $now->diffInMinutes($closesAt, false);

        // Ventana: 110 a 130 minutos antes del cierre (poll cada 5 min cubre la ventana de 2h).
        if ($minutesUntilClose < 110 || $minutesUntilClose > 130) {
            return self::SUCCESS;
        }

        // Idempotencia: no spammear si ya se envió para este cierre exacto.
        $cacheKey = 'closing_soon_reminder_sent:'.$closesAt->toIso8601String();
        if (Cache::has($cacheKey)) {
            return self::SUCCESS;
        }

        // Buscar usuarios con drafts pendientes.
        $drafts = InvestmentPaymentRequest::query()
            ->where('status', 'draft')
            ->with(['user', 'currency'])
            ->get()
            ->groupBy('user_id');

        if ($drafts->isEmpty()) {
            Cache::put($cacheKey, true, $closesAt->diffInSeconds($now) + 3600);

            return self::SUCCESS;
        }

        foreach ($drafts as $userPayments) {
            $user = $userPayments->first()->user;
            if (! $user) {
                continue;
            }
            $user->notify(new DraftPaymentsClosingSoonReminderNotification(
                pendingDrafts: $userPayments,
                closesAt: $closesAt,
            ));
        }

        // Cache hasta después del cierre + 1h margen.
        Cache::put($cacheKey, true, $closesAt->diffInSeconds($now) + 3600);

        $this->info('Recordatorios enviados a '.$drafts->count().' usuarios.');

        return self::SUCCESS;
    }
}
