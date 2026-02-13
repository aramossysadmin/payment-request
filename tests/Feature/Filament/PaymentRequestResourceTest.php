<?php

use App\Enums\PaymentRequestStatus;
use App\Enums\PaymentType;
use App\Filament\Resources\PaymentRequestResource\Pages\CreatePaymentRequest;
use App\Filament\Resources\PaymentRequestResource\Pages\EditPaymentRequest;
use App\Filament\Resources\PaymentRequestResource\Pages\ListPaymentRequests;
use App\Models\Branch;
use App\Models\Currency;
use App\Models\ExpenseConcept;
use App\Models\PaymentRequest;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $this->superAdmin = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $this->superAdmin->assignRole($role);
    $this->actingAs($this->superAdmin);
});

it('can render the list page', function () {
    Livewire::test(ListPaymentRequests::class)
        ->assertSuccessful();
});

it('can list payment requests', function () {
    $paymentRequests = PaymentRequest::factory()->count(3)->create();

    Livewire::test(ListPaymentRequests::class)
        ->assertCanSeeTableRecords($paymentRequests);
});

it('can render the create page', function () {
    Livewire::test(CreatePaymentRequest::class)
        ->assertSuccessful();
});

it('can create a payment request', function () {
    $currency = Currency::factory()->create();
    $branch = Branch::factory()->create();
    $expenseConcept = ExpenseConcept::factory()->create();

    Livewire::test(CreatePaymentRequest::class)
        ->set('data.provider', 'Proveedor Test')
        ->set('data.invoice_folio', 'FAC-0001')
        ->set('data.currency_id', $currency->id)
        ->set('data.branch_id', $branch->id)
        ->set('data.expense_concept_id', $expenseConcept->id)
        ->set('data.description', 'Descripción de prueba')
        ->set('data.payment_type', PaymentType::Full->value)
        ->set('data.subtotal', 1000.00)
        ->set('data.iva', 160.00)
        ->set('data.retention', 0)
        ->set('data.total', 1160.00)
        ->set('data.status', PaymentRequestStatus::Pending->value)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('payment_requests', [
        'provider' => 'Proveedor Test',
        'invoice_folio' => 'FAC-0001',
        'user_id' => $this->superAdmin->id,
        'currency_id' => $currency->id,
        'branch_id' => $branch->id,
        'expense_concept_id' => $expenseConcept->id,
    ]);
});

it('auto-assigns authenticated user on create', function () {
    $currency = Currency::factory()->create();
    $branch = Branch::factory()->create();
    $expenseConcept = ExpenseConcept::factory()->create();

    Livewire::test(CreatePaymentRequest::class)
        ->set('data.provider', 'Proveedor Auto')
        ->set('data.invoice_folio', 'FAC-AUTO')
        ->set('data.currency_id', $currency->id)
        ->set('data.branch_id', $branch->id)
        ->set('data.expense_concept_id', $expenseConcept->id)
        ->set('data.payment_type', PaymentType::Full->value)
        ->set('data.subtotal', 500.00)
        ->set('data.iva', 80.00)
        ->set('data.retention', 0)
        ->set('data.total', 580.00)
        ->set('data.status', PaymentRequestStatus::Pending->value)
        ->call('create')
        ->assertHasNoFormErrors();

    $paymentRequest = PaymentRequest::where('invoice_folio', 'FAC-AUTO')->first();
    expect($paymentRequest->user_id)->toBe($this->superAdmin->id);
});

it('validates required fields on create', function () {
    Livewire::test(CreatePaymentRequest::class)
        ->fillForm([
            'provider' => '',
            'invoice_folio' => '',
            'currency_id' => null,
            'branch_id' => null,
            'expense_concept_id' => null,
            'subtotal' => null,
            'iva' => null,
            'total' => null,
            'status' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'provider' => 'required',
            'invoice_folio' => 'required',
            'currency_id' => 'required',
            'branch_id' => 'required',
            'expense_concept_id' => 'required',
            'subtotal' => 'required',
            'iva' => 'required',
            'total' => 'required',
            'status' => 'required',
        ]);
});

it('can render the edit page', function () {
    $paymentRequest = PaymentRequest::factory()->create();

    Livewire::test(EditPaymentRequest::class, ['record' => $paymentRequest->getRouteKey()])
        ->assertSuccessful();
});

