<?php

use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\Department;
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
    Livewire::test(ListUsers::class)
        ->assertSuccessful();
});

it('can list users', function () {
    $users = User::factory()->count(3)->create();

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords($users);
});

it('can render the create page', function () {
    Livewire::test(CreateUser::class)
        ->assertSuccessful();
});

it('can create a user', function () {
    $department = Department::factory()->create();
    $position = Position::factory()->create();

    Livewire::test(CreateUser::class)
        ->set('data.name', 'John Doe')
        ->set('data.email', 'john@example.com')
        ->set('data.password', 'password123')
        ->set('data.password_confirmation', 'password123')
        ->set('data.department_id', $department->id)
        ->set('data.position_id', $position->id)
        ->set('data.is_active', true)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'is_active' => true,
        'department_id' => $department->id,
        'position_id' => $position->id,
    ]);
});

it('validates required fields on create', function () {
    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => '',
            'email' => '',
            'password' => '',
            'department_id' => null,
            'position_id' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'name' => 'required',
            'email' => 'required',
            'password' => 'required',
            'department_id' => 'required',
            'position_id' => 'required',
        ]);
});

it('validates unique email on create', function () {
    User::factory()->create(['email' => 'taken@example.com']);
    $department = Department::factory()->create();
    $position = Position::factory()->create();

    Livewire::test(CreateUser::class)
        ->set('data.name', 'Another User')
        ->set('data.email', 'taken@example.com')
        ->set('data.password', 'password123')
        ->set('data.password_confirmation', 'password123')
        ->set('data.department_id', $department->id)
        ->set('data.position_id', $position->id)
        ->call('create')
        ->assertHasFormErrors(['email']);
});

it('can render the edit page', function () {
    $user = User::factory()->create();

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->assertSuccessful();
});

it('can edit a user', function () {
    $user = User::factory()->create();

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->set('data.name', 'Updated Name')
        ->set('data.email', 'updated@example.com')
        ->call('save')
        ->assertHasNoFormErrors();

    $user->refresh();
    expect($user->name)->toBe('Updated Name');
    expect($user->email)->toBe('updated@example.com');
});

it('can edit a user without changing password', function () {
    $user = User::factory()->create();
    $originalPassword = $user->password;

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->set('data.name', 'Updated Name')
        ->set('data.password', '')
        ->set('data.password_confirmation', '')
        ->call('save')
        ->assertHasNoFormErrors();

    $user->refresh();
    expect($user->name)->toBe('Updated Name');
    expect($user->password)->toBe($originalPassword);
});

it('can toggle user active status', function () {
    $user = User::factory()->create(['is_active' => true]);

    Livewire::test(ListUsers::class)
        ->callTableAction('toggleActive', $user);

    $user->refresh();
    expect($user->is_active)->toBeFalse();

    Livewire::test(ListUsers::class)
        ->callTableAction('toggleActive', $user);

    $user->refresh();
    expect($user->is_active)->toBeTrue();
});

it('can soft delete a user', function () {
    $user = User::factory()->create();

    Livewire::test(ListUsers::class)
        ->callTableAction('delete', $user);

    $this->assertSoftDeleted('users', ['id' => $user->id]);
});

it('can restore a soft deleted user', function () {
    $user = User::factory()->create();
    $user->delete();

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->callAction('restore');

    $user->refresh();
    expect($user->deleted_at)->toBeNull();
});

it('prevents inactive user from accessing the panel', function () {
    $user = User::factory()->inactive()->create();

    expect($user->canAccessPanel(filament()->getDefaultPanel()))->toBeFalse();
});

it('allows active user to access the panel', function () {
    $user = User::factory()->create(['is_active' => true]);

    expect($user->canAccessPanel(filament()->getDefaultPanel()))->toBeTrue();
});

it('can assign roles to a user', function () {
    $role = Role::firstOrCreate(['name' => 'panel_user', 'guard_name' => 'web']);
    $department = Department::factory()->create();
    $position = Position::factory()->create();

    Livewire::test(CreateUser::class)
        ->set('data.name', 'Role User')
        ->set('data.email', 'roleuser@example.com')
        ->set('data.password', 'password123')
        ->set('data.password_confirmation', 'password123')
        ->set('data.department_id', $department->id)
        ->set('data.position_id', $position->id)
        ->set('data.is_active', true)
        ->set('data.roles', [$role->id])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::where('email', 'roleuser@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('panel_user'))->toBeTrue();
});

it('can filter users by active status', function () {
    $activeUser = User::factory()->create(['is_active' => true]);
    $inactiveUser = User::factory()->inactive()->create();

    Livewire::test(ListUsers::class)
        ->filterTable('is_active', true)
        ->assertCanSeeTableRecords([$activeUser])
        ->assertCanNotSeeTableRecords([$inactiveUser]);
});

it('can search users by name', function () {
    $user = User::factory()->create(['name' => 'Searchable User']);
    $otherUser = User::factory()->create(['name' => 'Other Person']);

    Livewire::test(ListUsers::class)
        ->searchTable('Searchable User')
        ->assertCanSeeTableRecords([$user])
        ->assertCanNotSeeTableRecords([$otherUser]);
});

it('can search users by email', function () {
    $user = User::factory()->create(['email' => 'findme@example.com']);
    $otherUser = User::factory()->create(['email' => 'notme@example.com']);

    Livewire::test(ListUsers::class)
        ->searchTable('findme@example.com')
        ->assertCanSeeTableRecords([$user])
        ->assertCanNotSeeTableRecords([$otherUser]);
});

it('can edit department on a user', function () {
    $user = User::factory()->create();
    $newDepartment = Department::factory()->create();

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->set('data.department_id', $newDepartment->id)
        ->call('save')
        ->assertHasNoFormErrors();

    $user->refresh();
    expect($user->department_id)->toBe($newDepartment->id);
});

it('does not show soft deleted departments in select', function () {
    $activeDepartment = Department::factory()->create(['name' => 'DEPTO ACTIVO']);
    $deletedDepartment = Department::factory()->create(['name' => 'DEPTO ELIMINADO']);
    $deletedDepartment->delete();

    Livewire::test(CreateUser::class)
        ->assertFormFieldExists('department_id', function ($field) {
            $options = $field->getOptions();

            $hasActive = collect($options)->contains('DEPTO ACTIVO');
            $hasDeleted = collect($options)->contains('DEPTO ELIMINADO');

            return $hasActive && ! $hasDeleted;
        });
});

it('can edit position on a user', function () {
    $user = User::factory()->create();
    $newPosition = Position::factory()->create();

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->set('data.position_id', $newPosition->id)
        ->call('save')
        ->assertHasNoFormErrors();

    $user->refresh();
    expect($user->position_id)->toBe($newPosition->id);
});

it('does not show soft deleted positions in select', function () {
    $activePosition = Position::factory()->create(['name' => 'POSICION ACTIVA']);
    $deletedPosition = Position::factory()->create(['name' => 'POSICION ELIMINADA']);
    $deletedPosition->delete();

    Livewire::test(CreateUser::class)
        ->assertFormFieldExists('position_id', function ($field) {
            $options = $field->getOptions();

            $hasActive = collect($options)->contains('POSICION ACTIVA');
            $hasDeleted = collect($options)->contains('POSICION ELIMINADA');

            return $hasActive && ! $hasDeleted;
        });
});
