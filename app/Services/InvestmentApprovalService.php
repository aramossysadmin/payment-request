<?php

namespace App\Services;

use App\Models\Department;
use App\Models\InvestmentRequest;
use App\Models\InvestmentRequestApproval;
use App\Models\User;
use App\Notifications\InvestmentRequestApproved;
use App\Notifications\InvestmentRequestCompleted;
use App\Notifications\InvestmentRequestCreated;
use App\Notifications\InvestmentRequestLevel2Rejected;
use App\Notifications\InvestmentRequestRejected;
use App\States\InvestmentRequest\Completed;
use App\States\InvestmentRequest\PendingAdministration;
use App\States\InvestmentRequest\PendingDepartment;
use App\States\InvestmentRequest\PendingTreasury;
use Illuminate\Support\Str;

class InvestmentApprovalService
{
    /** @var array<int, array{stage: string, state: class-string, next_state: class-string|null, department_name: string|null}> */
    private const PIPELINE = [
        [
            'stage' => 'department',
            'state' => PendingDepartment::class,
            'next_state' => PendingAdministration::class,
            'department_name' => null,
        ],
        [
            'stage' => 'administration',
            'state' => PendingAdministration::class,
            'next_state' => PendingTreasury::class,
            'department_name' => 'ADMINISTRACIÓN',
        ],
        [
            'stage' => 'treasury',
            'state' => PendingTreasury::class,
            'next_state' => Completed::class,
            'department_name' => 'TESORERÍA',
        ],
    ];

