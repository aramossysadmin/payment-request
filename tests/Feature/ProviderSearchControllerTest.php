<?php

use App\Models\Branch;
use App\Models\Currency;
use App\Models\Department;
use App\Models\ExpenseConcept;
use App\Models\PaymentRequest;
use App\Models\User;

beforeEach(function () {
    $this->department = Department::factory()->create();
    $this->currency = Currency::factory()->create();
    $this->branch = Branch::factory()->create();
    $this->expenseConcept = ExpenseConcept::factory()->create();

    $this->user = User::factory()->create(['department_id' => $this->department->id]);
});

test('guests cannot search providers', function () {
    $this->getJson(route('providers.search', ['q' => 'test']))
        ->assertUnauthorized();
});

test('returns empty array when query is less than 2 characters', function () {
    $this->actingAs($this->user)
        ->getJson(route('providers.search', ['q' => 'a']))
        ->assertOk()
        ->assertExactJson([]);
});

test('returns empty array when query is missing', function () {
    $this->actingAs($this->user)
        ->getJson(route('providers.search'))
        ->assertOk()
        ->assertExactJson([]);
});

test('returns empty array for invalid field parameter', function () {
    $this->actingAs($this->user)
        ->getJson(route('providers.search', ['q' => 'test', 'field' => 'invalid']))
        ->assertOk()
        ->assertExactJson([]);
});

test('searches providers by provider name', function () {
    PaymentRequest::factory()->create([
        'provider' => 'EMPRESA NACIONAL SA',
        'rfc' => 'ENA123456789',
        'user_id' => $this->user->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
    ]);

    PaymentRequest::factory()->create([
        'provider' => 'Otra Compañía',
        'rfc' => 'OCP987654321',
        'user_id' => $this->user->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
    ]);

    $this->actingAs($this->user)
        ->getJson(route('providers.search', ['q' => 'Empresa', 'field' => 'provider']))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonFragment([
            'provider' => 'EMPRESA NACIONAL SA',
            'rfc' => 'ENA123456789',
        ]);
});

test('searches providers by rfc', function () {
    PaymentRequest::factory()->create([
        'provider' => 'EMPRESA NACIONAL SA',
        'rfc' => 'ENA123456789',
        'user_id' => $this->user->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
    ]);

    $this->actingAs($this->user)
        ->getJson(route('providers.search', ['q' => 'ENA123', 'field' => 'rfc']))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonFragment([
            'provider' => 'EMPRESA NACIONAL SA',
            'rfc' => 'ENA123456789',
        ]);
});

test('returns distinct provider-rfc combinations', function () {
    $attrs = [
        'provider' => 'Empresa Duplicada SA',
        'rfc' => 'EDU123456789',
        'user_id' => $this->user->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
    ];

    PaymentRequest::factory()->create($attrs);
    PaymentRequest::factory()->create($attrs);

    $this->actingAs($this->user)
        ->getJson(route('providers.search', ['q' => 'Duplicada', 'field' => 'provider']))
        ->assertOk()
        ->assertJsonCount(1);
});

test('defaults to searching by provider field', function () {
    PaymentRequest::factory()->create([
        'provider' => 'DEFAULT FIELD TEST SA',
        'rfc' => 'DFT123456789',
        'user_id' => $this->user->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
    ]);

    $this->actingAs($this->user)
        ->getJson(route('providers.search', ['q' => 'Default Field']))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonFragment(['provider' => 'DEFAULT FIELD TEST SA']);
});

test('limits results to 10', function () {
    for ($i = 0; $i < 15; $i++) {
        PaymentRequest::factory()->create([
            'provider' => "Proveedor Masivo {$i} SA",
            'rfc' => 'PMV'.str_pad($i, 10, '0', STR_PAD_LEFT),
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'currency_id' => $this->currency->id,
            'branch_id' => $this->branch->id,
            'expense_concept_id' => $this->expenseConcept->id,
        ]);
    }

    $this->actingAs($this->user)
        ->getJson(route('providers.search', ['q' => 'Proveedor Masivo', 'field' => 'provider']))
        ->assertOk()
        ->assertJsonCount(10);
});