it('can edit a payment request', function () {
    $paymentRequest = PaymentRequest::factory()->create();
    $newBranch = Branch::factory()->create();

    Livewire::test(EditPaymentRequest::class, ['record' => $paymentRequest->getRouteKey()])
        ->set('data.provider', 'Proveedor Editado')
        ->set('data.branch_id', $newBranch->id)
        ->call('save')
        ->assertHasNoFormErrors();

    $paymentRequest->refresh();
    expect($paymentRequest->provider)->toBe('Proveedor Editado');
    expect($paymentRequest->branch_id)->toBe($newBranch->id);
});

it('can change status of a payment request', function () {
    $paymentRequest = PaymentRequest::factory()->create([
        'status' => PaymentRequestStatus::Pending,
    ]);

    Livewire::test(EditPaymentRequest::class, ['record' => $paymentRequest->getRouteKey()])
        ->set('data.status', PaymentRequestStatus::Approved->value)
        ->call('save')
        ->assertHasNoFormErrors();

    $paymentRequest->refresh();
    expect($paymentRequest->status)->toBe(PaymentRequestStatus::Approved);
});

it('can soft delete a payment request', function () {
    $paymentRequest = PaymentRequest::factory()->create();

    Livewire::test(ListPaymentRequests::class)
        ->callTableAction('delete', $paymentRequest);

    $this->assertSoftDeleted('payment_requests', ['id' => $paymentRequest->id]);
});

it('can restore a soft deleted payment request', function () {
    $paymentRequest = PaymentRequest::factory()->create();
    $paymentRequest->delete();

    Livewire::test(EditPaymentRequest::class, ['record' => $paymentRequest->getRouteKey()])
        ->callAction('restore');

    $paymentRequest->refresh();
    expect($paymentRequest->deleted_at)->toBeNull();
});

it('can filter by status', function () {
    $pending = PaymentRequest::factory()->create(['status' => PaymentRequestStatus::Pending]);
    $approved = PaymentRequest::factory()->create(['status' => PaymentRequestStatus::Approved]);

    Livewire::test(ListPaymentRequests::class)
        ->filterTable('status', PaymentRequestStatus::Pending->value)
        ->assertCanSeeTableRecords([$pending])
        ->assertCanNotSeeTableRecords([$approved]);
});

it('can filter by currency', function () {
    $currency = Currency::factory()->create();
    $paymentRequest = PaymentRequest::factory()->create(['currency_id' => $currency->id]);
    $other = PaymentRequest::factory()->create();

    Livewire::test(ListPaymentRequests::class)
        ->filterTable('currency', $currency->id)
        ->assertCanSeeTableRecords([$paymentRequest])
        ->assertCanNotSeeTableRecords([$other]);
});

it('can filter by branch', function () {
    $branch = Branch::factory()->create();
    $paymentRequest = PaymentRequest::factory()->create(['branch_id' => $branch->id]);
    $other = PaymentRequest::factory()->create();

    Livewire::test(ListPaymentRequests::class)
        ->filterTable('branch', $branch->id)
        ->assertCanSeeTableRecords([$paymentRequest])
        ->assertCanNotSeeTableRecords([$other]);
});

it('can filter by expense concept', function () {
    $concept = ExpenseConcept::factory()->create();
    $paymentRequest = PaymentRequest::factory()->create(['expense_concept_id' => $concept->id]);
    $other = PaymentRequest::factory()->create();

    Livewire::test(ListPaymentRequests::class)
        ->filterTable('expenseConcept', $concept->id)
        ->assertCanSeeTableRecords([$paymentRequest])
        ->assertCanNotSeeTableRecords([$other]);
});

it('can filter by user', function () {
    $user = User::factory()->create();
    $paymentRequest = PaymentRequest::factory()->create(['user_id' => $user->id]);
    $other = PaymentRequest::factory()->create();

    Livewire::test(ListPaymentRequests::class)
        ->filterTable('user', $user->id)
        ->assertCanSeeTableRecords([$paymentRequest])
        ->assertCanNotSeeTableRecords([$other]);
});

