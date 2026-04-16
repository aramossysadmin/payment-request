<?php

use App\Models\Branch;
use App\Models\Currency;
use App\Models\Department;
use App\Models\InvestmentRequest;
use App\Models\User;
use App\States\InvestmentRequest\Completed;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->department = Department::factory()->create();
    $this->currency = Currency::factory()->create();
    $this->branch = Branch::factory()->create();
    $this->user = User::factory()->create(['department_id' => $this->department->id]);

    $this->investmentRequest = InvestmentRequest::factory()->create([
        'user_id' => $this->user->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'status' => Completed::$name,
        'total' => 100000,
    ]);
});

it('stores payment_provision_date and calculates payment_week_number', function () {
    $date = Carbon::now()->addDays(10);

    $data = [
        'investment_request_id' => $this->investmentRequest->id,
        'provider' => 'Proveedor Test',
        'rfc' => 'XAXX010101000',
        'payment_provision_date' => $date->format('Y-m-d'),
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'is_invoice' => false,
        'description' => 'Test payment',
        'subtotal' => 1000.00,
        'iva_rate' => '0.16',
        'iva' => 160.00,
        'retention' => false,
        'total' => 1160.00,
    ];

    $this->actingAs($this->user)
        ->post(route('investment-payment-requests.store'), $data)
        ->assertRedirect();

    $this->assertDatabaseHas('investment_payment_requests', [
        'payment_provision_date' => $date->format('Y-m-d') . ' 00:00:00',
        'payment_week_number' => $date->weekOfYear,
        'provider' => 'PROVEEDOR TEST',
    ]);
});

it('requires payment_provision_date', function () {
    $data = [
        'investment_request_id' => $this->investmentRequest->id,
        'provider' => 'Proveedor Test',
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'is_invoice' => false,
        'subtotal' => 1000.00,
        'iva_rate' => '0.16',
        'iva' => 160.00,
        'retention' => false,
        'total' => 1160.00,
    ];

    $this->actingAs($this->user)
        ->post(route('investment-payment-requests.store'), $data)
        ->assertSessionHasErrors('payment_provision_date');
});

it('rejects past dates for payment_provision_date', function () {
    $data = [
        'investment_request_id' => $this->investmentRequest->id,
        'provider' => 'Proveedor Test',
        'payment_provision_date' => Carbon::yesterday()->format('Y-m-d'),
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'is_invoice' => false,
        'subtotal' => 1000.00,
        'iva_rate' => '0.16',
        'iva' => 160.00,
        'retention' => false,
        'total' => 1160.00,
    ];

    $this->actingAs($this->user)
        ->post(route('investment-payment-requests.store'), $data)
        ->assertSessionHasErrors('payment_provision_date');
});

it('calculates correct week number for different dates', function () {
    $date = Carbon::now()->addMonth();

    $data = [
        'investment_request_id' => $this->investmentRequest->id,
        'provider' => 'Proveedor Semana',
        'payment_provision_date' => $date->format('Y-m-d'),
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'is_invoice' => false,
        'subtotal' => 500.00,
        'iva_rate' => '0.00',
        'iva' => 0.00,
        'retention' => false,
        'total' => 500.00,
    ];

    $this->actingAs($this->user)
        ->post(route('investment-payment-requests.store'), $data)
        ->assertRedirect();

    $this->assertDatabaseHas('investment_payment_requests', [
        'provider' => 'PROVEEDOR SEMANA',
        'payment_week_number' => $date->weekOfYear,
    ]);
});
