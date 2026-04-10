<?php

use App\Models\Department;
use App\Models\InvestmentRequest;
use App\Models\User;
use App\Notifications\InvestmentRequestApproved;
use App\Notifications\InvestmentRequestCompleted;
use App\Notifications\InvestmentRequestCreated;
use App\Notifications\InvestmentRequestLevel2Rejected;
use App\Notifications\InvestmentRequestRejected;
use App\Services\InvestmentApprovalService;
use App\States\InvestmentRequest\Completed;
use App\States\InvestmentRequest\PendingAdministration;
use App\States\InvestmentRequest\PendingDepartment;
use App\States\InvestmentRequest\PendingTreasury;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();

    $this->department = Department::factory()->create();
    $this->adminDepartment = Department::factory()->create(['name' => 'ADMINISTRACIÓN']);
    $this->treasuryDepartment = Department::factory()->create(['name' => 'TESORERÍA']);

    $this->requester = User::factory()->create([
        'department_id' => $this->department->id,
    ]);

    $this->deptAuthorizer = User::factory()->create();
    $this->department->update(['authorizer_level_1_id' => $this->deptAuthorizer->id]);

    $this->adminAuthorizer = User::factory()->create();
    $this->adminDepartment->update(['authorizer_level_1_id' => $this->adminAuthorizer->id]);

    $this->treasuryAuthorizer = User::factory()->create();
    $this->treasuryDepartment->update(['authorizer_level_1_id' => $this->treasuryAuthorizer->id]);

    $this->service = app(InvestmentApprovalService::class);
});

it('creates a department approval record on investment request creation', function () {
    $investmentRequest = InvestmentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
    ]);

    $this->service->createApprovals($investmentRequest);

    expect($investmentRequest->approvals)->toHaveCount(1);
    expect($investmentRequest->approvals->first()->user_id)->toBe($this->deptAuthorizer->id);
    expect($investmentRequest->approvals->first()->stage)->toBe('department');
    expect($investmentRequest->approvals->first()->status)->toBe('pending');
});

it('notifies the department authorizer when an investment request is created', function () {
    $investmentRequest = InvestmentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
    ]);

    $this->service->createApprovals($investmentRequest);

    Notification::assertSentTo($this->deptAuthorizer, InvestmentRequestCreated::class);
});

it('transitions from pending_department to pending_administration on department approval', function () {
    $investmentRequest = InvestmentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
        'status' => PendingDepartment::$name,
    ]);

    $this->service->createApprovals($investmentRequest);
    $this->service->approve($investmentRequest, $this->deptAuthorizer);

    $investmentRequest->refresh();

    expect($investmentRequest->status)->toBeInstanceOf(PendingAdministration::class);
    expect($investmentRequest->approvals()->where('stage', 'department')->first()->status)->toBe('approved');
});

it('creates an administration approval record after department approval', function () {
    $investmentRequest = InvestmentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
        'status' => PendingDepartment::$name,
    ]);

    $this->service->createApprovals($investmentRequest);
    $this->service->approve($investmentRequest, $this->deptAuthorizer);

    $investmentRequest->refresh();

    $adminApproval = $investmentRequest->approvals()->where('stage', 'administration')->first();
    expect($adminApproval)->not->toBeNull();
    expect($adminApproval->user_id)->toBe($this->adminAuthorizer->id);
    expect($adminApproval->status)->toBe('pending');
});

it('notifies the administration authorizer after department approval', function () {
    $investmentRequest = InvestmentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
        'status' => PendingDepartment::$name,
    ]);

    $this->service->createApprovals($investmentRequest);
    $this->service->approve($investmentRequest, $this->deptAuthorizer);

    Notification::assertSentTo($this->adminAuthorizer, InvestmentRequestApproved::class);
});

it('transitions from pending_administration to pending_treasury on administration approval', function () {
    $investmentRequest = InvestmentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
        'status' => PendingAdministration::$name,
    ]);

    $investmentRequest->approvals()->create([
        'user_id' => $this->adminAuthorizer->id,
        'stage' => 'administration',
        'status' => 'pending',
    ]);

    $this->service->approve($investmentRequest, $this->adminAuthorizer);

    $investmentRequest->refresh();

    expect($investmentRequest->status)->toBeInstanceOf(PendingTreasury::class);
});

