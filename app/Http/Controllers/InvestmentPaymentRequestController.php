<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvestmentPaymentRequest;
use App\Models\InvestmentPaymentRequest;
use App\Models\InvestmentRequest;
use App\Services\InvestmentPaymentApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class InvestmentPaymentRequestController extends Controller
{
    public function __construct(private InvestmentPaymentApprovalService $approvalService) {}

    public function store(StoreInvestmentPaymentRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $investmentRequest = InvestmentRequest::findOrFail($validated['investment_request_id']);

        $paymentRequest = new InvestmentPaymentRequest($validated);
        $paymentRequest->user_id = $user->id;
        $paymentRequest->department_id = $user->department_id;
        $paymentRequest->expense_concept_id = $investmentRequest->expense_concept_id;
        $paymentRequest->payment_type = $request->boolean('is_invoice') ? 'factura' : 'anticipo';
        $paymentRequest->save();

        $directory = 'investment-payment-documents/'.now()->format('Y/m').'/'.$paymentRequest->folio_number;
        $allDocuments = [];

        if ($request->hasFile('invoice_documents')) {
            foreach ($request->file('invoice_documents') as $file) {
                $allDocuments[] = $file->storeAs($directory, Str::uuid().'.'.$file->getClientOriginalExtension(), 'local');
            }
        }

        if ($request->hasFile('advance_documents')) {
            foreach ($request->file('advance_documents') as $file) {
                $allDocuments[] = $file->storeAs($directory, Str::uuid().'.'.$file->getClientOriginalExtension(), 'local');
            }
        }

        if (! empty($allDocuments)) {
            $paymentRequest->update(['advance_documents' => $allDocuments]);
        }

        $this->approvalService->createApproval($paymentRequest);

        return back()->with('success', 'Solicitud de pago de inversión creada exitosamente.');
    }
}
