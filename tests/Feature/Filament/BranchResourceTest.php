<?php

use App\Filament\Resources\BranchResource\Pages\CreateBranch;
use App\Filament\Resources\BranchResource\Pages\EditBranch;
use App\Filament\Resources\BranchResource\Pages\ListBranches;
use App\Models\Branch;
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
    Livewire::test(ListBranches::class)
        ->assertSuccessful();
});

it('can list branches', function () {
    $branches = Branch::factory()->count(3)->create();

    Livewire::test(ListBranches::class)
        ->assertCanSeeTableRecords($branches);
});

it('can render the create page', function () {
    Livewire::test(CreateBranch::class)
        ->assertSuccessful();
});

it('can create a branch', function () {
    $society = Society::factory()->create();

    Livewire::test(CreateBranch::class)
        ->set('data.name', 'Sucursal Centro')
        ->set('data.society_id', $society->id)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('branches', [
        'name' => 'SUCURSAL CENTRO',
        'society_id' => $society->id,
    ]);
});

it('validates required fields on create', function () {
    Livewire::test(CreateBranch::class)
        ->fillForm([
            'name' => '',
            'society_id' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'name' => 'required',
            'society_id' => 'required',
        ]);
});

it('can render the edit page', function () {
    $branch = Branch::factory()->create();

    Livewire::test(EditBranch::class, ['record' => $branch->getRouteKey()])
        ->assertSuccessful();
});

it('can edit a branch', function () {
    $branch = Branch::factory()->create();
    $newSociety = Society::factory()->create();

    Livewire::test(EditBranch::class, ['record' => $branch->getRouteKey()])
        ->set('data.name', 'Sucursal Editada')
        ->set('data.society_id', $newSociety->id)
        ->call('save')
        ->assertHasNoFormErrors();

    $branch->refresh();
    expect($branch->name)->toBe('SUCURSAL EDITADA');
    expect($branch->society_id)->toBe($newSociety->id);
});

it('can soft delete a branch', function () {
    $branch = Branch::factory()->create();

    Livewire::test(ListBranches::class)
        ->callTableAction('delete', $branch);

    $this->assertSoftDeleted('branches', ['id' => $branch->id]);
});

it('can restore a soft deleted branch', function () {
    $branch = Branch::factory()->create();
    $branch->delete();

    Livewire::test(EditBranch::class, ['record' => $branch->getRouteKey()])
        ->callAction('restore');

    $branch->refresh();
    expect($branch->deleted_at)->toBeNull();
});

it('can search branches by name', function () {
    $branch = Branch::factory()->create(['name' => 'SUCURSAL BUSCABLE']);
    $other = Branch::factory()->create(['name' => 'OTRA SUCURSAL']);

    Livewire::test(ListBranches::class)
        ->searchTable('SUCURSAL BUSCABLE')
        ->assertCanSeeTableRecords([$branch])
        ->assertCanNotSeeTableRecords([$other]);
});

it('can search branches by society name', function () {
    $society = Society::factory()->create(['name' => 'SOCIEDAD ESPECIAL']);
    $branch = Branch::factory()->create(['society_id' => $society->id]);
    $other = Branch::factory()->create();

    Livewire::test(ListBranches::class)
        ->searchTable('SOCIEDAD ESPECIAL')
        ->assertCanSeeTableRecords([$branch])
        ->assertCanNotSeeTableRecords([$other]);
});

it('can filter branches by society', function () {
    $society = Society::factory()->create();
    $branch = Branch::factory()->create(['society_id' => $society->id]);
    $other = Branch::factory()->create();

    Livewire::test(ListBranches::class)
        ->filterTable('society', $society->id)
        ->assertCanSeeTableRecords([$branch])
        ->assertCanNotSeeTableRecords([$other]);
});
