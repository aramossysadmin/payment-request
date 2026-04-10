<?php

namespace Database\Factories;

use App\Models\InvestmentRequest;
use App\Models\InvestmentRequestApproval;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InvestmentRequestApproval>
 */
class InvestmentRequestApprovalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'investment_request_id' => InvestmentRequest::factory(),
            'user_id' => User::factory(),
            'stage' => 'department',
            'level' => 1,
            'status' => 'pending',
            'comments' => null,
            'responded_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'responded_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'comments' => fake()->sentence(),
            'responded_at' => now(),
        ]);
    }

    public function withToken(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_token' => Str::uuid()->toString(),
            'approval_token_expires_at' => now()->addHours(48),
        ]);
    }

    public function withExpiredToken(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_token' => Str::uuid()->toString(),
            'approval_token_expires_at' => now()->subHour(),
        ]);
    }
}
