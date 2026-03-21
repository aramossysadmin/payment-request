<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            PositionSeeder::class,
            CurrencySeeder::class,
            SocietySeeder::class,
            BranchSeeder::class,
            PaymentTypeSeeder::class,
            ExpenseConceptSeeder::class,
            RoleAndPermissionSeeder::class,
        ]);

        $user = User::firstOrCreate(
            ['email' => 'alfonso.ramos@grupocosteno.com'],
            [
                'name' => 'Alfonso Ramos',
                'password' => Hash::make('123456789'),
                'is_active' => true,
                'department_id' => Department::where('name', 'SISTEMAS')->first()?->id,
                'position_id' => Position::where('name', 'GERENTE')->first()?->id,
            ],
        );

        $user->assignRole('super_admin');
    }
}
