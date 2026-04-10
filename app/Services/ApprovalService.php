<?php

namespace App\Services;

use App\Models\Department;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestApproval;
use App\Models\User;
use App\Notifications\PaymentRequestApproved;
use App\Notifications\PaymentRequestCompleted;
use App\Notifications\PaymentRequestCreated;
use App\Notifications\PaymentRequestLevel2Rejected;
use App\Notifications\PaymentRequestRejected;
use App\States\PaymentRequest\Completed;
use App\States\PaymentRequest\PendingAdministration;
use App\States\PaymentRequest\PendingDepartment;
use App\States\PaymentRequest\PendingTreasury;
use Illuminate\Support\Str;

class ApprovalService
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
    public function createApprovals(PaymentRequest $paymentRequest): void
    {
        $department = $paymentRequest->department;

        if (! $department) {
            return;
        }

        $authorizer = $department->authorizerLevel1;

        if (! $authorizer) {
            return;
        }

        $approval = PaymentRequestApproval::create([
            'payment_request_id' => $paymentRequest->id,
            'user_id' => $authorizer->id,
            'stage' => 'department',
            'level' => 1,
            'status' => 'pending',
            'approval_token' => Str::uuid()->toString(),
            'approval_token_expires_at' => now()->addHours(48),
        ]);

        $authorizer->notify(new PaymentRequestCreated($paymentRequest, $approval->approval_token));
    }

    /**
     * Record the current stage's approval, handle level progression within stage,
     * then transition to next state when all levels are approved.
     *
     * @param  array<string, mixed>  $data
     */
    public function approve(PaymentRequest $paymentRequest, User $authorizer, array $data = []): void
    {
        $currentStage = $this->getCurrentStage($paymentRequest);

        if (! $currentStage) {
            return;
        }

        $approval = $this->getPendingApprovalFor($paymentRequest, $authorizer, $currentStage['stage']);

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
            $paymentRequest->update($sapFields);
        }

        $department = $this->getDepartmentForStage($paymentRequest, $currentStage);

        // Level 1 approved: check if Level 2 exists
        if ($approval->level === 1 && $department && $department->authorizer_level_2_id) {
            $this->createLevel2Approval($paymentRequest, $department, $currentStage, $authorizer);

            return;
        }

        // Level 2 approved (or Level 1 with no Level 2): transition to next state
        $paymentRequest->status->transitionTo($currentStage['next_state']);
        $paymentRequest->refresh();

        if ($currentStage['next_state'] === Completed::class) {
            $paymentRequest->user->notify(
                new PaymentRequestCompleted($paymentRequest)
            );

            return;
        }

        $this->createNextStageApproval($paymentRequest, $authorizer);
    }

    /**
     * Record rejection. Level 1 notifies requester. Level 2 resets Level 1 and notifies Level 1 authorizer.
     */
    public function reject(PaymentRequest $paymentRequest, User $authorizer, string $comments): void
    {
        $currentStage = $this->getCurrentStage($paymentRequest);

        if (! $currentStage) {
            return;
        }

        $approval = $this->getPendingApprovalFor($paymentRequest, $authorizer, $currentStage['stage']);

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
            $this->resetLevel1AfterLevel2Rejection($paymentRequest, $currentStage, $authorizer, $comments);

            return;
        }

        // Level 1 rejection: notify the requester
        $paymentRequest->user->notify(
            new PaymentRequestRejected($paymentRequest, $authorizer, $comments)
        );
    }

    /**
     * Get the current pipeline stage based on the payment request status.
     *
     * @return array{stage: string, state: class-string, next_state: class-string|null, department_name: string|null}|null
     */
    private function getCurrentStage(PaymentRequest $paymentRequest): ?array
    {
        foreach (self::PIPELINE as $stage) {
            if ($paymentRequest->status->equals($stage['state'])) {
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
    private function createLevel2Approval(PaymentRequest $paymentRequest, Department $department, array $currentStage, User $previousApprover): void
    {
        $authorizer = $department->authorizerLevel2;

        if (! $authorizer) {
            return;
        }

        $approval = PaymentRequestApproval::create([
            'payment_request_id' => $paymentRequest->id,
            'user_id' => $authorizer->id,
            'stage' => $currentStage['stage'],
            'level' => 2,
            'status' => 'pending',
            'approval_token' => Str::uuid()->toString(),
            'approval_token_expires_at' => now()->addHours(48),
        ]);

        $authorizer->notify(
            new PaymentRequestApproved($paymentRequest, $previousApprover, $approval->approval_token)
        );
    }

    /**
     * Create the approval record for the next stage in the pipeline (Level 1) and notify the authorizer.
     */
    private function createNextStageApproval(PaymentRequest $paymentRequest, User $previousApprover): void
    {
        $currentStage = $this->getCurrentStage($paymentRequest);

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

        $approval = PaymentRequestApproval::create([
            'payment_request_id' => $paymentRequest->id,
            'user_id' => $authorizer->id,
            'stage' => $currentStage['stage'],
            'level' => 1,
            'status' => 'pending',
            'approval_token' => Str::uuid()->toString(),
            'approval_token_expires_at' => now()->addHours(48),
        ]);

        $authorizer->notify(
            new PaymentRequestApproved($paymentRequest, $previousApprover, $approval->approval_token)
        );
    }

    /**
     * When Level 2 rejects, reset Level 1 to pending with a new token and notify Level 1 authorizer.
     *
     * @param  array{stage: string, state: class-string, next_state: class-string|null, department_name: string|null}  $currentStage
     */
    private function resetLevel1AfterLevel2Rejection(PaymentRequest $paymentRequest, array $currentStage, User $level2Rejector, string $comments): void
    {
        $department = $this->getDepartmentForStage($paymentRequest, $currentStage);

        if (! $department) {
            return;
        }

        $level1Authorizer = $department->authorizerLevel1;

        if (! $level1Authorizer) {
            return;
        }

        // Create a new Level 1 pending approval record
        $newApproval = PaymentRequestApproval::create([
            'payment_request_id' => $paymentRequest->id,
            'user_id' => $level1Authorizer->id,
            'stage' => $currentStage['stage'],
            'level' => 1,
            'status' => 'pending',
            'approval_token' => Str::uuid()->toString(),
            'approval_token_expires_at' => now()->addHours(48),
        ]);

        // Notify Level 1 authorizer about the Level 2 rejection
        $level1Authorizer->notify(
            new PaymentRequestLevel2Rejected($paymentRequest, $level2Rejector, $comments, $newApproval->approval_token)
        );

        // Also notify the requester
        $paymentRequest->user->notify(
            new PaymentRequestRejected($paymentRequest, $level2Rejector, $comments)
        );
    }

    /**
     * Get the department responsible for the given stage.
     *
     * @param  array{stage: string, state: class-string, next_state: class-string|null, department_name: string|null}  $stage
     */
    private function getDepartmentForStage(PaymentRequest $paymentRequest, array $stage): ?Department
    {
        if ($stage['department_name'] === null) {
            return $paymentRequest->department;
        }

        return Department::where('name', $stage['department_name'])->first();
    }

    private function getPendingApprovalFor(PaymentRequest $paymentRequest, User $authorizer, string $stage): ?PaymentRequestApproval
    {
        return $paymentRequest->approvals()
            ->where('user_id', $authorizer->id)
            ->where('stage', $stage)
            ->where('status', 'pending')
            ->latest()
            ->first();
    }
}
