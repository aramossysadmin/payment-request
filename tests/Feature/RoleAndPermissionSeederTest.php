<?php

use Database\Seeders\RoleAndPermissionSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
});

it('creates the three roles', function () {
    $this->artisan('db:seed', ['--class' => RoleAndPermissionSeeder::class, '--no-interaction' => true]);

    expect(Role::where('name', 'Colaborador')->exists())->toBeTrue()
        ->and(Role::where('name', 'Gerente')->exists())->toBeTrue()
        ->and(Role::where('name', 'Director')->exists())->toBeTrue();
});

it('assigns correct permissions to Colaborador', function () {
    $this->artisan('db:seed', ['--class' => RoleAndPermissionSeeder::class, '--no-interaction' => true]);

    $role = Role::findByName('Colaborador', 'web');
    $permissions = $role->permissions->pluck('name')->sort()->values()->all();

    expect($permissions)->toBe([
        'create_payment::request',
        'update_payment::request',
        'view_any_payment::request',
        'view_payment::request',
    ]);
});

it('assigns correct permissions to Gerente', function () {
    $this->artisan('db:seed', ['--class' => RoleAndPermissionSeeder::class, '--no-interaction' => true]);

    $role = Role::findByName('Gerente', 'web');
    $permissions = $role->permissions->pluck('name')->sort()->values()->all();

    expect($permissions)->toBe([
        'create_branch',
        'create_expense::concept',
        'create_payment::request',
        'create_society',
        'update_branch',
        'update_expense::concept',
        'update_payment::request',
        'update_society',
        'view_any_branch',
        'view_any_expense::concept',
        'view_any_payment::request',
        'view_any_society',
        'view_branch',
        'view_expense::concept',
        'view_payment::request',
        'view_society',
    ]);
});

it('assigns correct permissions to Director', function () {
    $this->artisan('db:seed', ['--class' => RoleAndPermissionSeeder::class, '--no-interaction' => true]);

    $role = Role::findByName('Director', 'web');
    $permissions = $role->permissions->pluck('name')->sort()->values()->all();

    $gerentePermissions = Role::findByName('Gerente', 'web')
        ->permissions->pluck('name')->sort()->values()->all();

    expect($permissions)->toBe($gerentePermissions);
});

it('is idempotent', function () {
    $this->artisan('db:seed', ['--class' => RoleAndPermissionSeeder::class, '--no-interaction' => true]);
    $this->artisan('db:seed', ['--class' => RoleAndPermissionSeeder::class, '--no-interaction' => true]);

    expect(Role::where('name', 'Colaborador')->count())->toBe(1)
        ->and(Role::where('name', 'Gerente')->count())->toBe(1)
        ->and(Role::where('name', 'Director')->count())->toBe(1)
        ->and(Role::findByName('Colaborador', 'web')->permissions)->toHaveCount(4)
        ->and(Role::findByName('Gerente', 'web')->permissions)->toHaveCount(16)
        ->and(Role::findByName('Director', 'web')->permissions)->toHaveCount(16);
});
