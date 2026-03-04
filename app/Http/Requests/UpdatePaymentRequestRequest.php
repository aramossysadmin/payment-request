<?php

namespace App\Http\Requests;

use App\Enums\PaymentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentRequestRequest extends FormRequest
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
            'provider' => ['required', 'string', 'max:255'],
            'invoice_folio' => ['required', 'string', 'max:255'],
            'currency_id' => ['required', 'integer', Rule::exists('currencies', 'id')],
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'expense_concept_id' => ['required', 'integer', Rule::exists('expense_concepts', 'id')],
            'description' => ['nullable', 'string', 'max:1000'],
            'payment_type' => ['required', Rule::enum(PaymentType::class)],
            'advance_documents' => ['nullable', 'array', 'max:2'],
            'advance_documents.*' => ['file', 'mimes:xml,pdf', 'max:10240'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'iva' => ['required', 'numeric', 'min:0'],
            'retention' => ['boolean'],
            'total' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'provider.required' => 'El proveedor es obligatorio.',
            'invoice_folio.required' => 'El folio de factura es obligatorio.',
            'currency_id.required' => 'La moneda es obligatoria.',
            'currency_id.exists' => 'La moneda seleccionada no es válida.',
            'branch_id.required' => 'La sucursal es obligatoria.',
            'branch_id.exists' => 'La sucursal seleccionada no es válida.',
            'expense_concept_id.required' => 'El concepto de gasto es obligatorio.',
            'expense_concept_id.exists' => 'El concepto de gasto seleccionado no es válido.',
            'payment_type.required' => 'El tipo de pago es obligatorio.',
            'advance_documents.max' => 'Solo se permiten máximo 2 documentos.',
            'advance_documents.*.mimes' => 'Los documentos deben ser archivos XML o PDF.',
            'advance_documents.*.max' => 'Cada documento no debe superar los 10MB.',
            'subtotal.required' => 'El subtotal es obligatorio.',
            'subtotal.min' => 'El subtotal debe ser mayor o igual a 0.',
            'iva.required' => 'El IVA es obligatorio.',
            'retention.boolean' => 'El campo retención debe ser verdadero o falso.',
            'total.required' => 'El total es obligatorio.',
            'total.min' => 'El total debe ser mayor o igual a 0.',
        ];
    }
}
