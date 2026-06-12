<?php

namespace App\Services;

use App\Models\PaymentCaptureAttemptLog;
use App\Models\User;
use Illuminate\Http\Request;

class PaymentPolicyAuditService
{
    public function logBlockedAttempt(User $user, string $action, ?Request $request = null, ?string $notes = null): void
    {
        $service = PaymentRequestPolicyService::fromCurrent();
        $now = now(config('app.timezone'));

        PaymentCaptureAttemptLog::create([
            'user_id' => $user->id,
            'attempted_at' => $now,
            'action' => $action,
            'was_blocked' => true,
            'ip_address' => $request?->ip(),
            'policy_snapshot' => $this->snapshot($service),
            'notes' => $notes,
        ]);
    }

    public function logSnoozeExtended(User $user, string $windowType, int $hours, string $reason, ?Request $request = null): void
    {
        $service = PaymentRequestPolicyService::fromCurrent();
        $now = now(config('app.timezone'));

        PaymentCaptureAttemptLog::create([
            'user_id' => $user->id,
            'attempted_at' => $now,
            'action' => 'snooze_extended',
            'was_blocked' => false,
            'ip_address' => $request?->ip(),
            'policy_snapshot' => $this->snapshot($service),
            'notes' => "{$windowType} extendida {$hours}h. Motivo: {$reason}",
        ]);
    }

    private function snapshot(PaymentRequestPolicyService $service): array
    {
        $policy = $service->policy;

        return [
            'capture_window_is_active' => $policy->capture_window_is_active,
            'capture_open' => "{$policy->capture_open_day} {$policy->capture_open_time}",
            'capture_close' => "{$policy->capture_close_day} {$policy->capture_close_time}",
            'submit_window_is_active' => $policy->submit_window_is_active,
            'submit_open' => "{$policy->submit_open_day} {$policy->submit_open_time}",
            'submit_close' => "{$policy->submit_close_day} {$policy->submit_close_time}",
        ];
    }
}
