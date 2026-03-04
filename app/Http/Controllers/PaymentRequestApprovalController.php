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

        $this->approvalService->approve($paymentRequest, $user);

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
}
