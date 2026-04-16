<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWeeklyPaymentScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'week_number' => ['required', 'integer', 'min:1', 'max:53'],
            'year' => ['required', 'integer', 'min:2024'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:investment_payment_requests,id'],
            'items.*.included' => ['required', 'boolean'],
            'items.*.exclusion_reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'week_number.required' => 'El número de semana es obligatorio.',
            'year.required' => 'El año es obligatorio.',
            'items.required' => 'Debe incluir al menos un pago en la programación.',
            'items.min' => 'Debe incluir al menos un pago en la programación.',
            'items.*.id.required' => 'El identificador del pago es obligatorio.',
            'items.*.id.exists' => 'El pago seleccionado no existe.',
            'items.*.included.required' => 'Debe indicar si el pago está incluido.',
        ];
    }
}