it('creates a treasury approval record after administration approval', function () {
    $investmentRequest = InvestmentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
        'status' => PendingAdministration::$name,
    ]);

    $investmentRequest->approvals()->create([
        'user_id' => $this->adminAuthorizer->id,
        'stage' => 'administration',
        'status' => 'pending',
    ]);

    $this->service->approve($investmentRequest, $this->adminAuthorizer);

    $investmentRequest->refresh();

    $treasuryApproval = $investmentRequest->approvals()->where('stage', 'treasury')->first();
    expect($treasuryApproval)->not->toBeNull();
    expect($treasuryApproval->user_id)->toBe($this->treasuryAuthorizer->id);
    expect($treasuryApproval->status)->toBe('pending');
});

it('notifies the treasury authorizer after administration approval', function () {
    $investmentRequest = InvestmentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
        'status' => PendingAdministration::$name,
    ]);

    $investmentRequest->approvals()->create([
        'user_id' => $this->adminAuthorizer->id,
        'stage' => 'administration',
        'status' => 'pending',
    ]);

    $this->service->approve($investmentRequest, $this->adminAuthorizer);

    Notification::assertSentTo($this->treasuryAuthorizer, InvestmentRequestApproved::class);
});

it('transitions from pending_treasury to completed on treasury approval', function () {
    $investmentRequest = InvestmentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
        'status' => PendingTreasury::$name,
    ]);

    $investmentRequest->approvals()->create([
        'user_id' => $this->treasuryAuthorizer->id,
        'stage' => 'treasury',
        'status' => 'pending',
    ]);

    $this->service->approve($investmentRequest, $this->treasuryAuthorizer);

    $investmentRequest->refresh();

    expect($investmentRequest->status)->toBeInstanceOf(Completed::class);
});

it('notifies the requester when the pipeline completes', function () {
    $investmentRequest = InvestmentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
        'status' => PendingTreasury::$name,
    ]);

    $investmentRequest->approvals()->create([
        'user_id' => $this->treasuryAuthorizer->id,
        'stage' => 'treasury',
        'status' => 'pending',
    ]);

    $this->service->approve($investmentRequest, $this->treasuryAuthorizer);

    Notification::assertSentTo($this->requester, InvestmentRequestCompleted::class);
});

it('completes the full pipeline: department → administration → treasury → completed', function () {
    $investmentRequest = InvestmentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
        'status' => PendingDepartment::$name,
    ]);

    $this->service->createApprovals($investmentRequest);

    $this->service->approve($investmentRequest, $this->deptAuthorizer);
    $investmentRequest->refresh();
    expect($investmentRequest->status)->toBeInstanceOf(PendingAdministration::class);

    $this->service->approve($investmentRequest, $this->adminAuthorizer);
    $investmentRequest->refresh();
    expect($investmentRequest->status)->toBeInstanceOf(PendingTreasury::class);

    $this->service->approve($investmentRequest, $this->treasuryAuthorizer);
    $investmentRequest->refresh();
    expect($investmentRequest->status)->toBeInstanceOf(Completed::class);

    expect($investmentRequest->approvals)->toHaveCount(3);
});

it('does not change status on rejection and saves notes', function () {
    $investmentRequest = InvestmentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
        'status' => PendingDepartment::$name,
    ]);

    $this->service->createApprovals($investmentRequest);
    $this->service->reject($investmentRequest, $this->deptAuthorizer, 'No apruebo esta inversión');

    $investmentRequest->refresh();

    expect($investmentRequest->status)->toBeInstanceOf(PendingDepartment::class);

    $approval = $investmentRequest->approvals()->where('user_id', $this->deptAuthorizer->id)->first();
    expect($approval->status)->toBe('rejected');
    expect($approval->comments)->toBe('No apruebo esta inversión');
});

