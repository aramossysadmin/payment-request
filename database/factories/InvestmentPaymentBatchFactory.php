<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\InvestmentPaymentBatch;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InvestmentPaymentBatch>
 */
class InvestmentPaymentBatchFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'department_id' => Department::factory(),
            'user_id' => User::factory(),
            'project_id' => Project::factory(),
            'week_number' => fake()->numberBetween(1, 53),
            'year' => (int) date('Y'),
            'status' => 'draft',
        ];
    }
}
