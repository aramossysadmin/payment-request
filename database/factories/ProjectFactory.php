<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'branch_id' => Branch::factory(),
            'is_active' => true,
        ];
    }
}
