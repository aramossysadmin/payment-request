<?php

use App\Models\InvestmentRequest;
use App\Models\User;
use App\Notifications\InvestmentRequestCompleted;
use App\Notifications\InvestmentRequestCreated;
use App\Notifications\InvestmentRequestRejected;
use App\Services\InvestmentApprovalService;
use App\States\InvestmentRequest\Completed;
use App\States\InvestmentRequest\PendingDepartment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();

    $this->authorizer = User::factory()->create([
        'email' => 'victor.setien@grupocosteno.com',
    ]);

    $this->requester = User::factory()->create();

    config(['investment-requests.authorizer_email' => 'victor.setien@grupocosteno.com']);

    $this->service = app(InvestmentApprovalService::class);
});

it('creates a single approval record assigned to the configured authorizer', function () {
    $investmentRequest = InvestmentRequest::factory()->create([
        'user_id' => $this->requester->id,
    ]);

    $this->service->createApprovals($investmentRequest);

    expect($investmentRequest->approvals)->toHaveCount(1);
    expect($investmentRequest->approvals->first())
        ->user_id->toBe($this->authorizer->id)
        ->stage->toBe('department')
        ->level->toBe(1)
        ->status->toBe('pending');
});

it('notifies the authorizer when an investment request is created', function () {
    $investmentRequest = InvestmentRequest::factory()->create([
        'user_id' => $this->requester->id,
    ]);

    $this->service->createApprovals($investmentRequest);

    Notification::assertSentTo($this->authorizer, InvestmentRequestCreated::class);
});

it('generates an approval token with 48-hour expiry', function () {
    $investmentRequest = InvestmentRequest::factory()->create([
        'user_id' => $this->requester->id,
    ]);

    $this->service->createApprovals($investmentRequest);

    $approval = $investmentRequest->approvals->first();
    expect($approval->approval_token)->not->toBeNull();
    expect($approval->approval_token_expires_at)->not->toBeNull();
});

it('transitions directly from pending_department to completed on approval', function () {
    $investmentRequest = InvestmentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'status' => PendingDepartment::$name,
    ]);

    $this->service->createApprovals($investmentRequest);
    $this->service->approve($investmentRequest, $this->authorizer);

    $investmentRequest->refresh();

    expect($investmentRequest->status)->toBeInstanceOf(Completed::class);
    expect($investmentRequest->approvals()->where('status', 'approved')->count())->toBe(1);
});

it('notifies the requester when the request is completed', function () {
    $investmentRequest = InvestmentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'status' => PendingDepartment::$name,
    ]);

    $this->service->createApprovals($investmentRequest);
    $this->service->approve($investmentRequest, $this->authorizer);

    Notification::assertSentTo($this->requester, InvestmentRequestCompleted::class);
});

it('clears the approval token after approval', function () {
    $investmentRequest = InvestmentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'status' => PendingDepartment::$name,
    ]);

    $this->service->createApprovals($investmentRequest);
    $this->service->approve($investmentRequest, $this->authorizer);

    $approval = $investmentRequest->approvals()->first();
    expect($approval->approval_token)->toBeNull();
    expect($approval->approval_token_expires_at)->toBeNull();
});

it('does not change status on rejection and saves comments', function () {
    $investmentRequest = InvestmentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'status' => PendingDepartment::$name,
    ]);

    $this->service->createApprovals($investmentRequest);
    $this->service->reject($investmentRequest, $this->authorizer, 'No apruebo esta inversión');

    $investmentRequest->refresh();

    expect($investmentRequest->status)->toBeInstanceOf(PendingDepartment::class);

    $approval = $investmentRequest->approvals()->first();
    expect($approval->status)->toBe('rejected');
    expect($approval->comments)->toBe('No apruebo esta inversión');
});

it('notifies the requester on rejection', function () {
    $investmentRequest = InvestmentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'status' => PendingDepartment::$name,
    ]);

    $this->service->createApprovals($investmentRequest);
    $this->service->reject($investmentRequest, $this->authorizer, 'Motivo de rechazo');

    Notification::assertSentTo($this->requester, InvestmentRequestRejected::class);
});

it('does not create approvals when authorizer email is not configured', function () {
    config(['investment-requests.authorizer_email' => null]);

    $investmentRequest = InvestmentRequest::factory()->create([
        'user_id' => $this->requester->id,
    ]);

    $this->service->createApprovals($investmentRequest);

    expect($investmentRequest->approvals)->toHaveCount(0);
});

it('does not create approvals when authorizer user does not exist', function () {
    config(['investment-requests.authorizer_email' => 'nonexistent@example.com']);

    $investmentRequest = InvestmentRequest::factory()->create([
        'user_id' => $this->requester->id,
    ]);

    $this->service->createApprovals($investmentRequest);

    expect($investmentRequest->approvals)->toHaveCount(0);
});

it('does not approve if request is not in pending_department status', function () {
    $investmentRequest = InvestmentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'status' => Completed::$name,
    ]);

    $investmentRequest->approvals()->create([
        'user_id' => $this->authorizer->id,
        'stage' => 'department',
        'level' => 1,
        'status' => 'pending',
    ]);

    $this->service->approve($investmentRequest, $this->authorizer);

    $approval = $investmentRequest->approvals()->first();
    expect($approval->status)->toBe('pending');
});

it('does not approve if user has no pending approval', function () {
    $otherUser = User::factory()->create();

    $investmentRequest = InvestmentRequest::factory()->create([
        'user_id' => $this->requester->id,
        'status' => PendingDepartment::$name,
    ]);

    $this->service->createApprovals($investmentRequest);
    $this->service->approve($investmentRequest, $otherUser);

    $investmentRequest->refresh();

    expect($investmentRequest->status)->toBeInstanceOf(PendingDepartment::class);
});

it('queues all notifications for background processing', function (string $notificationClass) {
    expect(is_a($notificationClass, ShouldQueue::class, true))->toBeTrue();
})->with([
    'created' => InvestmentRequestCreated::class,
    'completed' => InvestmentRequestCompleted::class,
    'rejected' => InvestmentRequestRejected::class,
]);
