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
use Database\Seeders\RoleAndPermissionSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(RoleAndPermissionSeeder::class);

    $this->department = Department::factory()->create();
    $this->currency = Currency::factory()->create();
    $this->branch = Branch::factory()->create();
    $this->expenseConcept = ExpenseConcept::factory()->create();
});

test('guests are redirected to the login page', function () {
    $this->get(route('payment-requests.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view the index', function () {
    $user = User::factory()->create(['department_id' => $this->department->id]);

    $this->actingAs($user)
        ->get(route('payment-requests.index'))
        ->assertOk();
});

test('normal users only see their own payment requests', function () {
    $user = User::factory()->create(['department_id' => $this->department->id]);
    $other = User::factory()->create(['department_id' => $this->department->id]);

    $ownPr = PaymentRequest::factory()->create([
        'user_id' => $user->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'provider' => 'Mi Proveedor',
    ]);

    PaymentRequest::factory()->create([
        'user_id' => $other->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'provider' => 'Otro Proveedor',
    ]);

    $this->actingAs($user)
        ->get(route('payment-requests.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('payment-requests/index')
            ->has('paymentRequests.data', 1)
            ->where('paymentRequests.data.0.uuid', $ownPr->uuid)
        );
});

test('authorizers see their department payment requests', function () {
    $authorizer = User::factory()->create(['department_id' => $this->department->id]);
    $authorizer->authorizedDepartments()->attach($this->department->id);

    $userInDept = User::factory()->create(['department_id' => $this->department->id]);
    $otherDept = Department::factory()->create();
    $otherUser = User::factory()->create(['department_id' => $otherDept->id]);

    $deptPr = PaymentRequest::factory()->create([
        'user_id' => $userInDept->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
    ]);

    PaymentRequest::factory()->create([
        'user_id' => $otherUser->id,
        'department_id' => $otherDept->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
    ]);

    $this->actingAs($authorizer)
        ->get(route('payment-requests.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('paymentRequests.data', 1)
            ->where('paymentRequests.data.0.uuid', $deptPr->uuid)
        );
});

test('authorizers see payment requests from other departments when they have an assigned approval', function () {
    $systemsDept = Department::factory()->create(['name' => 'SISTEMAS']);
    $adminDept = Department::factory()->create(['name' => 'ADMINISTRACIÓN']);

    $collaborator = User::factory()->create(['department_id' => $systemsDept->id]);
    $deptAuthorizer = User::factory()->create(['department_id' => $systemsDept->id]);
    $adminAuthorizer = User::factory()->create(['department_id' => $adminDept->id]);

    $systemsDept->authorizers()->attach($deptAuthorizer->id);
    $adminDept->authorizers()->attach($adminAuthorizer->id);

    $pr = PaymentRequest::factory()->create([
        'user_id' => $collaborator->id,
        'department_id' => $systemsDept->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => PendingAdministration::$name,
    ]);

    PaymentRequestApproval::factory()->approved()->create([
        'payment_request_id' => $pr->id,
        'user_id' => $deptAuthorizer->id,
        'stage' => 'department',
    ]);

    PaymentRequestApproval::factory()->create([
        'payment_request_id' => $pr->id,
        'user_id' => $adminAuthorizer->id,
        'stage' => 'administration',
        'status' => 'pending',
    ]);

    $this->actingAs($adminAuthorizer)
        ->get(route('payment-requests.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('paymentRequests.data', 1)
            ->where('paymentRequests.data.0.uuid', $pr->uuid)
        );
});

test('authorizers do not see payment requests without assigned approvals', function () {
    $systemsDept = Department::factory()->create(['name' => 'SISTEMAS']);
    $adminDept = Department::factory()->create(['name' => 'ADMINISTRACIÓN']);

    $collaborator = User::factory()->create(['department_id' => $systemsDept->id]);
    $adminAuthorizer = User::factory()->create(['department_id' => $adminDept->id]);

    $adminDept->authorizers()->attach($adminAuthorizer->id);

    PaymentRequest::factory()->create([
        'user_id' => $collaborator->id,
        'department_id' => $systemsDept->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => PendingDepartment::$name,
    ]);

    $this->actingAs($adminAuthorizer)
        ->get(route('payment-requests.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('paymentRequests.data', 0)
        );
});

test('search filter works by provider', function () {
    $user = User::factory()->create(['department_id' => $this->department->id]);

    $acme = PaymentRequest::factory()->create([
        'user_id' => $user->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'provider' => 'Acme Corp',
    ]);

    PaymentRequest::factory()->create([
        'user_id' => $user->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'provider' => 'Globex Inc',
    ]);

    $this->actingAs($user)
        ->get(route('payment-requests.index', ['search' => 'ACME']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('paymentRequests.data', 1)
            ->where('paymentRequests.data.0.provider', 'ACME CORP')
        );
});

test('status filter works', function () {
    $user = User::factory()->create(['department_id' => $this->department->id]);

    PaymentRequest::factory()->create([
        'user_id' => $user->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => PendingDepartment::$name,
    ]);

    PaymentRequest::factory()->create([
        'user_id' => $user->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => PendingAdministration::$name,
    ]);

    $this->actingAs($user)
        ->get(route('payment-requests.index', ['status' => PendingDepartment::$name]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('paymentRequests.data', 1)
        );
});

test('create page loads with catalogs', function () {
    $user = User::factory()->create(['department_id' => $this->department->id]);

    $this->actingAs($user)
        ->get(route('payment-requests.create'))
        ->assertOk();
});

test('store creates a payment request with valid data', function () {
    $user = User::factory()->create(['department_id' => $this->department->id]);
    $this->department->authorizers()->attach($user->id);

    $data = [
        'provider' => 'Test Provider',
        'invoice_folio' => 'FAC-0001',
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'payment_type' => 'advance',
        'subtotal' => 1000.00,
        'iva_rate' => '0.16',
        'iva' => 160.00,
        'retention' => false,
        'total' => 1160.00,
    ];

    $this->actingAs($user)
        ->post(route('payment-requests.store'), $data)
        ->assertRedirect(route('payment-requests.index'));

    $this->assertDatabaseHas('payment_requests', [
        'provider' => 'TEST PROVIDER',
        'user_id' => $user->id,
        'department_id' => $this->department->id,
    ]);
});

test('store auto-assigns user_id and department_id', function () {
    $user = User::factory()->create(['department_id' => $this->department->id]);
    $this->department->authorizers()->attach($user->id);

    $data = [
        'provider' => 'Auto Assign Test',
        'invoice_folio' => 'FAC-AUTO',
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'payment_type' => 'advance',
        'subtotal' => 500,
        'iva_rate' => '0.16',
        'iva' => 80,
        'retention' => false,
        'total' => 580,
    ];

    $this->actingAs($user)
        ->post(route('payment-requests.store'), $data);

    $pr = PaymentRequest::where('provider', 'AUTO ASSIGN TEST')->first();

    expect($pr->user_id)->toBe($user->id);
    expect($pr->department_id)->toBe($this->department->id);
});

test('store creates initial approval', function () {
    $user = User::factory()->create(['department_id' => $this->department->id]);
    $authorizer = User::factory()->create(['department_id' => $this->department->id]);
    $this->department->authorizers()->attach($authorizer->id);

    $data = [
        'provider' => 'Approval Test',
        'invoice_folio' => 'FAC-APPR',
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'payment_type' => 'advance',
        'subtotal' => 500,
        'iva_rate' => '0.16',
        'iva' => 80,
        'retention' => false,
        'total' => 580,
    ];

    $this->actingAs($user)
        ->post(route('payment-requests.store'), $data);

    $pr = PaymentRequest::where('provider', 'APPROVAL TEST')->first();

    expect($pr->approvals)->toHaveCount(1);
    expect($pr->approvals->first()->stage)->toBe('department');
    expect($pr->approvals->first()->user_id)->toBe($authorizer->id);
});

test('store validates required fields', function (string $field) {
    $user = User::factory()->create(['department_id' => $this->department->id]);

    $data = [
        'provider' => 'Test',
        'invoice_folio' => 'FAC-0001',
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'payment_type' => 'advance',
        'subtotal' => 1000,
        'iva_rate' => '0.16',
        'iva' => 160,
        'retention' => false,
        'total' => 1160,
    ];

    unset($data[$field]);

    $this->actingAs($user)
        ->post(route('payment-requests.store'), $data)
        ->assertSessionHasErrors($field);
})->with([
    'provider',
    'invoice_folio',
    'currency_id',
    'branch_id',
    'expense_concept_id',
    'payment_type',
    'subtotal',
    'iva',
    'total',
]);

test('show displays payment request details', function () {
    $user = User::factory()->create(['department_id' => $this->department->id]);
    $user->assignRole('Colaborador');

    $pr = PaymentRequest::factory()->create([
        'user_id' => $user->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
    ]);

    $this->actingAs($user)
        ->get(route('payment-requests.show', $pr))
        ->assertOk();
});

test('edit is only accessible for pending_department status', function () {
    $user = User::factory()->create(['department_id' => $this->department->id]);
    $user->assignRole('Colaborador');

    $pr = PaymentRequest::factory()->create([
        'user_id' => $user->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => PendingDepartment::$name,
    ]);

    $this->actingAs($user)
        ->get(route('payment-requests.edit', $pr))
        ->assertOk();

    $prAdmin = PaymentRequest::factory()->create([
        'user_id' => $user->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => PendingAdministration::$name,
    ]);

    $this->actingAs($user)
        ->get(route('payment-requests.edit', $prAdmin))
        ->assertForbidden();
});

test('update modifies the payment request', function () {
    $user = User::factory()->create(['department_id' => $this->department->id]);
    $user->assignRole('Colaborador');

    $pr = PaymentRequest::factory()->create([
        'user_id' => $user->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => PendingDepartment::$name,
        'provider' => 'Old Provider',
    ]);

    $this->actingAs($user)
        ->put(route('payment-requests.update', $pr), [
            'provider' => 'New Provider',
            'invoice_folio' => $pr->invoice_folio,
            'currency_id' => $this->currency->id,
            'branch_id' => $this->branch->id,
            'expense_concept_id' => $this->expenseConcept->id,
            'payment_type' => 'advance',
            'subtotal' => 1000,
            'iva_rate' => '0.16',
            'iva' => 160,
            'retention' => false,
            'total' => 1160,
        ])
        ->assertRedirect(route('payment-requests.show', $pr));

    expect($pr->refresh()->provider)->toBe('NEW PROVIDER');
});

test('soft delete works', function () {
    $user = User::factory()->create(['department_id' => $this->department->id]);
    $role = Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user->assignRole($role);

    $pr = PaymentRequest::factory()->create([
        'user_id' => $user->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
    ]);

    $this->actingAs($user)
        ->delete(route('payment-requests.destroy', $pr))
        ->assertRedirect(route('payment-requests.index'));

    expect($pr->refresh()->trashed())->toBeTrue();
});

test('status_group pending filter returns only pending statuses', function () {
    $user = User::factory()->create(['department_id' => $this->department->id]);

    PaymentRequest::factory()->create([
        'user_id' => $user->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => PendingDepartment::$name,
    ]);

    PaymentRequest::factory()->create([
        'user_id' => $user->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => PendingTreasury::$name,
    ]);

    PaymentRequest::factory()->create([
        'user_id' => $user->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => Completed::$name,
    ]);

    $this->actingAs($user)
        ->get(route('payment-requests.index', ['status_group' => 'pending']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('paymentRequests.data', 2)
        );
});

test('status_group completed filter returns only completed statuses', function () {
    $user = User::factory()->create(['department_id' => $this->department->id]);

    PaymentRequest::factory()->create([
        'user_id' => $user->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => PendingDepartment::$name,
    ]);

    PaymentRequest::factory()->create([
        'user_id' => $user->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => Completed::$name,
    ]);

    $this->actingAs($user)
        ->get(route('payment-requests.index', ['status_group' => 'completed']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('paymentRequests.data', 1)
        );
});

test('index defaults to pending filter when no status_group is provided', function () {
    $user = User::factory()->create(['department_id' => $this->department->id]);

    PaymentRequest::factory()->create([
        'user_id' => $user->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => PendingDepartment::$name,
    ]);

    PaymentRequest::factory()->create([
        'user_id' => $user->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => Completed::$name,
    ]);

    $this->actingAs($user)
        ->get(route('payment-requests.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('paymentRequests.data', 1)
            ->where('filters.status_group', 'pending')
        );
});

test('status_group all filter returns all payment requests', function () {
    $user = User::factory()->create(['department_id' => $this->department->id]);

    PaymentRequest::factory()->create([
        'user_id' => $user->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => PendingDepartment::$name,
    ]);

    PaymentRequest::factory()->create([
        'user_id' => $user->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'status' => Completed::$name,
    ]);

    $this->actingAs($user)
        ->get(route('payment-requests.index', ['status_group' => 'all']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('paymentRequests.data', 2)
            ->where('filters.status_group', 'all')
        );
});

test('uuid is automatically generated when creating a payment request', function () {
    $user = User::factory()->create(['department_id' => $this->department->id]);
    $this->department->authorizers()->attach($user->id);

    $data = [
        'provider' => 'UUID Test Provider',
        'invoice_folio' => 'FAC-UUID',
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
        'payment_type' => 'advance',
        'subtotal' => 1000,
        'iva_rate' => '0.16',
        'iva' => 160,
        'retention' => false,
        'total' => 1160,
    ];

    $this->actingAs($user)
        ->post(route('payment-requests.store'), $data);

    $pr = PaymentRequest::where('provider', 'UUID TEST PROVIDER')->first();

    expect($pr->uuid)->not->toBeNull();
    expect($pr->uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});

test('route model binding resolves by uuid', function () {
    $user = User::factory()->create(['department_id' => $this->department->id]);
    $user->assignRole('Colaborador');

    $pr = PaymentRequest::factory()->create([
        'user_id' => $user->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
    ]);

    $this->actingAs($user)
        ->get("/payment-requests/{$pr->uuid}")
        ->assertOk();
});

test('accessing payment request by integer id returns 404', function () {
    $user = User::factory()->create(['department_id' => $this->department->id]);
    $user->assignRole('Colaborador');

    $pr = PaymentRequest::factory()->create([
        'user_id' => $user->id,
        'department_id' => $this->department->id,
        'currency_id' => $this->currency->id,
        'branch_id' => $this->branch->id,
        'expense_concept_id' => $this->expenseConcept->id,
    ]);

    $this->actingAs($user)
        ->get("/payment-requests/{$pr->id}")
        ->assertNotFound();
});
