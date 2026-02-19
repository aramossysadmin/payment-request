<?php

namespace Database\Factories;

use App\Enums\PaymentType;
use App\Models\Branch;
use App\Models\Currency;
use App\Models\Department;
use App\Models\ExpenseConcept;
use App\Models\User;
use App\States\PaymentRequest\PendingDepartment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentRequest>
 */
class PaymentRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 50000);
        $iva = round($subtotal * 0.16, 2);
        $retention = fake()->boolean(30) ? round($subtotal * 0.0125, 2) : 0;
        $total = round($subtotal + $iva - $retention, 2);

        return [
            'user_id' => User::factory(),
            'department_id' => Department::factory(),
            'folio_number' => fake()->unique()->numberBetween(1, 99999),
            'provider' => fake()->company(),
            'invoice_folio' => fake()->unique()->bothify('FAC-####-??'),
            'currency_id' => Currency::factory(),
            'branch_id' => Branch::factory(),
            'expense_concept_id' => ExpenseConcept::factory(),
            'description' => fake()->optional()->sentence(),
            'payment_type' => PaymentType::Full,
            'status' => PendingDepartment::$name,
            'subtotal' => $subtotal,
            'iva' => $iva,
            'retention' => $retention,
            'total' => $total,
        ];
    }
}
