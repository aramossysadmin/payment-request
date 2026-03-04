<?php

use App\Models\Branch;
use App\Models\Currency;
use App\Models\Department;
use App\Models\ExpenseConcept;
use App\Models\User;

beforeEach(function () {
    $this->department = Department::factory()->create();
    $this->user = User::factory()->create(['department_id' => $this->department->id]);
    $this->department->authorizers()->attach($this->user->id);

    $this->validData = [
        'provider' => 'Test Provider',
        'invoice_folio' => 'FAC-0001',
        'currency_id' => Currency::factory()->create()->id,
        'branch_id' => Branch::factory()->create()->id,
        'expense_concept_id' => ExpenseConcept::factory()->create()->id,
        'payment_type' => 'full',
        'subtotal' => 1000.00,
        'iva' => 160.00,
        'retention' => false,
        'total' => 1160.00,
    ];
});

test('validation passes with valid data', function () {
    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), $this->validData)
        ->assertRedirect(route('payment-requests.index'));
});

test('provider is required', function () {
    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [...$this->validData, 'provider' => ''])
        ->assertSessionHasErrors('provider');
});

test('provider must be a string with max 255 characters', function () {
    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [...$this->validData, 'provider' => str_repeat('a', 256)])
        ->assertSessionHasErrors('provider');
});

test('invoice_folio is required', function () {
    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [...$this->validData, 'invoice_folio' => ''])
        ->assertSessionHasErrors('invoice_folio');
});

test('currency_id must exist in currencies table', function () {
    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [...$this->validData, 'currency_id' => 99999])
        ->assertSessionHasErrors('currency_id');
});

test('branch_id must exist in branches table', function () {
    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [...$this->validData, 'branch_id' => 99999])
        ->assertSessionHasErrors('branch_id');
});

test('expense_concept_id must exist in expense_concepts table', function () {
    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [...$this->validData, 'expense_concept_id' => 99999])
        ->assertSessionHasErrors('expense_concept_id');
});

test('payment_type must be a valid enum value', function (string $invalidType) {
    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [...$this->validData, 'payment_type' => $invalidType])
        ->assertSessionHasErrors('payment_type');
})->with([
    'invalid',
    'partial',
    '',
]);

test('valid payment types are accepted', function (string $type) {
    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [...$this->validData, 'payment_type' => $type])
        ->assertRedirect(route('payment-requests.index'));
})->with([
    'full',
    'advance',
]);

test('subtotal must be numeric and non-negative', function (mixed $value) {
    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [...$this->validData, 'subtotal' => $value])
        ->assertSessionHasErrors('subtotal');
})->with([
    'abc',
    -1,
]);

test('total must be numeric and non-negative', function (mixed $value) {
    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [...$this->validData, 'total' => $value])
        ->assertSessionHasErrors('total');
})->with([
    'abc',
    -1,
]);

test('description is optional', function () {
    $data = $this->validData;
    unset($data['description']);

    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), $data)
        ->assertRedirect(route('payment-requests.index'));
});
