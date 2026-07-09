<?php

namespace App\Http\Requests;

use App\Enums\InvestmentPaymentType;
use App\Enums\IvaRate;
use App\Models\Currency;
use App\Models\InvestmentPaymentRequest;
use App\Services\PaymentPolicyAuditService;
use App\Services\PaymentRequestPolicyService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateInvestmentPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $payment = $this->route('payment');

        if (! $payment instanceof InvestmentPaymentRequest) {
            return false;
        }

        return $payment->user_id === $this->user()->id
            && $payment->status === 'draft';
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', 'max:255'],
            'rfc' => ['nullable', 'string', 'alpha_num', 'min:12', 'max:13'],
            'invoice_folio' => ['nullable', 'string', 'max:255'],
            'payment_provision_date' => ['required', 'date'],
            'currency_id' => ['required', 'integer', Rule::exists('currencies', 'id')],
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'payment_type' => ['required', Rule::enum(InvestmentPaymentType::class)],
            'description' => ['nullable', 'string', 'max:1000'],
            'keep_documents' => ['nullable', 'array'],
            'keep_documents.*' => ['string'],
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
            // Política de ventana: bloquear EDITAR draft fuera de horario.
            $policy = PaymentRequestPolicyService::fromCurrent();
            $user = $this->user();

            if (! $policy->canCaptureNow($user)) {
                app(PaymentPolicyAuditService::class)->logBlockedAttempt($user, 'edit', $this);
                $validator->errors()->add(
                    'payment_policy',
                    'La ventana de captura está cerrada. Próxima apertura: '
                    .$policy->getNextCaptureOpensAt()->locale('es_MX')->translatedFormat('l j M H:i').' hora CDMX.'
                );

                return;
            }

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

            if (in_array($paymentType, ['reembolso', 'estrategia', 'anticipo', 'cotizacion', 'pagare', 'domiciliado', 'factura_espana'], true)) {
                $files = $this->file('advance_documents', []);
                if (is_array($files) && count($files) > 0) {
                    if (count($files) > 1) {
                        $validator->errors()->add('advance_documents', 'Para reembolso, estrategia, anticipo, cotización, pagaré o domiciliado, adjunta un solo documento (PDF o imagen).');
                    } elseif (! in_array(strtolower($files[0]->getClientOriginalExtension()), ['pdf', 'jpg', 'jpeg', 'png'], true)) {
                        $validator->errors()->add('advance_documents', 'El documento debe ser PDF o imagen (JPG, JPEG o PNG).');
                    }
                }
            }

            $payment = $this->route('payment');
            $investmentRequest = $payment?->investmentRequest;

            if ($investmentRequest) {
                // Al EDITAR un borrador se excluye el propio pago del comprometido/pagado
                // para no contarlo contra sí mismo (disponible = presupuesto − comprometido − pagado).
                $remaining = $investmentRequest->budgetBreakdown(excludePaymentId: $payment->id)['available'];

                // Normalizar el nuevo total (en su moneda) a MXN antes de comparar.
                $rate = (float) (Currency::find((int) $this->input('currency_id'))?->exchange_rate ?? 1);
                $totalMxn = (float) $this->input('total', 0) * $rate;

                if ($totalMxn > $remaining) {
                    $validator->errors()->add(
                        'total',
                        'El total ($'.number_format($totalMxn, 2).' MXN) excede el saldo disponible del presupuesto ($'.number_format($remaining, 2).' MXN, ya descontando lo comprometido y pagado).',
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