it('notifies the requester on rejection', function () {
    $investmentRequest = InvestmentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
        'status' => PendingDepartment::$name,
    ]);

    $this->service->createApprovals($investmentRequest);
    $this->service->reject($investmentRequest, $this->deptAuthorizer, 'Motivo de rechazo');

    Notification::assertSentTo($this->requester, InvestmentRequestRejected::class);
});

it('does not change status on rejection at administration stage', function () {
    $investmentRequest = InvestmentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
        'status' => PendingAdministration::$name,
    ]);

    $investmentRequest->approvals()->create([
        'user_id' => $this->adminAuthorizer->id,
        'stage' => 'administration',
        'status' => 'pending',
    ]);

    $this->service->reject($investmentRequest, $this->adminAuthorizer, 'Faltan documentos');

    $investmentRequest->refresh();

    expect($investmentRequest->status)->toBeInstanceOf(PendingAdministration::class);

    $approval = $investmentRequest->approvals()->where('stage', 'administration')->first();
    expect($approval->status)->toBe('rejected');
    expect($approval->comments)->toBe('Faltan documentos');
});

it('does not change status on rejection at treasury stage', function () {
    $investmentRequest = InvestmentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
        'status' => PendingTreasury::$name,
    ]);

    $investmentRequest->approvals()->create([
        'user_id' => $this->treasuryAuthorizer->id,
        'stage' => 'treasury',
        'status' => 'pending',
    ]);

    $this->service->reject($investmentRequest, $this->treasuryAuthorizer, 'Sin presupuesto');

    $investmentRequest->refresh();

    expect($investmentRequest->status)->toBeInstanceOf(PendingTreasury::class);
});

it('queues all notifications for background processing', function (string $notificationClass) {
    expect(is_a($notificationClass, ShouldQueue::class, true))->toBeTrue();
})->with([
    'created' => InvestmentRequestCreated::class,
    'approved' => InvestmentRequestApproved::class,
    'completed' => InvestmentRequestCompleted::class,
    'rejected' => InvestmentRequestRejected::class,
    'level2_rejected' => InvestmentRequestLevel2Rejected::class,
]);

it('handles level 2 approval flow within a stage', function () {
    $deptLevel2 = User::factory()->create();
    $this->department->update(['authorizer_level_2_id' => $deptLevel2->id]);

    $investmentRequest = InvestmentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
        'status' => PendingDepartment::$name,
    ]);

    $this->service->createApprovals($investmentRequest);

    // Level 1 approves — should NOT transition yet
    $this->service->approve($investmentRequest, $this->deptAuthorizer);
    $investmentRequest->refresh();
    expect($investmentRequest->status)->toBeInstanceOf(PendingDepartment::class);

    // Level 2 should have been notified
    Notification::assertSentTo($deptLevel2, InvestmentRequestApproved::class);

    // Level 2 approves — NOW transitions
    $this->service->approve($investmentRequest, $deptLevel2);
    $investmentRequest->refresh();
    expect($investmentRequest->status)->toBeInstanceOf(PendingAdministration::class);
});

it('resets level 1 when level 2 rejects', function () {
    $deptLevel2 = User::factory()->create();
    $this->department->update(['authorizer_level_2_id' => $deptLevel2->id]);

    $investmentRequest = InvestmentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
        'status' => PendingDepartment::$name,
    ]);

    $this->service->createApprovals($investmentRequest);
    $this->service->approve($investmentRequest, $this->deptAuthorizer);

    // Level 2 rejects
    $this->service->reject($investmentRequest, $deptLevel2, 'Necesita revisión');
    $investmentRequest->refresh();

    // Status should remain the same
    expect($investmentRequest->status)->toBeInstanceOf(PendingDepartment::class);

    // Level 1 should have a new pending approval
    $newLevel1 = $investmentRequest->approvals()
        ->where('user_id', $this->deptAuthorizer->id)
        ->where('status', 'pending')
        ->latest()
        ->first();
    expect($newLevel1)->not->toBeNull();

    // Level 1 authorizer notified about the rejection
    Notification::assertSentTo($this->deptAuthorizer, InvestmentRequestLevel2Rejected::class);
});
