<?php

use App\Filament\Resources\PositionResource\Pages\CreatePosition;
use App\Filament\Resources\PositionResource\Pages\EditPosition;
use App\Filament\Resources\PositionResource\Pages\ListPositions;
use App\Models\Position;
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
    Livewire::test(ListPositions::class)
        ->assertSuccessful();
});

it('can list positions', function () {
    $positions = Position::factory()->count(3)->create();

    Livewire::test(ListPositions::class)
        ->assertCanSeeTableRecords($positions);
});

it('can render the create page', function () {
    Livewire::test(CreatePosition::class)
        ->assertSuccessful();
});

it('can create a position', function () {
    Livewire::test(CreatePosition::class)
        ->set('data.name', 'Gerente de Ventas')
        ->set('data.description', 'Responsable del área de ventas')
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('positions', [
        'name' => 'Gerente de Ventas',
        'description' => 'Responsable del área de ventas',
    ]);
});

it('can create a position without description', function () {
    Livewire::test(CreatePosition::class)
        ->set('data.name', 'Analista')
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('positions', [
        'name' => 'Analista',
        'description' => null,
    ]);
});

it('validates required fields on create', function () {
    Livewire::test(CreatePosition::class)
        ->fillForm([
            'name' => '',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'name' => 'required',
        ]);
});

it('validates unique name on create', function () {
    Position::factory()->create(['name' => 'Director']);

    Livewire::test(CreatePosition::class)
        ->set('data.name', 'Director')
        ->call('create')
        ->assertHasFormErrors(['name']);
});

it('can render the edit page', function () {
    $position = Position::factory()->create();

    Livewire::test(EditPosition::class, ['record' => $position->getRouteKey()])
        ->assertSuccessful();
});

it('can edit a position', function () {
    $position = Position::factory()->create();

    Livewire::test(EditPosition::class, ['record' => $position->getRouteKey()])
        ->set('data.name', 'Puesto Editado')
        ->set('data.description', 'Descripción editada')
        ->call('save')
        ->assertHasNoFormErrors();

    $position->refresh();
    expect($position->name)->toBe('Puesto Editado');
    expect($position->description)->toBe('Descripción editada');
});

it('can soft delete a position', function () {
    $position = Position::factory()->create();

    Livewire::test(ListPositions::class)
        ->callTableAction('delete', $position);

    $this->assertSoftDeleted('positions', ['id' => $position->id]);
});

it('can restore a soft deleted position', function () {
    $position = Position::factory()->create();
    $position->delete();

    Livewire::test(EditPosition::class, ['record' => $position->getRouteKey()])
        ->callAction('restore');

    $position->refresh();
    expect($position->deleted_at)->toBeNull();
});

it('can search positions by name', function () {
    $position = Position::factory()->create(['name' => 'Contador']);
    $other = Position::factory()->create(['name' => 'Abogado']);

    Livewire::test(ListPositions::class)
        ->searchTable('Contador')
        ->assertCanSeeTableRecords([$position])
        ->assertCanNotSeeTableRecords([$other]);
});
