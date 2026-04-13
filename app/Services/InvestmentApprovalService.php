<?php

namespace App\Services;

use App\Models\InvestmentRequest;
use App\Models\InvestmentRequestApproval;
use App\Models\User;
use App\Notifications\InvestmentRequestCompleted;
use App\Notifications\InvestmentRequestCreated;
use App\Notifications\InvestmentRequestRejected;
use App\States\InvestmentRequest\Completed;
use App\States\InvestmentRequest\PendingDepartment;
use Illuminate\Support\Str;

class InvestmentApprovalService
{
    /**
     * Create the single approval record and notify the authorizer.
     */
    public function createApprovals(InvestmentRequest $investmentRequest): void
    {
        $authorizer = $this->getAuthorizer();

        if (! $authorizer) {
            return;
        }

        $approval = InvestmentRequestApproval::create([
            'investment_request_id' => $investmentRequest->id,
            'user_id' => $authorizer->id,
            'stage' => 'department',
            'level' => 1,
            'status' => 'pending',
            'approval_token' => Str::uuid()->toString(),
            'approval_token_expires_at' => now()->addHours(48),
        ]);

        $authorizer->notify(new InvestmentRequestCreated($investmentRequest, $approval->approval_token));
    }

    /**
     * Approve the investment request and transition directly to completed.
     *
     * @param  array<string, mixed>  $data
     */
    public function approve(InvestmentRequest $investmentRequest, User $authorizer, array $data = []): void
    {
        if (! $investmentRequest->status->equals(PendingDepartment::class)) {
            return;
        }

        $approval = $this->getPendingApprovalFor($investmentRequest, $authorizer);

        if (! $approval) {
            return;
        }

        $approval->update([
            'status' => 'approved',
            'responded_at' => now(),
            'approval_token' => null,
            'approval_token_expires_at' => null,
        ]);

        $investmentRequest->status->transitionTo(Completed::class);
        $investmentRequest->refresh();

        $investmentRequest->user->notify(
            new InvestmentRequestCompleted($investmentRequest)
        );
    }

    /**
     * Reject the investment request and notify the requester.
     */
    public function reject(InvestmentRequest $investmentRequest, User $authorizer, string $comments): void
    {
        if (! $investmentRequest->status->equals(PendingDepartment::class)) {
            return;
        }

        $approval = $this->getPendingApprovalFor($investmentRequest, $authorizer);

        if (! $approval) {
            return;
        }

        $approval->update([
            'status' => 'rejected',
            'comments' => $comments,
            'responded_at' => now(),
            'approval_token' => null,
            'approval_token_expires_at' => null,
        ]);

        $investmentRequest->user->notify(
            new InvestmentRequestRejected($investmentRequest, $authorizer, $comments)
        );
    }

    /**
     * Get the single authorizer for investment requests from config.
     */
    private function getAuthorizer(): ?User
    {
        $email = config('investment-requests.authorizer_email');

        if (! $email) {
            return null;
        }

        return User::where('email', $email)->first();
    }

    private function getPendingApprovalFor(InvestmentRequest $investmentRequest, User $authorizer): ?InvestmentRequestApproval
    {
        return $investmentRequest->approvals()
            ->where('user_id', $authorizer->id)
            ->where('status', 'pending')
            ->latest()
            ->first();
    }
}
