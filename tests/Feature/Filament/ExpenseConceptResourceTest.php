<?php

use App\Filament\Resources\ExpenseConceptResource\Pages\CreateExpenseConcept;
use App\Filament\Resources\ExpenseConceptResource\Pages\EditExpenseConcept;
use App\Filament\Resources\ExpenseConceptResource\Pages\ListExpenseConcepts;
use App\Models\ExpenseConcept;
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
    Livewire::test(ListExpenseConcepts::class)
        ->assertSuccessful();
});

it('can list expense concepts', function () {
    $expenseConcepts = ExpenseConcept::factory()->count(3)->create();

    Livewire::test(ListExpenseConcepts::class)
        ->assertCanSeeTableRecords($expenseConcepts);
});

it('can render the create page', function () {
    Livewire::test(CreateExpenseConcept::class)
        ->assertSuccessful();
});

it('can create an expense concept', function () {
    Livewire::test(CreateExpenseConcept::class)
        ->set('data.name', 'Viáticos')
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('expense_concepts', [
        'name' => 'VIÁTICOS',
        'is_active' => true,
    ]);
});

it('normalizes name to uppercase and trimmed on create', function () {
    Livewire::test(CreateExpenseConcept::class)
        ->set('data.name', '  papelería  ')
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('expense_concepts', [
        'name' => 'PAPELERÍA',
    ]);
});

it('validates required fields on create', function () {
    Livewire::test(CreateExpenseConcept::class)
        ->fillForm([
            'name' => '',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'name' => 'required',
        ]);
});

it('validates unique name on create', function () {
    ExpenseConcept::factory()->create(['name' => 'TRANSPORTE']);

    Livewire::test(CreateExpenseConcept::class)
        ->set('data.name', 'TRANSPORTE')
        ->call('create')
        ->assertHasFormErrors(['name']);
});

it('can render the edit page', function () {
    $expenseConcept = ExpenseConcept::factory()->create();

    Livewire::test(EditExpenseConcept::class, ['record' => $expenseConcept->getRouteKey()])
        ->assertSuccessful();
});

it('can edit an expense concept', function () {
    $expenseConcept = ExpenseConcept::factory()->create();

    Livewire::test(EditExpenseConcept::class, ['record' => $expenseConcept->getRouteKey()])
        ->set('data.name', 'Concepto Editado')
        ->call('save')
        ->assertHasNoFormErrors();

    $expenseConcept->refresh();
    expect($expenseConcept->name)->toBe('CONCEPTO EDITADO');
});

it('can soft delete an expense concept', function () {
    $expenseConcept = ExpenseConcept::factory()->create();

    Livewire::test(ListExpenseConcepts::class)
        ->callTableAction('delete', $expenseConcept);

    $this->assertSoftDeleted('expense_concepts', ['id' => $expenseConcept->id]);
});

it('can restore a soft deleted expense concept', function () {
    $expenseConcept = ExpenseConcept::factory()->create();
    $expenseConcept->delete();

    Livewire::test(EditExpenseConcept::class, ['record' => $expenseConcept->getRouteKey()])
        ->callAction('restore');

    $expenseConcept->refresh();
    expect($expenseConcept->deleted_at)->toBeNull();
});

it('can search expense concepts by name', function () {
    $expenseConcept = ExpenseConcept::factory()->create(['name' => 'HOSPEDAJE']);
    $other = ExpenseConcept::factory()->create(['name' => 'ALIMENTACION']);

    Livewire::test(ListExpenseConcepts::class)
        ->searchTable('HOSPEDAJE')
        ->assertCanSeeTableRecords([$expenseConcept])
        ->assertCanNotSeeTableRecords([$other]);
});

it('creates expense concept with is_active true by default', function () {
    Livewire::test(CreateExpenseConcept::class)
        ->set('data.name', 'NUEVO CONCEPTO')
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('expense_concepts', [
        'name' => 'NUEVO CONCEPTO',
        'is_active' => true,
    ]);
});

it('can toggle expense concept active status', function () {
    $expenseConcept = ExpenseConcept::factory()->create(['is_active' => true]);

    Livewire::test(ListExpenseConcepts::class)
        ->callTableAction('toggleActive', $expenseConcept);

    $expenseConcept->refresh();
    expect($expenseConcept->is_active)->toBeFalse();
});

it('can filter by active status', function () {
    $active = ExpenseConcept::factory()->create(['is_active' => true]);
    $inactive = ExpenseConcept::factory()->create(['is_active' => false]);

    Livewire::test(ListExpenseConcepts::class)
        ->filterTable('is_active', true)
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$inactive]);
});

it('can filter by inactive status', function () {
    $active = ExpenseConcept::factory()->create(['is_active' => true]);
    $inactive = ExpenseConcept::factory()->create(['is_active' => false]);

    Livewire::test(ListExpenseConcepts::class)
        ->filterTable('is_active', false)
        ->assertCanSeeTableRecords([$inactive])
        ->assertCanNotSeeTableRecords([$active]);
});
