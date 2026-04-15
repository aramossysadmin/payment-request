<?php

namespace Database\Factories;

use App\Models\InvestmentExpenseCategory;
use App\Models\InvestmentExpenseConcept;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvestmentExpenseConcept>
 */
class InvestmentExpenseConceptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'investment_expense_category_id' => InvestmentExpenseCategory::factory(),
            'is_active' => true,
        ];
    }
}
