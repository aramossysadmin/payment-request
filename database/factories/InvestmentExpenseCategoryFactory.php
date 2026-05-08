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
            'is_active' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (InvestmentExpenseCategory $category): void {
            if ($category->departments()->count() === 0) {
                $category->departments()->attach(Department::factory()->create()->id);
            }
        });
    }
}
