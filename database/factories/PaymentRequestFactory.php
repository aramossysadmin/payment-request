<?php

namespace Database\Factories;

use App\Enums\IvaRate;
use App\Models\Branch;
use App\Models\Currency;
use App\Models\Department;
use App\Models\ExpenseConcept;
use App\Models\PaymentRequest;
use App\Models\PaymentType;
use App\Models\User;
use App\States\PaymentRequest\PendingDepartment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentRequest>
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
        $ivaRate = fake()->randomElement(IvaRate::cases());
        $iva = round($subtotal * $ivaRate->rate(), 2);
        $retention = fake()->boolean(30);
        $total = round($subtotal + $iva, 2);

        return [
            'uuid' => fake()->uuid(),
            'user_id' => User::factory(),
            'department_id' => Department::factory(),
            'folio_number' => fake()->unique()->numberBetween(1, 99999),
            'provider' => fake()->company(),
            'invoice_folio' => fake()->unique()->bothify('FAC-####-??'),
            'currency_id' => Currency::factory(),
            'branch_id' => Branch::factory(),
            'expense_concept_id' => ExpenseConcept::factory(),
            'description' => fake()->optional()->sentence(),
            'payment_type_id' => PaymentType::factory(),
            'status' => PendingDepartment::$name,
            'subtotal' => $subtotal,
            'iva_rate' => $ivaRate->value,
            'iva' => $iva,
            'retention' => $retention,
            'total' => $total,
        ];
    }
}
