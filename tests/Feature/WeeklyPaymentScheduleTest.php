<?php

use App\Models\Branch;
use App\Models\Currency;
use App\Models\Department;
use App\Models\InvestmentPaymentRequest;
use App\Models\InvestmentRequest;
use App\Models\User;
use App\Models\WeeklyPaymentSchedule;
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

function createApprovedPayment(array $overrides = []): InvestmentPaymentRequest
{
    return InvestmentPaymentRequest::forceCreate(array_merge([
        'uuid' => fake()->uuid(),
        'folio_number' => fake()->unique()->numberBetween(1, 99999),
        'investment_request_id' => test()->investmentRequest->id,
        'user_id' => test()->user->id,
        'department_id' => test()->department->id,
        'provider' => 'PROVEEDOR TEST',
        'currency_id' => test()->currency->id,
        'branch_id' => test()->branch->id,
        'payment_type' => 'anticipo',
        'status' => 'approved',
        'subtotal' => 1000,
        'iva_rate' => '0.16',
        'iva' => 160,
        'retention' => false,
        'total' => 1160,
        'payment_provision_date' => Carbon::now()->addDays(3)->format('Y-m-d'),
        'payment_week_number' => Carbon::now()->addDays(3)->weekOfYear,
    ], $overrides));
}

it('renders the weekly payment schedule page', function () {
    $this->actingAs($this->user)
        ->get(route('weekly-payment-schedule.index'))
        ->assertSuccessful();
});

it('shows approved payments on the page', function () {
    $payment = createApprovedPayment();

    $response = $this->actingAs($this->user)
        ->get(route('weekly-payment-schedule.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('weekly-payment-schedule/index')
        ->has('payments', 1)
        ->has('currentWeek')
        ->has('currentYear')
    );
});

it('stores a weekly payment schedule with included and excluded items', function () {
    $payment1 = createApprovedPayment(['provider' => 'PROVEEDOR UNO']);
    $payment2 = createApprovedPayment(['provider' => 'PROVEEDOR DOS']);

    $currentWeek = (int) Carbon::now()->weekOfYear;

    $data = [
        'week_number' => $currentWeek,
        'year' => Carbon::now()->year,
        'items' => [
            ['id' => $payment1->id, 'included' => true, 'exclusion_reason' => null],
            ['id' => $payment2->id, 'included' => false, 'exclusion_reason' => 'Sin fondos'],
        ],
    ];

    $this->actingAs($this->user)
        ->post(route('weekly-payment-schedule.store'), $data)
        ->assertRedirect();

    $this->assertDatabaseHas('weekly_payment_schedules', [
        'week_number' => $currentWeek,
        'status' => 'pending_approval',
    ]);

    $schedule = WeeklyPaymentSchedule::first();
    expect($schedule->items)->toHaveCount(2);
    expect($schedule->items->where('included', true)->count())->toBe(1);
    expect($schedule->items->where('included', false)->first()->exclusion_reason)->toBe('Sin fondos');
});

it('postpones excluded payments to next week', function () {
    $currentWeek = (int) Carbon::now()->weekOfYear;
    $payment = createApprovedPayment(['payment_week_number' => $currentWeek]);

    $data = [
        'week_number' => $currentWeek,
        'year' => Carbon::now()->year,
        'items' => [
            ['id' => $payment->id, 'included' => false, 'exclusion_reason' => 'Pospuesto'],
        ],
    ];

    $this->actingAs($this->user)
        ->post(route('weekly-payment-schedule.store'), $data)
        ->assertRedirect();

    $payment->refresh();
    expect($payment->payment_week_number)->toBe($currentWeek + 1);
});

it('requires at least one item', function () {
    $data = [
        'week_number' => Carbon::now()->weekOfYear,
        'year' => Carbon::now()->year,
        'items' => [],
    ];

    $this->actingAs($this->user)
        ->post(route('weekly-payment-schedule.store'), $data)
        ->assertSessionHasErrors('items');
});

it('validates item ids exist', function () {
    $data = [
        'week_number' => Carbon::now()->weekOfYear,
        'year' => Carbon::now()->year,
        'items' => [
            ['id' => 99999, 'included' => true, 'exclusion_reason' => null],
        ],
    ];

    $this->actingAs($this->user)
        ->post(route('weekly-payment-schedule.store'), $data)
        ->assertSessionHasErrors('items.0.id');
});
