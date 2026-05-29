<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class CeoRoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'ceo', 'guard_name' => 'web']);
    }
}