it('can search payment requests by provider', function () {
    $paymentRequest = PaymentRequest::factory()->create(['provider' => 'Proveedor Buscable']);
    $other = PaymentRequest::factory()->create(['provider' => 'Otro Proveedor']);

    Livewire::test(ListPaymentRequests::class)
        ->searchTable('Proveedor Buscable')
        ->assertCanSeeTableRecords([$paymentRequest])
        ->assertCanNotSeeTableRecords([$other]);
});

it('can search payment requests by invoice folio', function () {
    $paymentRequest = PaymentRequest::factory()->create(['invoice_folio' => 'FAC-UNICA-999']);
    $other = PaymentRequest::factory()->create(['invoice_folio' => 'FAC-OTRA-001']);

    Livewire::test(ListPaymentRequests::class)
        ->searchTable('FAC-UNICA-999')
        ->assertCanSeeTableRecords([$paymentRequest])
        ->assertCanNotSeeTableRecords([$other]);
});

it('can create a payment request with full payment type', function () {
    $currency = Currency::factory()->create();
    $branch = Branch::factory()->create();
    $expenseConcept = ExpenseConcept::factory()->create();

    Livewire::test(CreatePaymentRequest::class)
        ->set('data.provider', 'Proveedor Completo')
        ->set('data.invoice_folio', 'FAC-FULL-001')
        ->set('data.currency_id', $currency->id)
        ->set('data.branch_id', $branch->id)
        ->set('data.expense_concept_id', $expenseConcept->id)
        ->set('data.payment_type', PaymentType::Full->value)
        ->set('data.subtotal', 1000.00)
        ->set('data.iva', 160.00)
        ->set('data.retention', 0)
        ->set('data.total', 1160.00)
        ->set('data.status', PaymentRequestStatus::Pending->value)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('payment_requests', [
        'invoice_folio' => 'FAC-FULL-001',
        'payment_type' => PaymentType::Full->value,
    ]);
});

it('can create a payment request with advance payment type', function () {
    $currency = Currency::factory()->create();
    $branch = Branch::factory()->create();
    $expenseConcept = ExpenseConcept::factory()->create();

    Livewire::test(CreatePaymentRequest::class)
        ->set('data.provider', 'Proveedor Anticipo')
        ->set('data.invoice_folio', 'FAC-ADV-001')
        ->set('data.currency_id', $currency->id)
        ->set('data.branch_id', $branch->id)
        ->set('data.expense_concept_id', $expenseConcept->id)
        ->set('data.payment_type', PaymentType::Advance->value)
        ->set('data.subtotal', 5000.00)
        ->set('data.iva', 800.00)
        ->set('data.retention', 0)
        ->set('data.total', 5800.00)
        ->set('data.status', PaymentRequestStatus::Pending->value)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('payment_requests', [
        'invoice_folio' => 'FAC-ADV-001',
        'payment_type' => PaymentType::Advance->value,
    ]);
});

it('requires payment type to be selected', function () {
    Livewire::test(CreatePaymentRequest::class)
        ->assertSet('data.payment_type', null);
});

it('validates payment type is required', function () {
    $currency = Currency::factory()->create();
    $branch = Branch::factory()->create();
    $expenseConcept = ExpenseConcept::factory()->create();

    Livewire::test(CreatePaymentRequest::class)
        ->set('data.provider', 'Proveedor Test')
        ->set('data.invoice_folio', 'FAC-0001')
        ->set('data.currency_id', $currency->id)
        ->set('data.branch_id', $branch->id)
        ->set('data.expense_concept_id', $expenseConcept->id)
        ->set('data.payment_type', null)
        ->set('data.subtotal', 1000.00)
        ->set('data.iva', 160.00)
        ->set('data.retention', 0)
        ->set('data.total', 1160.00)
        ->set('data.status', PaymentRequestStatus::Pending->value)
        ->call('create')
        ->assertHasFormErrors(['payment_type' => 'required']);
});

it('can edit payment type on a payment request', function () {
    $paymentRequest = PaymentRequest::factory()->create([
        'payment_type' => PaymentType::Full,
    ]);

    Livewire::test(EditPaymentRequest::class, ['record' => $paymentRequest->getRouteKey()])
        ->set('data.payment_type', PaymentType::Advance->value)
        ->call('save')
        ->assertHasNoFormErrors();

    $paymentRequest->refresh();
    expect($paymentRequest->payment_type)->toBe(PaymentType::Advance);
});
