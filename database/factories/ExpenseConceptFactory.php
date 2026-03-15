<?php

namespace Database\Factories;

use App\Models\ExpenseConcept;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseConcept>
 */
class ExpenseConceptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'is_active' => true,
        ];
    }
}
