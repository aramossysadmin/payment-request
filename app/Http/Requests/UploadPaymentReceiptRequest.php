<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadPaymentReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'receipt_documents' => ['required', 'array', 'min:1', 'max:5'],
            'receipt_documents.*' => ['file', 'max:10240', 'mimes:pdf,xml,jpg,jpeg,png'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'receipt_documents.required' => 'Adjunta al menos un comprobante de pago.',
            'receipt_documents.max' => 'Máximo 5 archivos por carga.',
            'receipt_documents.*.max' => 'Cada archivo debe pesar máximo 10 MB.',
            'receipt_documents.*.mimes' => 'Solo se permiten PDF, XML o imágenes (JPG, JPEG, PNG).',
        ];
    }
}
