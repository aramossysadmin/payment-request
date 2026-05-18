<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UploadInvestmentPaymentDocumentsRequest extends FormRequest
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
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'xml' => ['required', 'file', 'mimes:xml', 'max:10240'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $pdf = $this->file('pdf');
            $xml = $this->file('xml');

            if ($pdf && strtolower($pdf->getClientOriginalExtension()) !== 'pdf') {
                $validator->errors()->add('pdf', 'El archivo PDF debe tener extensión .pdf');
            }

            if ($xml && strtolower($xml->getClientOriginalExtension()) !== 'xml') {
                $validator->errors()->add('xml', 'El archivo XML debe tener extensión .xml');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pdf.required' => 'El archivo PDF es obligatorio.',
            'pdf.file' => 'El PDF debe ser un archivo válido.',
            'pdf.mimes' => 'El archivo PDF debe ser de tipo PDF.',
            'pdf.max' => 'El archivo PDF no puede exceder 10MB.',
            'xml.required' => 'El archivo XML es obligatorio.',
            'xml.file' => 'El XML debe ser un archivo válido.',
            'xml.mimes' => 'El archivo XML debe ser de tipo XML.',
            'xml.max' => 'El archivo XML no puede exceder 10MB.',
        ];
    }
}
