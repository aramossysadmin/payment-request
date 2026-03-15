<?php

use App\Filament\Resources\DepartmentResource\Pages\CreateDepartment;
use App\Filament\Resources\DepartmentResource\Pages\EditDepartment;
use App\Filament\Resources\DepartmentResource\Pages\ListDepartments;
use App\Models\Department;
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
    Livewire::test(ListDepartments::class)
        ->assertSuccessful();
});

it('can list departments', function () {
    $departments = Department::factory()->count(3)->create();

    Livewire::test(ListDepartments::class)
        ->assertCanSeeTableRecords($departments);
});

it('can render the create page', function () {
    Livewire::test(CreateDepartment::class)
        ->assertSuccessful();
});

it('can create a department', function () {
    Livewire::test(CreateDepartment::class)
        ->set('data.name', 'Contabilidad')
        ->set('data.description', 'Departamento de contabilidad')
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('departments', [
        'name' => 'CONTABILIDAD',
        'description' => 'Departamento de contabilidad',
    ]);
});

it('can create a department without description', function () {
    Livewire::test(CreateDepartment::class)
        ->set('data.name', 'Recursos Humanos')
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('departments', [
        'name' => 'RECURSOS HUMANOS',
        'description' => null,
    ]);
});

it('validates required fields on create', function () {
    Livewire::test(CreateDepartment::class)
        ->fillForm([
            'name' => '',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'name' => 'required',
        ]);
});

it('validates unique name on create', function () {
    Department::factory()->create(['name' => 'FINANZAS']);

    Livewire::test(CreateDepartment::class)
        ->set('data.name', 'FINANZAS')
        ->call('create')
        ->assertHasFormErrors(['name']);
});

it('can render the edit page', function () {
    $department = Department::factory()->create();

    Livewire::test(EditDepartment::class, ['record' => $department->getRouteKey()])
        ->assertSuccessful();
});

it('can edit a department', function () {
    $department = Department::factory()->create();

    Livewire::test(EditDepartment::class, ['record' => $department->getRouteKey()])
        ->set('data.name', 'Departamento Editado')
        ->set('data.description', 'Descripción editada')
        ->call('save')
        ->assertHasNoFormErrors();

    $department->refresh();
    expect($department->name)->toBe('DEPARTAMENTO EDITADO');
    expect($department->description)->toBe('Descripción editada');
});

it('can soft delete a department', function () {
    $department = Department::factory()->create();

    Livewire::test(ListDepartments::class)
        ->callTableAction('delete', $department);

    $this->assertSoftDeleted('departments', ['id' => $department->id]);
});

it('can restore a soft deleted department', function () {
    $department = Department::factory()->create();
    $department->delete();

    Livewire::test(EditDepartment::class, ['record' => $department->getRouteKey()])
        ->callAction('restore');

    $department->refresh();
    expect($department->deleted_at)->toBeNull();
});

it('can search departments by name', function () {
    $department = Department::factory()->create(['name' => 'LOGISTICA']);
    $other = Department::factory()->create(['name' => 'MARKETING']);

    Livewire::test(ListDepartments::class)
        ->searchTable('LOGISTICA')
        ->assertCanSeeTableRecords([$department])
        ->assertCanNotSeeTableRecords([$other]);
});

it('can create a department with authorizers', function () {
    $authorizers = User::factory()->count(2)->create(['is_active' => true]);

    Livewire::test(CreateDepartment::class)
        ->set('data.name', 'Compras')
        ->set('data.authorizers', $authorizers->pluck('id')->toArray())
        ->call('create')
        ->assertHasNoFormErrors();

    $department = Department::where('name', 'COMPRAS')->first();
    expect($department->authorizers)->toHaveCount(2);
    expect($department->authorizers->pluck('id')->toArray())
        ->toEqualCanonicalizing($authorizers->pluck('id')->toArray());
});

it('can edit authorizers on a department', function () {
    $department = Department::factory()->create();
    $oldAuthorizer = User::factory()->create(['is_active' => true]);
    $department->authorizers()->attach($oldAuthorizer);

    $newAuthorizers = User::factory()->count(2)->create(['is_active' => true]);

    Livewire::test(EditDepartment::class, ['record' => $department->getRouteKey()])
        ->set('data.authorizers', $newAuthorizers->pluck('id')->toArray())
        ->call('save')
        ->assertHasNoFormErrors();

    $department->refresh();
    expect($department->authorizers)->toHaveCount(2);
    expect($department->authorizers->pluck('id')->toArray())
        ->toEqualCanonicalizing($newAuthorizers->pluck('id')->toArray());
});

it('can create a department without authorizers', function () {
    Livewire::test(CreateDepartment::class)
        ->set('data.name', 'Departamento Sin Autorizadores')
        ->call('create')
        ->assertHasNoFormErrors();

    $department = Department::where('name', 'DEPARTAMENTO SIN AUTORIZADORES')->first();
    expect($department->authorizers)->toHaveCount(0);
});

it('only shows active users as authorizer options', function () {
    User::factory()->create(['name' => 'Usuario Activo', 'is_active' => true]);
    User::factory()->create(['name' => 'Usuario Inactivo', 'is_active' => false]);

    Livewire::test(CreateDepartment::class)
        ->assertFormFieldExists('authorizers', function ($field) {
            $options = $field->getOptions();

            $hasActive = collect($options)->contains('Usuario Activo');
            $hasInactive = collect($options)->contains('Usuario Inactivo');

            return $hasActive && ! $hasInactive;
        });
});
