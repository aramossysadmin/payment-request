<?php

use App\Filament\Resources\SocietyResource\Pages\CreateSociety;
use App\Filament\Resources\SocietyResource\Pages\EditSociety;
use App\Filament\Resources\SocietyResource\Pages\ListSocieties;
use App\Models\Society;
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
    Livewire::test(ListSocieties::class)
        ->assertSuccessful();
});

it('can list societies', function () {
    $societies = Society::factory()->count(3)->create();

    Livewire::test(ListSocieties::class)
        ->assertCanSeeTableRecords($societies);
});

it('can render the create page', function () {
    Livewire::test(CreateSociety::class)
        ->assertSuccessful();
});

it('can create a society', function () {
    Livewire::test(CreateSociety::class)
        ->set('data.name', 'Sociedad Alpha')
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('societies', [
        'name' => 'SOCIEDAD ALPHA',
    ]);
});

it('validates required fields on create', function () {
    Livewire::test(CreateSociety::class)
        ->fillForm([
            'name' => '',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'name' => 'required',
        ]);
});

it('validates unique name on create', function () {
    Society::factory()->create(['name' => 'SOCIEDAD UNICA']);

    Livewire::test(CreateSociety::class)
        ->set('data.name', 'SOCIEDAD UNICA')
        ->call('create')
        ->assertHasFormErrors(['name']);
});

it('can render the edit page', function () {
    $society = Society::factory()->create();

    Livewire::test(EditSociety::class, ['record' => $society->getRouteKey()])
        ->assertSuccessful();
});

it('can edit a society', function () {
    $society = Society::factory()->create();

    Livewire::test(EditSociety::class, ['record' => $society->getRouteKey()])
        ->set('data.name', 'Sociedad Editada')
        ->call('save')
        ->assertHasNoFormErrors();

    $society->refresh();
    expect($society->name)->toBe('SOCIEDAD EDITADA');
});

it('can soft delete a society', function () {
    $society = Society::factory()->create();

    Livewire::test(ListSocieties::class)
        ->callTableAction('delete', $society);

    $this->assertSoftDeleted('societies', ['id' => $society->id]);
});

it('can restore a soft deleted society', function () {
    $society = Society::factory()->create();
    $society->delete();

    Livewire::test(EditSociety::class, ['record' => $society->getRouteKey()])
        ->callAction('restore');

    $society->refresh();
    expect($society->deleted_at)->toBeNull();
});

it('can search societies by name', function () {
    $society = Society::factory()->create(['name' => 'SOCIEDAD BUSCABLE']);
    $other = Society::factory()->create(['name' => 'OTRA SOCIEDAD']);

    Livewire::test(ListSocieties::class)
        ->searchTable('SOCIEDAD BUSCABLE')
        ->assertCanSeeTableRecords([$society])
        ->assertCanNotSeeTableRecords([$other]);
});
