<?php

namespace App\Services;

use App\Models\InvestmentPaymentApproval;
use App\Models\InvestmentPaymentRequest;
use App\Models\User;
use App\Notifications\InvestmentPaymentRequestCreated;
use Illuminate\Support\Str;

class InvestmentPaymentApprovalService
{
    private const APPROVER_EMAIL = 'victor.setien@grupocosteno.com';

    public function createApproval(InvestmentPaymentRequest $paymentRequest): void
    {
        $approver = User::where('email', self::APPROVER_EMAIL)->first();

        if (! $approver) {
            return;
        }

        $approval = InvestmentPaymentApproval::create([
            'investment_payment_request_id' => $paymentRequest->id,
            'user_id' => $approver->id,
            'status' => 'pending',
        ]);

        $approval->approval_token = Str::uuid()->toString();
        $approval->approval_token_expires_at = now()->addHours(48);
        $approval->save();

        $approver->notify(new InvestmentPaymentRequestCreated($paymentRequest, $approval->approval_token));
    }

    public function approve(InvestmentPaymentRequest $paymentRequest, User $approver): void
    {
        $approval = $paymentRequest->approvals()
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

        $paymentRequest->update(['status' => 'approved']);
    }

    public function reject(InvestmentPaymentRequest $paymentRequest, User $approver, string $comments): void
    {
        $approval = $paymentRequest->approvals()
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

        $paymentRequest->update(['status' => 'rejected']);
    }
}
