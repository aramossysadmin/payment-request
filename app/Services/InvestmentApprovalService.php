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
use App\States\InvestmentRequest\Rejected;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InvestmentApprovalService
{
    /**
     * Create the approval record(s) and notify the corresponding approver(s).
     *
     * Precedence:
     *   1. Per-project lock (requires_pm_approval=true) → notify all PMs. Wins
     *      over the global bypass so the feature can be activated per project
     *      from Filament without touching the production .env.
     *   2. Global bypass (require_authorization=false) → autoApprove for
     *      projects without a lock.
     *   3. Default flow → notify the global authorizer_email.
     */
    public function createApprovals(InvestmentRequest $investmentRequest): void
    {
        if ($investmentRequest->project?->requires_pm_approval) {
            $this->createPmApprovals($investmentRequest);

            return;
        }

        if (! config('investment-requests.require_authorization')) {
            $this->autoApprove($investmentRequest);

            return;
        }

        $this->createGlobalAuthorizerApproval($investmentRequest);
    }

    /**
     * Per-project lock flow: create ONE shared approval owned by the first PM
     * and notify every user with the project_manager role. If no PM exists,
     * fall back to the global authorizer to avoid orphan requests.
     */
    private function createPmApprovals(InvestmentRequest $investmentRequest): void
    {
        $pms = User::role('project_manager')->get();

        if ($pms->isEmpty()) {
            Log::warning('Investment request created on locked project but no project_manager user exists; falling back to global authorizer.', [
                'investment_request_id' => $investmentRequest->id,
                'project_id' => $investmentRequest->project_id,
            ]);

            $this->createGlobalAuthorizerApproval($investmentRequest);

            return;
        }

        $primaryPm = $pms->first();

        $approval = InvestmentRequestApproval::create([
            'investment_request_id' => $investmentRequest->id,
            'user_id' => $primaryPm->id,
            'stage' => 'department',
            'level' => 1,
            'status' => 'pending',
        ]);
        $approval->approval_token = Str::uuid()->toString();
        $approval->approval_token_expires_at = now()->addHours(48);
        $approval->save();

        $pms->each(fn (User $pm) => $pm->notify(new InvestmentRequestCreated($investmentRequest, $approval->approval_token)));
    }

    /**
     * Default flow: single approval owned by the global authorizer_email user.
     */
    private function createGlobalAuthorizerApproval(InvestmentRequest $investmentRequest): void
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
        ]);
        $approval->approval_token = Str::uuid()->toString();
        $approval->approval_token_expires_at = now()->addHours(48);
        $approval->save();

        $authorizer->notify(new InvestmentRequestCreated($investmentRequest, $approval->approval_token));
    }

    /**
     * Bypass: auto-approve the request on creation with audit trail.
     */
    private function autoApprove(InvestmentRequest $investmentRequest): void
    {
        InvestmentRequestApproval::create([
            'investment_request_id' => $investmentRequest->id,
            'user_id' => $investmentRequest->user_id,
            'stage' => 'department',
            'level' => 1,
            'status' => 'approved',
            'responded_at' => now(),
            'comments' => 'Aprobación automática al momento de la creación',
        ]);

        $investmentRequest->status->transitionTo(Completed::class);
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

        $approval->status = 'approved';
        $approval->responded_at = now();
        $approval->approval_token = null;
        $approval->approval_token_expires_at = null;
        $approval->save();

        $investmentRequest->status->transitionTo(Completed::class);
        $investmentRequest->refresh();

        $investmentRequest->user->notify(
            new InvestmentRequestCompleted($investmentRequest)
        );
    }

    /**
     * Reject the investment request, transition to Rejected, and notify the requester.
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

        $approval->status = 'rejected';
        $approval->comments = $comments;
        $approval->responded_at = now();
        $approval->approval_token = null;
        $approval->approval_token_expires_at = null;
        $approval->save();

        $investmentRequest->status->transitionTo(Rejected::class);
        $investmentRequest->refresh();

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
