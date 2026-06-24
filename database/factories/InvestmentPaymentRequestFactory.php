<?php

namespace Database\Factories;

use App\Enums\InvestmentPaymentType;
use App\Enums\IvaRate;
use App\Models\Branch;
use App\Models\Currency;
use App\Models\Department;
use App\Models\InvestmentPaymentRequest;
use App\Models\InvestmentRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvestmentPaymentRequest>
 */
class InvestmentPaymentRequestFactory extends Factory
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
        $total = round($subtotal + $iva, 2);

        return [
            'investment_request_id' => InvestmentRequest::factory(),
            'user_id' => User::factory(),
            'department_id' => Department::factory(),
            'provider' => fake()->company(),
            'currency_id' => Currency::factory(),
            'branch_id' => Branch::factory(),
            'payment_type' => InvestmentPaymentType::Anticipo->value,
            'status' => 'pending_approval',
            'subtotal' => $subtotal,
            'iva_rate' => $ivaRate->value,
            'iva' => $iva,
            'retention' => false,
            'total' => $total,
        ];
    }
}
