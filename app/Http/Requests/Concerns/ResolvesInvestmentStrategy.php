<?php

namespace App\Http\Requests\Concerns;

use App\Enums\InvestmentPaymentType;
use App\Enums\IvaRate;
use App\Models\Strategy;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Pagos tipo "estrategia": el backend recalcula Subtotal/IVA/Total desde el Monto Estrategia
 * y el factor del catálogo (fuente de verdad), y congela un snapshot del factor y nombre.
 */
trait ResolvesInvestmentStrategy
{
    protected ?Strategy $resolvedStrategy = null;

    protected function prepareForValidation(): void
    {
        if (! $this->isStrategyPayment()) {
            $this->merge(['strategy_id' => null, 'strategy_amount' => null]);

            return;
        }

        $strategy = Strategy::query()->active()->find($this->input('strategy_id'));
        $ivaRate = IvaRate::tryFrom((string) $this->input('iva_rate'));
        $amount = $this->input('strategy_amount');

        if ($strategy === null || $ivaRate === null || ! is_numeric($amount) || (float) $amount <= 0) {
            return;
        }

        $this->resolvedStrategy = $strategy;
        $this->merge($strategy->amountsFor($amount, $ivaRate));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function strategyRules(): array
    {
        return [
            'strategy_id' => ['nullable', 'required_if:payment_type,'.InvestmentPaymentType::Estrategia->value, 'integer', Rule::exists('strategies', 'id')->whereNull('deleted_at')],
            'strategy_amount' => ['nullable', 'required_if:payment_type,'.InvestmentPaymentType::Estrategia->value, 'numeric', 'min:0.01'],
        ];
    }

    protected function validateStrategyIsActive(Validator $validator): void
    {
        if (! $this->isStrategyPayment() || $validator->errors()->has('strategy_id') || blank($this->input('strategy_id'))) {
            return;
        }

        $strategy = Strategy::query()->find($this->input('strategy_id'));

        if ($strategy !== null && ! $strategy->is_active) {
            $validator->errors()->add('strategy_id', 'La estrategia seleccionada está inactiva. Elige una estrategia activa.');
        }
    }

    /**
     * @return array<string, string>
     */
    protected function strategyMessages(): array
    {
        return [
            'strategy_id.required_if' => 'Selecciona el código de estrategia.',
            'strategy_id.exists' => 'La estrategia seleccionada no existe.',
            'strategy_amount.required_if' => 'Captura el monto de la estrategia.',
            'strategy_amount.numeric' => 'El monto de la estrategia debe ser numérico.',
            'strategy_amount.min' => 'El monto de la estrategia debe ser mayor a 0.',
        ];
    }

    /**
     * Atributos a persistir en la solicitud: vínculo + snapshot inmutable del factor y nombre.
     *
     * @return array{strategy_id: int|null, strategy_amount: string|null, strategy_factor_snapshot: string|null, strategy_name_snapshot: string|null}
     */
    public function strategyAttributes(): array
    {
        if ($this->resolvedStrategy === null) {
            return [
                'strategy_id' => null,
                'strategy_amount' => null,
                'strategy_factor_snapshot' => null,
                'strategy_name_snapshot' => null,
            ];
        }

        return [
            'strategy_id' => $this->resolvedStrategy->id,
            'strategy_amount' => number_format((float) $this->input('strategy_amount'), 2, '.', ''),
            'strategy_factor_snapshot' => $this->resolvedStrategy->factor,
            'strategy_name_snapshot' => $this->resolvedStrategy->name,
        ];
    }

    private function isStrategyPayment(): bool
    {
        return $this->input('payment_type') === InvestmentPaymentType::Estrategia->value;
    }
}
