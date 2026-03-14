<?php

use App\Models\Branch;
use App\Models\Currency;
use App\Models\Department;
use App\Models\ExpenseConcept;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestApproval;
use App\Models\User;
use App\Notifications\PaymentRequestCreated;
use App\Services\ApprovalService;
use App\States\PaymentRequest\PendingAdministration;
use App\States\PaymentRequest\PendingDepartment;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->department = Department::factory()->create();
    $this->currency = Currency::factory()->create();
    $this->branch = Branch::factory()->create();
    $this->expenseConcept = ExpenseConcept::factory()->create();

    $this->paymentRequest = PaymentRequest::factory()->create([
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => PendingDepartment::$name,
    ]);

    $this->authorizer = User::factory()->create(['department_id' => $this->department->id]);
});

test('valid token shows approval page with payment request details', function () {
    $approval = PaymentRequestApproval::factory()->withToken()->create([
        'payment_request_id' => $this->paymentRequest->id,
        'user_id' => $this->authorizer->id,
        'stage' => 'department',
    ]);

    $this->get(route('approval.show', $approval->approval_token))
        ->assertOk()
        ->assertSee($this->paymentRequest->folio_number)
        ->assertSee($this->paymentRequest->provider)
        ->assertSee('Autorizar Solicitud')
        ->assertSee('Rechazar Solicitud');
});

test('valid token can approve payment request', function () {
    Department::factory()->create(['name' => 'Administración']);

    $approval = PaymentRequestApproval::factory()->withToken()->create([
        'payment_request_id' => $this->paymentRequest->id,
        'user_id' => $this->authorizer->id,
        'stage' => 'department',
    ]);

    $this->post(route('approval.approve', $approval->approval_token))
        ->assertOk()
        ->assertSee('ha sido autorizada correctamente');

    $this->assertDatabaseHas('payment_request_approvals', [
        'id' => $approval->id,
        'status' => 'approved',
        'approval_token' => null,
    ]);
});

test('valid token can reject payment request with comments', function () {
    $approval = PaymentRequestApproval::factory()->withToken()->create([
        'payment_request_id' => $this->paymentRequest->id,
        'user_id' => $this->authorizer->id,
        'stage' => 'department',
    ]);

    $this->post(route('approval.reject', $approval->approval_token), [
        'comments' => 'No cumple con los requisitos necesarios para aprobación',
    ])
        ->assertOk()
        ->assertSee('ha sido rechazada');

    $this->assertDatabaseHas('payment_request_approvals', [
        'id' => $approval->id,
        'status' => 'rejected',
        'comments' => 'No cumple con los requisitos necesarios para aprobación',
        'approval_token' => null,
    ]);
});

test('reject requires comments with at least 10 characters', function () {
    $approval = PaymentRequestApproval::factory()->withToken()->create([
        'payment_request_id' => $this->paymentRequest->id,
        'user_id' => $this->authorizer->id,
        'stage' => 'department',
    ]);

    $this->post(route('approval.reject', $approval->approval_token), [
        'comments' => 'short',
    ])
        ->assertSessionHasErrors('comments');
});

test('expired token shows error message', function () {
    $approval = PaymentRequestApproval::factory()->withExpiredToken()->create([
        'payment_request_id' => $this->paymentRequest->id,
        'user_id' => $this->authorizer->id,
        'stage' => 'department',
    ]);

    $this->get(route('approval.show', $approval->approval_token))
        ->assertOk()
        ->assertSee('ha expirado');
});

test('expired token cannot approve', function () {
    $approval = PaymentRequestApproval::factory()->withExpiredToken()->create([
        'payment_request_id' => $this->paymentRequest->id,
        'user_id' => $this->authorizer->id,
        'stage' => 'department',
    ]);

    $this->post(route('approval.approve', $approval->approval_token))
        ->assertOk()
        ->assertSee('ha expirado');

    $this->assertDatabaseHas('payment_request_approvals', [
        'id' => $approval->id,
        'status' => 'pending',
    ]);
});

test('invalid token shows error message', function () {
    $this->get(route('approval.show', 'invalid-token'))
        ->assertOk()
        ->assertSee('no es válido');
});

test('already approved request shows error', function () {
    $approval = PaymentRequestApproval::factory()->approved()->withToken()->create([
        'payment_request_id' => $this->paymentRequest->id,
        'user_id' => $this->authorizer->id,
        'stage' => 'department',
    ]);

    $this->get(route('approval.show', $approval->approval_token))
        ->assertOk()
        ->assertSee('ya fue autorizada previamente');
});

test('used token cannot be reused after approval', function () {
    Department::factory()->create(['name' => 'Administración']);

    $approval = PaymentRequestApproval::factory()->withToken()->create([
        'payment_request_id' => $this->paymentRequest->id,
        'user_id' => $this->authorizer->id,
        'stage' => 'department',
    ]);

    $token = $approval->approval_token;

    $this->post(route('approval.approve', $token))
        ->assertOk()
        ->assertSee('ha sido autorizada correctamente');

    $this->get(route('approval.show', $token))
        ->assertOk()
        ->assertSee('no es válido');
});

test('notification email contains approval token url', function () {
    Notification::fake();

    $this->department->authorizers()->attach($this->authorizer);

    $pr = PaymentRequest::factory()->create([
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => PendingDepartment::$name,
    ]);

    app(ApprovalService::class)->createApprovals($pr);

    Notification::assertSentTo(
        $this->authorizer,
        PaymentRequestCreated::class,
        function (PaymentRequestCreated $notification) {
            return $notification->approvalToken !== null;
        }
    );

    $approval = $pr->approvals()->first();
    expect($approval->approval_token)->not->toBeNull();
    expect($approval->approval_token_expires_at)->not->toBeNull();
});

test('email renders approval button as primary action when token exists', function () {
    Notification::fake();

    $this->department->authorizers()->attach($this->authorizer);

    $pr = PaymentRequest::factory()->create([
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => PendingDepartment::$name,
    ]);

    app(ApprovalService::class)->createApprovals($pr);

    Notification::assertSentTo(
        $this->authorizer,
        PaymentRequestCreated::class,
        function (PaymentRequestCreated $notification) {
            $mail = $notification->toMail($this->authorizer);

            expect($mail->actionText)->toBe('Autorizar / Rechazar Solicitud');
            expect($mail->actionUrl)->toContain('/approval/');
            expect($mail->actionUrl)->not->toContain('/admin/');

            return true;
        }
    );
});

test('email renders filament link as primary action when token is null', function () {
    $notification = new PaymentRequestCreated($this->paymentRequest, null);
    $mail = $notification->toMail($this->authorizer);

    expect($mail->actionText)->toBe('Ver Solicitud');
    expect($mail->actionUrl)->toContain('/admin/payment-requests/');
});

test('approval via email advances to next stage', function () {
    Department::factory()->create(['name' => 'Administración']);

    $approval = PaymentRequestApproval::factory()->withToken()->create([
        'payment_request_id' => $this->paymentRequest->id,
        'user_id' => $this->authorizer->id,
        'stage' => 'department',
    ]);

    $this->post(route('approval.approve', $approval->approval_token));

    $this->paymentRequest->refresh();
    expect($this->paymentRequest->status)->toBeInstanceOf(PendingAdministration::class);
});
