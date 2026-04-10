<?php

namespace Database\Factories;

use App\Models\PaymentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentType>
 */
class PaymentTypeFactory extends Factory
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
            'slug' => fake()->unique()->slug(2),
            'invoice_documents_mode' => 'disabled',
            'additional_documents_mode' => 'optional',
            'is_active' => true,
        ];
    }
}
