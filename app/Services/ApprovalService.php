<?php

namespace App\Services;

use App\Models\Department;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestApproval;
use App\Models\User;
use App\Notifications\PaymentRequestApproved;
use App\Notifications\PaymentRequestCompleted;
use App\Notifications\PaymentRequestCreated;
use App\Notifications\PaymentRequestRejected;
use App\States\PaymentRequest\Completed;
use App\States\PaymentRequest\PendingAdministration;
use App\States\PaymentRequest\PendingDepartment;
use App\States\PaymentRequest\PendingTreasury;

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
            'department_name' => 'Administración',
        ],
        [
            'stage' => 'treasury',
            'state' => PendingTreasury::class,
            'next_state' => Completed::class,
            'department_name' => 'Tesorería',
        ],
    ];

    /**
     * Create the first approval record (department stage) and notify the authorizer.
     */
    public function createApprovals(PaymentRequest $paymentRequest): void
    {
        $department = $paymentRequest->department;

        if (! $department) {
            return;
        }

        $authorizer = $department->authorizers()->first();

        if (! $authorizer) {
            return;
        }

        PaymentRequestApproval::updateOrCreate(
            [
                'payment_request_id' => $paymentRequest->id,
                'user_id' => $authorizer->id,
                'stage' => 'department',
            ],
            [
                'status' => 'pending',
                'comments' => null,
                'responded_at' => null,
            ]
        );

        $authorizer->notify(new PaymentRequestCreated($paymentRequest));
    }

    /**
     * Record the current stage's approval, transition to next state, and create next approval.
     */
    public function approve(PaymentRequest $paymentRequest, User $authorizer): void
    {
        $currentStage = $this->getCurrentStage($paymentRequest);

        if (! $currentStage) {
            return;
        }

        $approval = $this->getApprovalFor($paymentRequest, $authorizer, $currentStage['stage']);

        if (! $approval) {
            return;
        }

        $approval->update([
            'status' => 'approved',
            'responded_at' => now(),
        ]);

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
     * Record rejection: status does NOT change, notes are saved, requester is notified.
     */
    public function reject(PaymentRequest $paymentRequest, User $authorizer, string $comments): void
    {
        $currentStage = $this->getCurrentStage($paymentRequest);

        if (! $currentStage) {
            return;
        }

        $approval = $this->getApprovalFor($paymentRequest, $authorizer, $currentStage['stage']);

        if (! $approval) {
            return;
        }

        $approval->update([
            'status' => 'rejected',
            'comments' => $comments,
            'responded_at' => now(),
        ]);

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
     * Create the approval record for the next stage in the pipeline and notify the authorizer.
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

        $authorizer = $department->authorizers()->first();

        if (! $authorizer) {
            return;
        }

        PaymentRequestApproval::updateOrCreate(
            [
                'payment_request_id' => $paymentRequest->id,
                'user_id' => $authorizer->id,
                'stage' => $currentStage['stage'],
            ],
            [
                'status' => 'pending',
                'comments' => null,
                'responded_at' => null,
            ]
        );

        $authorizer->notify(
            new PaymentRequestApproved($paymentRequest, $previousApprover)
        );
    }

    private function getApprovalFor(PaymentRequest $paymentRequest, User $authorizer, string $stage): ?PaymentRequestApproval
    {
        return $paymentRequest->approvals()
            ->where('user_id', $authorizer->id)
            ->where('stage', $stage)
            ->first();
    }
}
