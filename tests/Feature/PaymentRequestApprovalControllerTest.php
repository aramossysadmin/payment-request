<?php

use App\Models\Branch;
use App\Models\Currency;
use App\Models\Department;
use App\Models\ExpenseConcept;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestApproval;
use App\Models\User;
use App\States\PaymentRequest\PendingAdministration;
use App\States\PaymentRequest\PendingDepartment;

beforeEach(function () {
    $this->department = Department::factory()->create();
    $this->currency = Currency::factory()->create();
    $this->branch = Branch::factory()->create();
    $this->expenseConcept = ExpenseConcept::factory()->create();
});

test('authorized user can approve at department stage', function () {
    $authorizer = User::factory()->create(['department_id' => $this->department->id]);

    $pr = PaymentRequest::factory()->create([
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => PendingDepartment::$name,
    ]);

    PaymentRequestApproval::factory()->create([
        'payment_request_id' => $pr->id,
        'user_id' => $authorizer->id,
        'stage' => 'department',
        'status' => 'pending',
    ]);

    Department::factory()->create(['name' => 'Administración']);

    $this->actingAs($authorizer)
        ->post(route('payment-requests.approve', $pr))
        ->assertRedirect();

    $this->assertDatabaseHas('payment_request_approvals', [
        'payment_request_id' => $pr->id,
        'user_id' => $authorizer->id,
        'status' => 'approved',
    ]);
});

test('authorized user can reject with comments', function () {
    $authorizer = User::factory()->create(['department_id' => $this->department->id]);

    $pr = PaymentRequest::factory()->create([
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => PendingDepartment::$name,
    ]);

    PaymentRequestApproval::factory()->create([
        'payment_request_id' => $pr->id,
        'user_id' => $authorizer->id,
        'stage' => 'department',
        'status' => 'pending',
    ]);

    $this->actingAs($authorizer)
        ->post(route('payment-requests.reject', $pr), [
            'comments' => 'No cumple con los requisitos necesarios',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('payment_request_approvals', [
        'payment_request_id' => $pr->id,
        'user_id' => $authorizer->id,
        'status' => 'rejected',
        'comments' => 'No cumple con los requisitos necesarios',
    ]);
});

test('non-assigned user cannot approve', function () {
    $outsider = User::factory()->create(['department_id' => $this->department->id]);

    $pr = PaymentRequest::factory()->create([
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => PendingDepartment::$name,
    ]);

    $this->actingAs($outsider)
        ->post(route('payment-requests.approve', $pr))
        ->assertForbidden();
});

test('reject requires comments with at least 10 characters', function () {
    $authorizer = User::factory()->create(['department_id' => $this->department->id]);

    $pr = PaymentRequest::factory()->create([
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => PendingDepartment::$name,
    ]);

    PaymentRequestApproval::factory()->create([
        'payment_request_id' => $pr->id,
        'user_id' => $authorizer->id,
        'stage' => 'department',
        'status' => 'pending',
    ]);

    $this->actingAs($authorizer)
        ->post(route('payment-requests.reject', $pr), [
            'comments' => 'short',
        ])
        ->assertSessionHasErrors('comments');
});

test('cannot approve already approved stage', function () {
    $authorizer = User::factory()->create(['department_id' => $this->department->id]);

    $pr = PaymentRequest::factory()->create([
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => PendingAdministration::$name,
    ]);

    PaymentRequestApproval::factory()->approved()->create([
        'payment_request_id' => $pr->id,
        'user_id' => $authorizer->id,
        'stage' => 'department',
    ]);

    $this->actingAs($authorizer)
        ->post(route('payment-requests.approve', $pr))
        ->assertForbidden();
});
