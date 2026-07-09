<?php

namespace App\Http\Requests;

use App\Enums\InvestmentPaymentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
            'payment_type' => ['required', Rule::enum(InvestmentPaymentType::class)],
            'pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            // Los XML de CFDI suelen detectarse como text/plain por finfo (no traen
            // declaracion XML ni BOM al inicio). La extension .xml sigue verificandose
            // en withValidator() abajo.
            'xml' => ['nullable', 'file', 'mimetypes:application/xml,text/xml,text/plain', 'max:10240'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $paymentType = $this->input('payment_type');

            if ($paymentType === 'factura') {
                $pdf = $this->file('pdf');
                $xml = $this->file('xml');

                if (! $pdf) {
                    $validator->errors()->add('pdf', 'El archivo PDF es obligatorio.');
                } elseif (strtolower($pdf->getClientOriginalExtension()) !== 'pdf') {
                    $validator->errors()->add('pdf', 'El archivo PDF debe tener extensión .pdf');
                }

                if (! $xml) {
                    $validator->errors()->add('xml', 'El archivo XML es obligatorio.');
                } elseif (strtolower($xml->getClientOriginalExtension()) !== 'xml') {
                    $validator->errors()->add('xml', 'El archivo XML debe tener extensión .xml');
                }
            }

            if (in_array($paymentType, ['reembolso', 'estrategia', 'anticipo', 'cotizacion', 'pagare', 'domiciliado', 'factura_espana'], true)) {
                $document = $this->file('document');

                if (! $document) {
                    $validator->errors()->add('document', 'El documento es obligatorio.');
                } elseif (! in_array(strtolower($document->getClientOriginalExtension()), ['pdf', 'jpg', 'jpeg', 'png'], true)) {
                    $validator->errors()->add('document', 'El documento debe ser PDF o imagen (JPG, JPEG o PNG).');
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_type.required' => 'Debe indicar el tipo de pago.',
            'pdf.file' => 'El PDF debe ser un archivo válido.',
            'pdf.mimes' => 'El archivo PDF debe ser de tipo PDF.',
            'pdf.max' => 'El archivo PDF no puede exceder 10MB.',
            'xml.file' => 'El XML debe ser un archivo válido.',
            'xml.mimetypes' => 'El archivo XML debe ser un XML válido.',
            'xml.max' => 'El archivo XML no puede exceder 10MB.',
            'document.file' => 'El documento debe ser un archivo válido.',
            'document.mimes' => 'El documento debe ser PDF o imagen (JPG, JPEG o PNG).',
            'document.max' => 'El documento no puede exceder 10MB.',
        ];
    }
}
