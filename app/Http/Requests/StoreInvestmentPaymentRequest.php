<?php

namespace App\Http\Requests;

use App\Enums\InvestmentPaymentType;
use App\Enums\IvaRate;
use App\Models\InvestmentPaymentRequest;
use App\Models\InvestmentRequest;
use App\States\InvestmentRequest\Completed;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreInvestmentPaymentRequest extends FormRequest
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
            'investment_request_id' => ['required', 'integer', Rule::exists('investment_requests', 'id')],
            'provider' => ['required', 'string', 'max:255'],
            'rfc' => ['nullable', 'string', 'alpha_num', 'min:12', 'max:13'],
            'invoice_folio' => ['nullable', 'string', 'max:255'],
            'payment_provision_date' => ['required', 'date'],
            'currency_id' => ['required', 'integer', Rule::exists('currencies', 'id')],
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'payment_type' => ['required', Rule::enum(InvestmentPaymentType::class)],
            'description' => ['nullable', 'string', 'max:1000'],
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
            $paymentType = $this->input('payment_type');

            if ($paymentType === 'factura') {
                $files = $this->file('invoice_documents', []);
                if (is_array($files) && count($files) > 0) {
                    if (count($files) !== 2) {
                        $validator->errors()->add('invoice_documents', 'Si adjuntas documentos de factura, debe ser exactamente 1 PDF y 1 XML.');
                    } else {
                        $extensions = array_map(fn ($file) => strtolower($file->getClientOriginalExtension()), $files);
                        sort($extensions);
                        if ($extensions !== ['pdf', 'xml']) {
                            $validator->errors()->add('invoice_documents', 'Si adjuntas documentos de factura, debe ser exactamente 1 PDF y 1 XML.');
                        }
                    }
                }
            }

            if (in_array($paymentType, ['reembolso', 'estrategia', 'anticipo'], true)) {
                $files = $this->file('advance_documents', []);
                if (is_array($files) && count($files) > 0) {
                    if (count($files) > 1) {
                        $validator->errors()->add('advance_documents', 'Para reembolso, estrategia o anticipo, adjunta un solo documento (PDF o imagen).');
                    } elseif (! in_array(strtolower($files[0]->getClientOriginalExtension()), ['pdf', 'jpg', 'jpeg', 'png'], true)) {
                        $validator->errors()->add('advance_documents', 'El documento debe ser PDF o imagen (JPG, JPEG o PNG).');
                    }
                }
            }

            $investmentRequest = InvestmentRequest::find($this->input('investment_request_id'));
            if ($investmentRequest && $investmentRequest->investment_expense_concept_id && $investmentRequest->project_id) {
                $groupIds = InvestmentRequest::query()
                    ->where('project_id', $investmentRequest->project_id)
                    ->where('investment_expense_concept_id', $investmentRequest->investment_expense_concept_id)
                    ->whereState('status', Completed::class)
                    ->pluck('id');

                $groupBudget = (float) InvestmentRequest::whereIn('id', $groupIds)->sum('total');
                $groupPaid = (float) InvestmentPaymentRequest::query()
                    ->whereIn('investment_request_id', $groupIds)
                    ->whereNotIn('status', ['rejected', 'ceo_rejected', 'projectmanager_rejected', 'final_rejected'])
                    ->sum('total');

                $remaining = $groupBudget - $groupPaid;
                $total = (float) $this->input('total', 0);

                if ($total > $remaining) {
                    $validator->errors()->add(
                        'total',
                        'El total ($'.number_format($total, 2).') excede el saldo disponible del presupuesto ($'.number_format($remaining, 2).').',
                    );
                }
            } elseif ($investmentRequest) {
                $remaining = (float) $investmentRequest->remaining_balance;
                $total = (float) $this->input('total', 0);
                if ($total > $remaining) {
                    $validator->errors()->add(
                        'total',
                        'El total ($'.number_format($total, 2).') excede el saldo disponible del concepto ($'.number_format($remaining, 2).').',
                    );
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
            'investment_request_id.required' => 'El concepto de inversión es obligatorio.',
            'provider.required' => 'La razón social es obligatoria.',
            'rfc.alpha_num' => 'El RFC solo debe contener letras y números.',
            'rfc.min' => 'El RFC debe tener al menos 12 caracteres.',
            'rfc.max' => 'El RFC no debe exceder 13 caracteres.',
            'payment_provision_date.required' => 'La fecha de programación de pago es obligatoria.',
            'payment_provision_date.date' => 'La fecha de programación de pago debe ser una fecha válida.',
            'currency_id.required' => 'La moneda es obligatoria.',
            'branch_id.required' => 'La sucursal es obligatoria.',
            'payment_type.required' => 'Debe indicar el tipo de pago.',
            'invoice_documents.max' => 'Los documentos de factura no pueden ser más de 2 (1 PDF y 1 XML).',
            'advance_documents.max' => 'No se permiten más de 10 documentos.',
            'iva_rate.required' => 'La tasa de IVA es obligatoria.',
            'subtotal.required' => 'El subtotal es obligatorio.',
            'subtotal.min' => 'El subtotal debe ser mayor o igual a 0.',
            'iva.required' => 'El IVA es obligatorio.',
            'total.required' => 'El total es obligatorio.',
            'total.min' => 'El total debe ser mayor o igual a 0.',
        ];
    }
}
