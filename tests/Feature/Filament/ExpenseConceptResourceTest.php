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
        'name' => 'Viáticos',
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
    ExpenseConcept::factory()->create(['name' => 'Transporte']);

    Livewire::test(CreateExpenseConcept::class)
        ->set('data.name', 'Transporte')
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
    expect($expenseConcept->name)->toBe('Concepto Editado');
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
    $expenseConcept = ExpenseConcept::factory()->create(['name' => 'Hospedaje']);
    $other = ExpenseConcept::factory()->create(['name' => 'Alimentación']);

    Livewire::test(ListExpenseConcepts::class)
        ->searchTable('Hospedaje')
        ->assertCanSeeTableRecords([$expenseConcept])
        ->assertCanNotSeeTableRecords([$other]);
});
