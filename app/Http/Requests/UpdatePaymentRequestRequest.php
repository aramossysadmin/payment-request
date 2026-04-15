<?php

namespace App\Http\Requests;

use App\Enums\DocumentMode;
use App\Enums\IvaRate;
use App\Models\PaymentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
        $paymentType = PaymentType::find($this->input('payment_type_id'));
        $invoiceMode = $paymentType?->invoice_documents_mode ?? DocumentMode::Disabled;
        $additionalMode = $paymentType?->additional_documents_mode ?? DocumentMode::Optional;

        return [
            'provider' => ['required', 'string', 'max:255'],
            'rfc' => ['nullable', 'string', 'alpha_num', 'min:12', 'max:13'],
            'invoice_folio' => ['required', 'string', 'max:255'],
            'currency_id' => ['required', 'integer', Rule::exists('currencies', 'id')],
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'expense_concept_id' => ['required', 'integer', Rule::exists('expense_concepts', 'id')],
            'description' => ['nullable', 'string', 'max:1000'],
            'payment_type_id' => ['required', 'integer', Rule::exists('payment_types', 'id')],
            'invoice_documents' => ['nullable', 'array', 'max:2'],
            'invoice_documents.*' => ['file', 'max:10240', 'mimes:pdf,xml'],
            'advance_documents' => ['nullable', 'array', 'max:10'],
            'advance_documents.*' => ['file', 'max:10240', 'mimes:pdf,xml,jpg,jpeg,png,doc,docx,xls,xlsx'],
            'iva_rate' => ['required', Rule::enum(IvaRate::class)],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'iva' => ['required', 'numeric', 'min:0'],
            'retention' => ['boolean'],
            'total' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $paymentType = PaymentType::find($this->input('payment_type_id'));
            $invoiceMode = $paymentType?->invoice_documents_mode ?? DocumentMode::Disabled;

            if ($invoiceMode === DocumentMode::Disabled) {
                return;
            }

            $files = $this->file('invoice_documents');
            if (! is_array($files) || count($files) === 0) {
                return;
            }

            if (count($files) !== 2) {
                $validator->errors()->add(
                    'invoice_documents',
                    'Debe subir exactamente 1 archivo PDF y 1 archivo XML.',
                );

                return;
            }

            $extensions = array_map(
                fn ($file) => strtolower($file->getClientOriginalExtension()),
                $files,
            );

            sort($extensions);

            if ($extensions !== ['pdf', 'xml']) {
                $validator->errors()->add(
                    'invoice_documents',
                    'Debe subir exactamente 1 archivo PDF y 1 archivo XML.',
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'provider.required' => 'La razón social es obligatoria.',
            'rfc.alpha_num' => 'El RFC solo debe contener letras y números.',
            'rfc.min' => 'El RFC debe tener al menos 12 caracteres.',
            'rfc.max' => 'El RFC no debe exceder 13 caracteres.',
            'invoice_folio.required' => 'El folio de factura es obligatorio.',
            'currency_id.required' => 'La moneda es obligatoria.',
            'currency_id.exists' => 'La moneda seleccionada no es válida.',
            'branch_id.required' => 'La sucursal es obligatoria.',
            'branch_id.exists' => 'La sucursal seleccionada no es válida.',
            'expense_concept_id.required' => 'El concepto de gasto es obligatorio.',
            'expense_concept_id.exists' => 'El concepto de gasto seleccionado no es válido.',
            'payment_type_id.required' => 'El tipo de pago es obligatorio.',
            'payment_type_id.exists' => 'El tipo de pago seleccionado no es válido.',
            'invoice_documents.max' => 'Debe subir exactamente 2 archivos (1 PDF y 1 XML).',
            'invoice_documents.*.max' => 'Cada documento no debe superar los 10MB.',
            'advance_documents.max' => 'No se permiten más de 10 documentos.',
            'advance_documents.*.max' => 'Cada documento no debe superar los 10MB.',
            'iva_rate.required' => 'La tasa de IVA es obligatoria.',
            'subtotal.required' => 'El subtotal es obligatorio.',
            'subtotal.min' => 'El subtotal debe ser mayor o igual a 0.',
            'iva.required' => 'El IVA es obligatorio.',
            'retention.boolean' => 'El campo retención debe ser verdadero o falso.',
            'total.required' => 'El total es obligatorio.',
            'total.min' => 'El total debe ser mayor o igual a 0.',
        ];
    }
}
