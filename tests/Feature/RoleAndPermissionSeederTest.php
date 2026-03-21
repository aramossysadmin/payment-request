<?php

use Database\Seeders\RoleAndPermissionSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
});

it('creates the three roles', function () {
    $this->artisan('db:seed', ['--class' => RoleAndPermissionSeeder::class, '--no-interaction' => true]);

    expect(Role::where('name', 'super_admin')->exists())->toBeTrue()
        ->and(Role::where('name', 'admin_sp')->exists())->toBeTrue()
        ->and(Role::where('name', 'administrador')->exists())->toBeTrue();
});

it('assigns correct permissions to administrador', function () {
    $this->artisan('db:seed', ['--class' => RoleAndPermissionSeeder::class, '--no-interaction' => true]);

    $role = Role::findByName('administrador', 'web');
    $permissions = $role->permissions->pluck('name')->sort()->values()->all();

    expect($permissions)->toBe([
        'create_payment::request',
        'delete_any_payment::request',
        'delete_payment::request',
        'force_delete_any_payment::request',
        'force_delete_payment::request',
        'reorder_payment::request',
        'replicate_payment::request',
        'restore_any_payment::request',
        'restore_payment::request',
        'update_payment::request',
        'view_any_payment::request',
        'view_payment::request',
    ]);
});

it('assigns all permissions to admin_sp', function () {
    $this->artisan('db:seed', ['--class' => RoleAndPermissionSeeder::class, '--no-interaction' => true]);

    $role = Role::findByName('admin_sp', 'web');

    expect($role->permissions)->toHaveCount(114);
});

it('assigns all permissions to super_admin', function () {
    $this->artisan('db:seed', ['--class' => RoleAndPermissionSeeder::class, '--no-interaction' => true]);

    $role = Role::findByName('super_admin', 'web');

    expect($role->permissions)->toHaveCount(114);
});

it('is idempotent', function () {
    $this->artisan('db:seed', ['--class' => RoleAndPermissionSeeder::class, '--no-interaction' => true]);
    $this->artisan('db:seed', ['--class' => RoleAndPermissionSeeder::class, '--no-interaction' => true]);

    expect(Role::where('name', 'super_admin')->count())->toBe(1)
        ->and(Role::where('name', 'admin_sp')->count())->toBe(1)
        ->and(Role::where('name', 'administrador')->count())->toBe(1)
        ->and(Role::findByName('super_admin', 'web')->permissions)->toHaveCount(114)
        ->and(Role::findByName('admin_sp', 'web')->permissions)->toHaveCount(114)
        ->and(Role::findByName('administrador', 'web')->permissions)->toHaveCount(12);
});
