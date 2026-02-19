<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * @var array<string, list<string>>
     */
    private const ROLE_PERMISSIONS = [
        'Colaborador' => [
            'view_any_payment::request',
            'view_payment::request',
            'create_payment::request',
            'update_payment::request',
        ],
        'Gerente' => [
            'view_any_payment::request',
            'view_payment::request',
            'create_payment::request',
            'update_payment::request',
            'view_any_society',
            'view_society',
            'create_society',
            'update_society',
            'view_any_branch',
            'view_branch',
            'create_branch',
            'update_branch',
            'view_any_expense::concept',
            'view_expense::concept',
            'create_expense::concept',
            'update_expense::concept',
        ],
        'Director' => [
            'view_any_payment::request',
            'view_payment::request',
            'create_payment::request',
            'update_payment::request',
            'view_any_society',
            'view_society',
            'create_society',
            'update_society',
            'view_any_branch',
            'view_branch',
            'create_branch',
            'update_branch',
            'view_any_expense::concept',
            'view_expense::concept',
            'create_expense::concept',
            'update_expense::concept',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

            foreach ($permissions as $permissionName) {
                Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
            }

            $role->syncPermissions($permissions);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
