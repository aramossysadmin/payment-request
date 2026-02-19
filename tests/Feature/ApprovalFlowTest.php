<?php

use App\Models\Department;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Notifications\PaymentRequestApproved;
use App\Notifications\PaymentRequestCompleted;
use App\Notifications\PaymentRequestCreated;
use App\Notifications\PaymentRequestRejected;
use App\Services\ApprovalService;
use App\States\PaymentRequest\Completed;
use App\States\PaymentRequest\PendingAdministration;
use App\States\PaymentRequest\PendingDepartment;
use App\States\PaymentRequest\PendingTreasury;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();

    $this->department = Department::factory()->create();
    $this->adminDepartment = Department::factory()->create(['name' => 'Administración']);
    $this->treasuryDepartment = Department::factory()->create(['name' => 'Tesorería']);

    $this->requester = User::factory()->create([
        'department_id' => $this->department->id,
    ]);

    $this->deptAuthorizer = User::factory()->create();
    $this->department->authorizers()->attach($this->deptAuthorizer);

    $this->adminAuthorizer = User::factory()->create();
    $this->adminDepartment->authorizers()->attach($this->adminAuthorizer);

    $this->treasuryAuthorizer = User::factory()->create();
    $this->treasuryDepartment->authorizers()->attach($this->treasuryAuthorizer);

    $this->service = app(ApprovalService::class);
});

it('creates a department approval record on payment request creation', function () {
    $paymentRequest = PaymentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
    ]);

    $this->service->createApprovals($paymentRequest);

    expect($paymentRequest->approvals)->toHaveCount(1);
    expect($paymentRequest->approvals->first()->user_id)->toBe($this->deptAuthorizer->id);
    expect($paymentRequest->approvals->first()->stage)->toBe('department');
    expect($paymentRequest->approvals->first()->status)->toBe('pending');
});

it('notifies the department authorizer when a payment request is created', function () {
    $paymentRequest = PaymentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
    ]);

    $this->service->createApprovals($paymentRequest);

    Notification::assertSentTo($this->deptAuthorizer, PaymentRequestCreated::class);
});

it('transitions from pending_department to pending_administration on department approval', function () {
    $paymentRequest = PaymentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
        'status' => PendingDepartment::$name,
    ]);

    $this->service->createApprovals($paymentRequest);
    $this->service->approve($paymentRequest, $this->deptAuthorizer);

    $paymentRequest->refresh();

    expect($paymentRequest->status)->toBeInstanceOf(PendingAdministration::class);
    expect($paymentRequest->approvals()->where('stage', 'department')->first()->status)->toBe('approved');
});

it('creates an administration approval record after department approval', function () {
    $paymentRequest = PaymentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
        'status' => PendingDepartment::$name,
    ]);

    $this->service->createApprovals($paymentRequest);
    $this->service->approve($paymentRequest, $this->deptAuthorizer);

    $paymentRequest->refresh();

    $adminApproval = $paymentRequest->approvals()->where('stage', 'administration')->first();
    expect($adminApproval)->not->toBeNull();
    expect($adminApproval->user_id)->toBe($this->adminAuthorizer->id);
    expect($adminApproval->status)->toBe('pending');
});

it('notifies the administration authorizer after department approval', function () {
    $paymentRequest = PaymentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
        'status' => PendingDepartment::$name,
    ]);

    $this->service->createApprovals($paymentRequest);
    $this->service->approve($paymentRequest, $this->deptAuthorizer);

    Notification::assertSentTo($this->adminAuthorizer, PaymentRequestApproved::class);
});

it('transitions from pending_administration to pending_treasury on administration approval', function () {
    $paymentRequest = PaymentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
        'status' => PendingAdministration::$name,
    ]);

    $paymentRequest->approvals()->create([
        'user_id' => $this->adminAuthorizer->id,
        'stage' => 'administration',
        'status' => 'pending',
    ]);

    $this->service->approve($paymentRequest, $this->adminAuthorizer);

    $paymentRequest->refresh();

    expect($paymentRequest->status)->toBeInstanceOf(PendingTreasury::class);
});

it('creates a treasury approval record after administration approval', function () {
    $paymentRequest = PaymentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
        'status' => PendingAdministration::$name,
    ]);

    $paymentRequest->approvals()->create([
        'user_id' => $this->adminAuthorizer->id,
        'stage' => 'administration',
        'status' => 'pending',
    ]);

    $this->service->approve($paymentRequest, $this->adminAuthorizer);

    $paymentRequest->refresh();

    $treasuryApproval = $paymentRequest->approvals()->where('stage', 'treasury')->first();
    expect($treasuryApproval)->not->toBeNull();
    expect($treasuryApproval->user_id)->toBe($this->treasuryAuthorizer->id);
    expect($treasuryApproval->status)->toBe('pending');
});

it('notifies the treasury authorizer after administration approval', function () {
    $paymentRequest = PaymentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
        'status' => PendingAdministration::$name,
    ]);

    $paymentRequest->approvals()->create([
        'user_id' => $this->adminAuthorizer->id,
        'stage' => 'administration',
        'status' => 'pending',
    ]);

    $this->service->approve($paymentRequest, $this->adminAuthorizer);

    Notification::assertSentTo($this->treasuryAuthorizer, PaymentRequestApproved::class);
});