    /**
     * Create the first approval record (department stage, level 1) and notify the authorizer.
     */
    public function createApprovals(InvestmentRequest $investmentRequest): void
    {
        $department = $investmentRequest->department;

        if (! $department) {
            return;
        }

        $authorizer = $department->authorizerLevel1;

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
     * Record the current stage's approval, handle level progression within stage,
     * then transition to next state when all levels are approved.
     *
     * @param  array<string, mixed>  $data
     */
    public function approve(InvestmentRequest $investmentRequest, User $authorizer, array $data = []): void
    {
        $currentStage = $this->getCurrentStage($investmentRequest);

        if (! $currentStage) {
            return;
        }

        $approval = $this->getPendingApprovalFor($investmentRequest, $authorizer, $currentStage['stage']);

        if (! $approval) {
            return;
        }

        $approval->update([
            'status' => 'approved',
            'responded_at' => now(),
            'approval_token' => null,
            'approval_token_expires_at' => null,
        ]);

        $sapFields = array_intersect_key($data, array_flip(['number_purchase_invoices', 'number_vendor_payments']));

        if (! empty($sapFields)) {
            $investmentRequest->update($sapFields);
        }

        $department = $this->getDepartmentForStage($investmentRequest, $currentStage);

        // Level 1 approved: check if Level 2 exists
        if ($approval->level === 1 && $department && $department->authorizer_level_2_id) {
            $this->createLevel2Approval($investmentRequest, $department, $currentStage, $authorizer);

            return;
        }

        // Level 2 approved (or Level 1 with no Level 2): transition to next state
        $investmentRequest->status->transitionTo($currentStage['next_state']);
        $investmentRequest->refresh();

        if ($currentStage['next_state'] === Completed::class) {
            $investmentRequest->user->notify(
                new InvestmentRequestCompleted($investmentRequest)
            );

            return;
        }

        $this->createNextStageApproval($investmentRequest, $authorizer);
    }

    /**
     * Record rejection. Level 1 notifies requester. Level 2 resets Level 1 and notifies Level 1 authorizer.
     */
    public function reject(InvestmentRequest $investmentRequest, User $authorizer, string $comments): void
    {
        $currentStage = $this->getCurrentStage($investmentRequest);

        if (! $currentStage) {
            return;
        }

        $approval = $this->getPendingApprovalFor($investmentRequest, $authorizer, $currentStage['stage']);

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

        if ($approval->level === 2) {
            $this->resetLevel1AfterLevel2Rejection($investmentRequest, $currentStage, $authorizer, $comments);

            return;
        }

        // Level 1 rejection: notify the requester
        $investmentRequest->user->notify(
            new InvestmentRequestRejected($investmentRequest, $authorizer, $comments)
        );
    }

    /**
     * Get the current pipeline stage based on the investment request status.
     *
     * @return array{stage: string, state: class-string, next_state: class-string|null, department_name: string|null}|null
     */
    private function getCurrentStage(InvestmentRequest $investmentRequest): ?array
    {
        foreach (self::PIPELINE as $stage) {
            if ($investmentRequest->status->equals($stage['state'])) {
                return $stage;
            }
        }

        return null;
    }

    /**
     * Create a Level 2 approval within the same stage.
     *
     * @param  array{stage: string, state: class-string, next_state: class-string|null, department_name: string|null}  $currentStage
     */
    private function createLevel2Approval(InvestmentRequest $investmentRequest, Department $department, array $currentStage, User $previousApprover): void
    {
        $authorizer = $department->authorizerLevel2;

        if (! $authorizer) {
            return;
        }

        $approval = InvestmentRequestApproval::create([
            'investment_request_id' => $investmentRequest->id,
            'user_id' => $authorizer->id,
            'stage' => $currentStage['stage'],
            'level' => 2,
            'status' => 'pending',
            'approval_token' => Str::uuid()->toString(),
            'approval_token_expires_at' => now()->addHours(48),
        ]);

        $authorizer->notify(
            new InvestmentRequestApproved($investmentRequest, $previousApprover, $approval->approval_token)
        );
    }

    /**
     * Create the approval record for the next stage in the pipeline (Level 1) and notify the authorizer.
     */
    private function createNextStageApproval(InvestmentRequest $investmentRequest, User $previousApprover): void
    {
        $currentStage = $this->getCurrentStage($investmentRequest);

        if (! $currentStage) {
            return;
        }

        $department = Department::where('name', $currentStage['department_name'])->first();

        if (! $department) {
            return;
        }

        $authorizer = $department->authorizerLevel1;

        if (! $authorizer) {
            return;
        }

        $approval = InvestmentRequestApproval::create([
            'investment_request_id' => $investmentRequest->id,
            'user_id' => $authorizer->id,
            'stage' => $currentStage['stage'],
            'level' => 1,
            'status' => 'pending',
            'approval_token' => Str::uuid()->toString(),
            'approval_token_expires_at' => now()->addHours(48),
        ]);

        $authorizer->notify(
            new InvestmentRequestApproved($investmentRequest, $previousApprover, $approval->approval_token)
        );
    }

    /**
     * When Level 2 rejects, reset Level 1 to pending with a new token and notify Level 1 authorizer.
     *
     * @param  array{stage: string, state: class-string, next_state: class-string|null, department_name: string|null}  $currentStage
     */
    private function resetLevel1AfterLevel2Rejection(InvestmentRequest $investmentRequest, array $currentStage, User $level2Rejector, string $comments): void
    {
        $department = $this->getDepartmentForStage($investmentRequest, $currentStage);

        if (! $department) {
            return;
        }

        $level1Authorizer = $department->authorizerLevel1;

        if (! $level1Authorizer) {
            return;
        }

        // Create a new Level 1 pending approval record
        $newApproval = InvestmentRequestApproval::create([
            'investment_request_id' => $investmentRequest->id,
            'user_id' => $level1Authorizer->id,
            'stage' => $currentStage['stage'],
            'level' => 1,
            'status' => 'pending',
            'approval_token' => Str::uuid()->toString(),
            'approval_token_expires_at' => now()->addHours(48),
        ]);

        // Notify Level 1 authorizer about the Level 2 rejection
        $level1Authorizer->notify(
            new InvestmentRequestLevel2Rejected($investmentRequest, $level2Rejector, $comments, $newApproval->approval_token)
        );

        // Also notify the requester
        $investmentRequest->user->notify(
            new InvestmentRequestRejected($investmentRequest, $level2Rejector, $comments)
        );
    }

    /**
     * Get the department responsible for the given stage.
     *
     * @param  array{stage: string, state: class-string, next_state: class-string|null, department_name: string|null}  $stage
     */
    private function getDepartmentForStage(InvestmentRequest $investmentRequest, array $stage): ?Department
    {
        if ($stage['department_name'] === null) {
            return $investmentRequest->department;
        }

        return Department::where('name', $stage['department_name'])->first();
    }

    private function getPendingApprovalFor(InvestmentRequest $investmentRequest, User $authorizer, string $stage): ?InvestmentRequestApproval
    {
        return $investmentRequest->approvals()
            ->where('user_id', $authorizer->id)
            ->where('stage', $stage)
            ->where('status', 'pending')
            ->latest()
            ->first();
    }
}
