<?php

namespace App\Services;

use App\Models\PaymentRequestPolicy;
use App\Models\User;

class PaymentPolicySnoozeService
{
    public function __construct(
        private PaymentPolicyAuditService $audit,
    ) {}

    public function extendCaptureWindow(User $user, int $hours, string $reason): void
    {
        abort_unless(
            $user->hasAnyRole(PaymentRequestPolicy::current()->editor_role_names ?? ['super_admin', 'project_manager']),
            403,
            'No tienes permisos para extender la ventana.'
        );

        $policy = PaymentRequestPolicy::current();
        $current = PaymentRequestPolicyService::fromCurrent()->getCurrentCaptureClosesAt();

        $policy->update([
            'capture_window_snoozed_until' => $current->addHours($hours),
            'capture_window_snooze_reason' => $reason,
        ]);

        PaymentRequestPolicyService::flushCache();
        $this->audit->logSnoozeExtended($user, 'Captura', $hours, $reason);
    }

    public function extendSubmitWindow(User $user, int $hours, string $reason): void
    {
        abort_unless(
            $user->hasAnyRole(PaymentRequestPolicy::current()->editor_role_names ?? ['super_admin', 'project_manager']),
            403,
            'No tienes permisos para extender la ventana.'
        );

        $policy = PaymentRequestPolicy::current();
        $current = PaymentRequestPolicyService::fromCurrent()->getCurrentSubmitClosesAt();

        $policy->update([
            'submit_window_snoozed_until' => $current->addHours($hours),
            'submit_window_snooze_reason' => $reason,
        ]);

        PaymentRequestPolicyService::flushCache();
        $this->audit->logSnoozeExtended($user, 'Submit', $hours, $reason);
    }

    public function clearCaptureSnooze(): void
    {
        PaymentRequestPolicy::current()->update([
            'capture_window_snoozed_until' => null,
            'capture_window_snooze_reason' => null,
        ]);
        PaymentRequestPolicyService::flushCache();
    }

    public function clearSubmitSnooze(): void
    {
        PaymentRequestPolicy::current()->update([
            'submit_window_snoozed_until' => null,
            'submit_window_snooze_reason' => null,
        ]);
        PaymentRequestPolicyService::flushCache();
    }
}
