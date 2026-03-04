<?php

namespace App\Http\Controllers;

use App\Http\Requests\RejectPaymentRequestRequest;
use App\Models\PaymentRequest;
use App\Services\ApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentRequestApprovalController extends Controller
{
    public function __construct(private ApprovalService $approvalService) {}

    public function approve(Request $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        $user = $request->user();

        $pendingApproval = $paymentRequest->approvals()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        abort_unless($pendingApproval, 403);

        $validated = $request->validate([
            'number_purchase_invoices' => ['nullable', 'integer', 'min:1'],
            'number_vendor_payments' => ['nullable', 'integer', 'min:1'],
        ]);

        $this->approvalService->approve($paymentRequest, $user, $validated);

        return redirect()->back()->with('success', 'Solicitud aprobada exitosamente.');
    }

    public function reject(RejectPaymentRequestRequest $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        $user = $request->user();

        $pendingApproval = $paymentRequest->approvals()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        abort_unless($pendingApproval, 403);

        $this->approvalService->reject($paymentRequest, $user, $request->validated('comments'));

        return redirect()->back()->with('success', 'Solicitud rechazada exitosamente.');
    }

    public function updateSapFolios(Request $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'number_purchase_invoices' => ['nullable', 'integer', 'min:1'],
            'number_vendor_payments' => ['nullable', 'integer', 'min:1'],
        ]);

        $canEditPurchaseInvoices = $this->canEditField($paymentRequest, $user, 'administration');
        $canEditVendorPayments = $this->canEditField($paymentRequest, $user, 'treasury');

        abort_unless($canEditPurchaseInvoices || $canEditVendorPayments, 403);

        $data = [];

        if ($canEditPurchaseInvoices && array_key_exists('number_purchase_invoices', $validated)) {
            $data['number_purchase_invoices'] = $validated['number_purchase_invoices'];
        }

        if ($canEditVendorPayments && array_key_exists('number_vendor_payments', $validated)) {
            $data['number_vendor_payments'] = $validated['number_vendor_payments'];
        }

        if (! empty($data)) {
            $paymentRequest->update($data);
        }

        return redirect()->back()->with('success', 'Folios SAP actualizados exitosamente.');
    }

    private function canEditField(PaymentRequest $paymentRequest, mixed $user, string $stage): bool
    {
        return $paymentRequest->approvals()
            ->where('user_id', $user->id)
            ->where('stage', $stage)
            ->where('status', 'approved')
            ->exists();
    }
}
