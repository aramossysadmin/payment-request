<?php

namespace App\Services;

use App\Models\InvestmentPaymentRequest;
use App\Models\User;
use App\Models\WeeklyPaymentSchedule;
use App\Models\WeeklyPaymentScheduleApproval;
use App\Notifications\WeeklyPaymentScheduleCreated;
use Illuminate\Support\Str;

class WeeklyPaymentScheduleApprovalService
{
    private const APPROVER_EMAIL = 'victor.setien@grupocosteno.com';

    public function createApproval(WeeklyPaymentSchedule $schedule): void
    {
        $approver = User::where('email', self::APPROVER_EMAIL)->first();

        if (! $approver) {
            return;
        }

        $approval = WeeklyPaymentScheduleApproval::create([
            'weekly_payment_schedule_id' => $schedule->id,
            'user_id' => $approver->id,
            'status' => 'pending',
        ]);

        $approval->approval_token = Str::uuid()->toString();
        $approval->approval_token_expires_at = now()->addHours(48);
        $approval->save();

        $approver->notify(new WeeklyPaymentScheduleCreated($schedule, $approval->approval_token));
    }

    public function approve(WeeklyPaymentSchedule $schedule, User $approver): void
    {
        $approval = $schedule->approvals()
            ->where('user_id', $approver->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if (! $approval) {
            return;
        }

        $approval->status = 'approved';
        $approval->responded_at = now();
        $approval->approval_token = null;
        $approval->approval_token_expires_at = null;
        $approval->save();

        $schedule->update(['status' => 'approved']);

        // Move included payments to scheduled_for_bank
        $includedPaymentIds = $schedule->items()
            ->where('included', true)
            ->pluck('investment_payment_request_id');

        InvestmentPaymentRequest::whereIn('id', $includedPaymentIds)
            ->update(['status' => 'scheduled_for_bank']);
    }

    public function reject(WeeklyPaymentSchedule $schedule, User $approver, string $comments): void
    {
        $approval = $schedule->approvals()
            ->where('user_id', $approver->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if (! $approval) {
            return;
        }

        $approval->status = 'rejected';
        $approval->comments = $comments;
        $approval->responded_at = now();
        $approval->approval_token = null;
        $approval->approval_token_expires_at = null;
        $approval->save();

        $schedule->update(['status' => 'rejected']);
    }
}
