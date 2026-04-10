<?php

namespace App\Http\Controllers;

use App\Http\Requests\RejectInvestmentRequestRequest;
use App\Models\InvestmentRequest;
use App\Services\InvestmentApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InvestmentRequestApprovalController extends Controller
{
    public function __construct(private InvestmentApprovalService $approvalService) {}

    public function approve(Request $request, InvestmentRequest $investmentRequest): RedirectResponse
    {
        $user = $request->user();

        $pendingApproval = $investmentRequest->approvals()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        abort_unless($pendingApproval, 403);

        $validated = $request->validate([
            'number_purchase_invoices' => ['nullable', 'integer', 'min:1'],
            'number_vendor_payments' => ['nullable', 'integer', 'min:1'],
        ]);

        $this->approvalService->approve($investmentRequest, $user, $validated);

        return redirect()->back()->with('success', 'Solicitud aprobada exitosamente.');
    }

    public function reject(RejectInvestmentRequestRequest $request, InvestmentRequest $investmentRequest): RedirectResponse
    {
        $user = $request->user();

        $pendingApproval = $investmentRequest->approvals()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        abort_unless($pendingApproval, 403);

        $this->approvalService->reject($investmentRequest, $user, $request->validated('comments'));

        return redirect()->back()->with('success', 'Solicitud rechazada exitosamente.');
    }

    public function updateSapFolios(Request $request, InvestmentRequest $investmentRequest): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'number_purchase_invoices' => ['nullable', 'integer', 'min:1'],
            'number_vendor_payments' => ['nullable', 'integer', 'min:1'],
        ]);

        $canEditPurchaseInvoices = $this->canEditField($investmentRequest, $user, 'administration');
        $canEditVendorPayments = $this->canEditField($investmentRequest, $user, 'treasury');

        abort_unless($canEditPurchaseInvoices || $canEditVendorPayments, 403);

        $data = [];

        if ($canEditPurchaseInvoices && array_key_exists('number_purchase_invoices', $validated)) {
            $data['number_purchase_invoices'] = $validated['number_purchase_invoices'];
        }

        if ($canEditVendorPayments && array_key_exists('number_vendor_payments', $validated)) {
            $data['number_vendor_payments'] = $validated['number_vendor_payments'];
        }

        if (! empty($data)) {
            $investmentRequest->update($data);
        }

        return redirect()->back()->with('success', 'Folios SAP actualizados exitosamente.');
    }

    private function canEditField(InvestmentRequest $investmentRequest, mixed $user, string $stage): bool
    {
        return $investmentRequest->approvals()
            ->where('user_id', $user->id)
            ->where('stage', $stage)
            ->where('status', 'approved')
            ->exists();
    }
}
