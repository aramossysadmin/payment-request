<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectPaymentRequestRequest extends FormRequest
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
            'comments' => ['required', 'string', 'min:10'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'comments.required' => 'Los comentarios son obligatorios al rechazar.',
            'comments.min' => 'Los comentarios deben tener al menos 10 caracteres.',
        ];
    }
}
