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
        'super_admin' => [
            'view_any_branch', 'view_branch', 'create_branch', 'update_branch',
            'delete_branch', 'delete_any_branch', 'restore_branch', 'restore_any_branch',
            'replicate_branch', 'reorder_branch', 'force_delete_branch', 'force_delete_any_branch',
            'view_any_currency', 'view_currency', 'create_currency', 'update_currency',
            'delete_currency', 'delete_any_currency', 'restore_currency', 'restore_any_currency',
            'replicate_currency', 'reorder_currency', 'force_delete_currency', 'force_delete_any_currency',
            'view_any_department', 'view_department', 'create_department', 'update_department',
            'delete_department', 'delete_any_department', 'restore_department', 'restore_any_department',
            'replicate_department', 'reorder_department', 'force_delete_department', 'force_delete_any_department',
            'view_any_expense::concept', 'view_expense::concept', 'create_expense::concept', 'update_expense::concept',
            'delete_expense::concept', 'delete_any_expense::concept', 'restore_expense::concept', 'restore_any_expense::concept',
            'replicate_expense::concept', 'reorder_expense::concept', 'force_delete_expense::concept', 'force_delete_any_expense::concept',
            'view_any_payment::request', 'view_payment::request', 'create_payment::request', 'update_payment::request',
            'delete_payment::request', 'delete_any_payment::request', 'restore_payment::request', 'restore_any_payment::request',
            'replicate_payment::request', 'reorder_payment::request', 'force_delete_payment::request', 'force_delete_any_payment::request',
            'view_any_payment::type', 'view_payment::type', 'create_payment::type', 'update_payment::type',
            'delete_payment::type', 'delete_any_payment::type', 'restore_payment::type', 'restore_any_payment::type',
            'replicate_payment::type', 'reorder_payment::type', 'force_delete_payment::type', 'force_delete_any_payment::type',
            'view_any_position', 'view_position', 'create_position', 'update_position',
            'delete_position', 'delete_any_position', 'restore_position', 'restore_any_position',
            'replicate_position', 'reorder_position', 'force_delete_position', 'force_delete_any_position',
            'view_any_role', 'view_role', 'create_role', 'update_role',
            'delete_role', 'delete_any_role',
            'view_any_society', 'view_society', 'create_society', 'update_society',
            'delete_society', 'delete_any_society', 'restore_society', 'restore_any_society',
            'replicate_society', 'reorder_society', 'force_delete_society', 'force_delete_any_society',
            'view_any_user', 'view_user', 'create_user', 'update_user',
            'delete_user', 'delete_any_user', 'restore_user', 'restore_any_user',
            'replicate_user', 'reorder_user', 'force_delete_user', 'force_delete_any_user',
        ],
        'admin_sp' => [
            'view_any_branch', 'view_branch', 'create_branch', 'update_branch',
            'delete_branch', 'delete_any_branch', 'restore_branch', 'restore_any_branch',
            'replicate_branch', 'reorder_branch', 'force_delete_branch', 'force_delete_any_branch',
            'view_any_currency', 'view_currency', 'create_currency', 'update_currency',
            'delete_currency', 'delete_any_currency', 'restore_currency', 'restore_any_currency',
            'replicate_currency', 'reorder_currency', 'force_delete_currency', 'force_delete_any_currency',
            'view_any_department', 'view_department', 'create_department', 'update_department',
            'delete_department', 'delete_any_department', 'restore_department', 'restore_any_department',
            'replicate_department', 'reorder_department', 'force_delete_department', 'force_delete_any_department',
            'view_any_expense::concept', 'view_expense::concept', 'create_expense::concept', 'update_expense::concept',
            'delete_expense::concept', 'delete_any_expense::concept', 'restore_expense::concept', 'restore_any_expense::concept',
            'replicate_expense::concept', 'reorder_expense::concept', 'force_delete_expense::concept', 'force_delete_any_expense::concept',
            'view_any_payment::request', 'view_payment::request', 'create_payment::request', 'update_payment::request',
            'delete_payment::request', 'delete_any_payment::request', 'restore_payment::request', 'restore_any_payment::request',
            'replicate_payment::request', 'reorder_payment::request', 'force_delete_payment::request', 'force_delete_any_payment::request',
            'view_any_payment::type', 'view_payment::type', 'create_payment::type', 'update_payment::type',
            'delete_payment::type', 'delete_any_payment::type', 'restore_payment::type', 'restore_any_payment::type',
            'replicate_payment::type', 'reorder_payment::type', 'force_delete_payment::type', 'force_delete_any_payment::type',
            'view_any_position', 'view_position', 'create_position', 'update_position',
            'delete_position', 'delete_any_position', 'restore_position', 'restore_any_position',
            'replicate_position', 'reorder_position', 'force_delete_position', 'force_delete_any_position',
            'view_any_role', 'view_role', 'create_role', 'update_role',
            'delete_role', 'delete_any_role',
            'view_any_society', 'view_society', 'create_society', 'update_society',
            'delete_society', 'delete_any_society', 'restore_society', 'restore_any_society',
            'replicate_society', 'reorder_society', 'force_delete_society', 'force_delete_any_society',
            'view_any_user', 'view_user', 'create_user', 'update_user',
            'delete_user', 'delete_any_user', 'restore_user', 'restore_any_user',
            'replicate_user', 'reorder_user', 'force_delete_user', 'force_delete_any_user',
        ],
        'administrador' => [
            'view_any_payment::request', 'view_payment::request', 'create_payment::request', 'update_payment::request',
            'delete_payment::request', 'delete_any_payment::request', 'restore_payment::request', 'restore_any_payment::request',
            'replicate_payment::request', 'reorder_payment::request', 'force_delete_payment::request', 'force_delete_any_payment::request',
        ],
    ];

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
