<?php

use App\Models\Branch;
use App\Models\Currency;
use App\Models\Department;
use App\Models\ExpenseConcept;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestApproval;
use App\Models\User;
use App\States\PaymentRequest\Completed;
use App\States\PaymentRequest\PendingAdministration;
use App\States\PaymentRequest\PendingDepartment;
use App\States\PaymentRequest\PendingTreasury;

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

    Department::factory()->create(['name' => 'ADMINISTRACIÓN']);

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

test('approval at administration stage saves number_purchase_invoices', function () {
    $authorizer = User::factory()->create(['department_id' => $this->department->id]);

    $pr = PaymentRequest::factory()->create([
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => PendingAdministration::$name,
    ]);

    PaymentRequestApproval::factory()->create([
        'payment_request_id' => $pr->id,
        'user_id' => $authorizer->id,
        'stage' => 'administration',
        'status' => 'pending',
    ]);

    Department::factory()->create(['name' => 'TESORERÍA']);

    $this->actingAs($authorizer)
        ->post(route('payment-requests.approve', $pr), [
            'number_purchase_invoices' => 12345,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('payment_requests', [
        'id' => $pr->id,
        'number_purchase_invoices' => 12345,
    ]);
});

test('approval at treasury stage saves number_vendor_payments', function () {
    $authorizer = User::factory()->create(['department_id' => $this->department->id]);

    $pr = PaymentRequest::factory()->create([
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => PendingTreasury::$name,
    ]);

    PaymentRequestApproval::factory()->create([
        'payment_request_id' => $pr->id,
        'user_id' => $authorizer->id,
        'stage' => 'treasury',
        'status' => 'pending',
    ]);

    $this->actingAs($authorizer)
        ->post(route('payment-requests.approve', $pr), [
            'number_vendor_payments' => 67890,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('payment_requests', [
        'id' => $pr->id,
        'number_vendor_payments' => 67890,
    ]);
});

test('approval without sap fields still works', function () {
    $authorizer = User::factory()->create(['department_id' => $this->department->id]);

    $pr = PaymentRequest::factory()->create([
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => PendingAdministration::$name,
    ]);

    PaymentRequestApproval::factory()->create([
        'payment_request_id' => $pr->id,
        'user_id' => $authorizer->id,
        'stage' => 'administration',
        'status' => 'pending',
    ]);

    Department::factory()->create(['name' => 'TESORERÍA']);

    $this->actingAs($authorizer)
        ->post(route('payment-requests.approve', $pr))
        ->assertRedirect();

    $pr->refresh();
    expect($pr->number_purchase_invoices)->toBeNull();
});

test('administration authorizer can update purchase invoices sap folio', function () {
    $authorizer = User::factory()->create(['department_id' => $this->department->id]);

    $pr = PaymentRequest::factory()->create([
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => PendingTreasury::$name,
    ]);

    PaymentRequestApproval::factory()->approved()->create([
        'payment_request_id' => $pr->id,
        'user_id' => $authorizer->id,
        'stage' => 'administration',
    ]);

    $this->actingAs($authorizer)
        ->patch(route('payment-requests.sap-folios', $pr), [
            'number_purchase_invoices' => 99999,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('payment_requests', [
        'id' => $pr->id,
        'number_purchase_invoices' => 99999,
    ]);
});

test('treasury authorizer can update vendor payments sap folio', function () {
    $authorizer = User::factory()->create(['department_id' => $this->department->id]);

    $pr = PaymentRequest::factory()->create([
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => Completed::$name,
    ]);

    PaymentRequestApproval::factory()->approved()->create([
        'payment_request_id' => $pr->id,
        'user_id' => $authorizer->id,
        'stage' => 'treasury',
    ]);

    $this->actingAs($authorizer)
        ->patch(route('payment-requests.sap-folios', $pr), [
            'number_vendor_payments' => 11111,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('payment_requests', [
        'id' => $pr->id,
        'number_vendor_payments' => 11111,
    ]);
});

test('non-authorized user cannot update sap folios', function () {
    $outsider = User::factory()->create(['department_id' => $this->department->id]);

    $pr = PaymentRequest::factory()->create([
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => PendingTreasury::$name,
    ]);

    $this->actingAs($outsider)
        ->patch(route('payment-requests.sap-folios', $pr), [
            'number_purchase_invoices' => 12345,
        ])
        ->assertForbidden();
});

test('administration authorizer cannot update vendor payments field', function () {
    $authorizer = User::factory()->create(['department_id' => $this->department->id]);

    $pr = PaymentRequest::factory()->create([
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => PendingTreasury::$name,
    ]);

    PaymentRequestApproval::factory()->approved()->create([
        'payment_request_id' => $pr->id,
        'user_id' => $authorizer->id,
        'stage' => 'administration',
    ]);

    $this->actingAs($authorizer)
        ->patch(route('payment-requests.sap-folios', $pr), [
            'number_vendor_payments' => 12345,
        ])
        ->assertRedirect();

    $pr->refresh();
    expect($pr->number_vendor_payments)->toBeNull();
});
