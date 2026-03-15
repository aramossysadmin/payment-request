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
use Database\Seeders\RoleAndPermissionSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(RoleAndPermissionSeeder::class);

    $this->department = Department::factory()->create();
    $this->currency = Currency::factory()->create();
    $this->branch = Branch::factory()->create();
    $this->expenseConcept = ExpenseConcept::factory()->create();

    $this->mock(\App\Http\Middleware\HandleInertiaRequests::class)
        ->makePartial()
        ->shouldReceive('version')
        ->andReturn('testing');
});

function partialHeaders(string ...$props): array
{
    return [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => 'testing',
        'X-Inertia-Partial-Data' => implode(',', $props),
        'X-Inertia-Partial-Component' => 'dashboard',
    ];
}

test('guests are redirected to login', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view the dashboard', function () {
    $user = User::factory()->create(['department_id' => $this->department->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('isAuthorizer')
            ->has('isSuperAdmin')
        );
});

test('normal users have isAuthorizer as false', function () {
    $user = User::factory()->create(['department_id' => $this->department->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('isAuthorizer', false)
            ->where('isSuperAdmin', false)
        );
});

test('authorizers have isAuthorizer as true', function () {
    $authorizer = User::factory()->create(['department_id' => $this->department->id]);
    $authorizer->authorizedDepartments()->attach($this->department->id);

    $this->actingAs($authorizer)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('isAuthorizer', true)
        );
});

test('super admins have isSuperAdmin as true', function () {
    $admin = User::factory()->create(['department_id' => $this->department->id]);
    $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $admin->assignRole($role);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('isSuperAdmin', true)
        );
});

test('deferred stats are returned correctly', function () {
    $user = User::factory()->create(['department_id' => $this->department->id]);

    PaymentRequest::factory()->create([
        'user_id' => $user->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => PendingDepartment::$name,
        'total' => 1000.00,
    ]);

    PaymentRequest::factory()->create([
        'user_id' => $user->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => PendingAdministration::$name,
        'total' => 2000.00,
    ]);

    $response = $this->actingAs($user)
        ->withHeaders(partialHeaders('stats'))
        ->get(route('dashboard'));

    $response->assertOk();

    $props = $response->json('props');

    expect($props['stats']['pendingCount'])->toBe(2);
    expect($props['stats']['pendingByStage']['department'])->toBe(1);
    expect($props['stats']['pendingByStage']['administration'])->toBe(1);
    expect($props['stats']['pendingByStage']['treasury'])->toBe(0);
    expect($props['stats']['monthlyTotal'])->toBe('3000.00');
});

test('deferred recent requests respect role filtering', function () {
    $user = User::factory()->create(['department_id' => $this->department->id]);
    $other = User::factory()->create(['department_id' => $this->department->id]);

    PaymentRequest::factory()->create([
        'user_id' => $user->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'provider' => 'MI PROVEEDOR',
    ]);

    PaymentRequest::factory()->create([
        'user_id' => $other->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'provider' => 'Otro Proveedor',
    ]);

    $response = $this->actingAs($user)
        ->withHeaders(partialHeaders('recentRequests'))
        ->get(route('dashboard'));

    $response->assertOk();

    $props = $response->json('props');

    expect($props['recentRequests'])->toHaveCount(1);
    expect($props['recentRequests'][0]['provider'])->toBe('MI PROVEEDOR');
});

test('super admin sees all recent requests', function () {
    $admin = User::factory()->create(['department_id' => $this->department->id]);
    $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $admin->assignRole($role);

    $other = User::factory()->create(['department_id' => $this->department->id]);

    PaymentRequest::factory()->create([
        'user_id' => $admin->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
    ]);

    PaymentRequest::factory()->create([
        'user_id' => $other->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
    ]);

    $response = $this->actingAs($admin)
        ->withHeaders(partialHeaders('recentRequests'))
        ->get(route('dashboard'));

    $response->assertOk();

    $props = $response->json('props');

    expect($props['recentRequests'])->toHaveCount(2);
});

test('pending approvals are returned for authorizers', function () {
    $authorizer = User::factory()->create(['department_id' => $this->department->id]);
    $authorizer->authorizedDepartments()->attach($this->department->id);

    $requestor = User::factory()->create(['department_id' => $this->department->id]);

    $pr = PaymentRequest::factory()->create([
        'user_id' => $requestor->id,
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

    $response = $this->actingAs($authorizer)
        ->withHeaders(partialHeaders('pendingApprovals'))
        ->get(route('dashboard'));

    $response->assertOk();

    $props = $response->json('props');

    expect($props['pendingApprovals'])->toHaveCount(1);
});

test('pending approvals are empty for non-authorizers', function () {
    $user = User::factory()->create(['department_id' => $this->department->id]);

    $response = $this->actingAs($user)
        ->withHeaders(partialHeaders('pendingApprovals'))
        ->get(route('dashboard'));

    $response->assertOk();

    $props = $response->json('props');

    expect($props['pendingApprovals'])->toBe([]);
});

test('chart data returns 6 months', function () {
    $user = User::factory()->create(['department_id' => $this->department->id]);

    $response = $this->actingAs($user)
        ->withHeaders(partialHeaders('chartData'))
        ->get(route('dashboard'));

    $response->assertOk();

    $props = $response->json('props');

    expect($props['chartData'])->toHaveCount(6);
    expect($props['chartData'][0])->toHaveKeys(['month', 'count']);
});