it('transitions from pending_treasury to completed on treasury approval', function () {
    $paymentRequest = PaymentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
        'status' => PendingTreasury::$name,
    ]);

    $paymentRequest->approvals()->create([
        'user_id' => $this->treasuryAuthorizer->id,
        'stage' => 'treasury',
        'status' => 'pending',
    ]);

    $this->service->approve($paymentRequest, $this->treasuryAuthorizer);

    $paymentRequest->refresh();

    expect($paymentRequest->status)->toBeInstanceOf(Completed::class);
});

it('notifies the requester when the pipeline completes', function () {
    $paymentRequest = PaymentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
        'status' => PendingTreasury::$name,
    ]);

    $paymentRequest->approvals()->create([
        'user_id' => $this->treasuryAuthorizer->id,
        'stage' => 'treasury',
        'status' => 'pending',
    ]);

    $this->service->approve($paymentRequest, $this->treasuryAuthorizer);

    Notification::assertSentTo($this->requester, PaymentRequestCompleted::class);
});

it('completes the full pipeline: department → administration → treasury → completed', function () {
    $paymentRequest = PaymentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
        'status' => PendingDepartment::$name,
    ]);

    $this->service->createApprovals($paymentRequest);

    $this->service->approve($paymentRequest, $this->deptAuthorizer);
    $paymentRequest->refresh();
    expect($paymentRequest->status)->toBeInstanceOf(PendingAdministration::class);

    $this->service->approve($paymentRequest, $this->adminAuthorizer);
    $paymentRequest->refresh();
    expect($paymentRequest->status)->toBeInstanceOf(PendingTreasury::class);

    $this->service->approve($paymentRequest, $this->treasuryAuthorizer);
    $paymentRequest->refresh();
    expect($paymentRequest->status)->toBeInstanceOf(Completed::class);

    expect($paymentRequest->approvals)->toHaveCount(3);
});

it('does not change status on rejection and saves notes', function () {
    $paymentRequest = PaymentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
        'status' => PendingDepartment::$name,
    ]);

    $this->service->createApprovals($paymentRequest);
    $this->service->reject($paymentRequest, $this->deptAuthorizer, 'No apruebo este gasto');

    $paymentRequest->refresh();

    expect($paymentRequest->status)->toBeInstanceOf(PendingDepartment::class);

    $approval = $paymentRequest->approvals()->where('user_id', $this->deptAuthorizer->id)->first();
    expect($approval->status)->toBe('rejected');
    expect($approval->comments)->toBe('No apruebo este gasto');
});

it('notifies the requester on rejection', function () {
    $paymentRequest = PaymentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
        'status' => PendingDepartment::$name,
    ]);

    $this->service->createApprovals($paymentRequest);
    $this->service->reject($paymentRequest, $this->deptAuthorizer, 'Motivo de rechazo');

    Notification::assertSentTo($this->requester, PaymentRequestRejected::class);
});

it('does not change status on rejection at administration stage', function () {
    $paymentRequest = PaymentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
        'status' => PendingAdministration::$name,
    ]);

    $paymentRequest->approvals()->create([
        'user_id' => $this->adminAuthorizer->id,
        'stage' => 'administration',
        'status' => 'pending',
    ]);

    $this->service->reject($paymentRequest, $this->adminAuthorizer, 'Faltan documentos');

    $paymentRequest->refresh();

    expect($paymentRequest->status)->toBeInstanceOf(PendingAdministration::class);

    $approval = $paymentRequest->approvals()->where('stage', 'administration')->first();
    expect($approval->status)->toBe('rejected');
    expect($approval->comments)->toBe('Faltan documentos');
});

it('does not change status on rejection at treasury stage', function () {
    $paymentRequest = PaymentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'department_id' => $this->department->id,
        'status' => PendingTreasury::$name,
    ]);

    $paymentRequest->approvals()->create([
        'user_id' => $this->treasuryAuthorizer->id,
        'stage' => 'treasury',
        'status' => 'pending',
    ]);

    $this->service->reject($paymentRequest, $this->treasuryAuthorizer, 'Sin presupuesto');

    $paymentRequest->refresh();

    expect($paymentRequest->status)->toBeInstanceOf(PendingTreasury::class);
});

it('queues all notifications for background processing', function (string $notificationClass) {
    expect(is_a($notificationClass, ShouldQueue::class, true))->toBeTrue();
})->with([
    'created' => PaymentRequestCreated::class,
    'approved' => PaymentRequestApproved::class,
    'completed' => PaymentRequestCompleted::class,
    'rejected' => PaymentRequestRejected::class,
]);

it('auto-generates a folio number on creation', function () {
    $paymentRequest = PaymentRequest::factory()->create();

    expect($paymentRequest->folio_number)->not->toBeNull();
    expect($paymentRequest->folio_number)->toBeGreaterThan(0);
});

it('generates unique sequential folio numbers', function () {
    $first = PaymentRequest::factory()->create(['folio_number' => null]);
    $second = PaymentRequest::factory()->create(['folio_number' => null]);

    expect($second->folio_number)->toBe($first->folio_number + 1);
});
