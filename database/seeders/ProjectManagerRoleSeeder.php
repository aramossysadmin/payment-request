<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class ProjectManagerRoleSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => 'project_manager', 'guard_name' => 'web']);

        $yazmin = User::where('email', 'yazmin.rueda@grupocosteno.com')->first();

        if ($yazmin && ! $yazmin->hasRole('project_manager')) {
            $yazmin->assignRole($role);
        }
    }
}
