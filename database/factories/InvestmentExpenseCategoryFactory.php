<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\InvestmentExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvestmentExpenseCategory>
 */
class InvestmentExpenseCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'department_id' => Department::factory(),
            'is_active' => true,
        ];
    }
}
