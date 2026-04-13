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

        $this->approvalService->approve($investmentRequest, $user);

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
}
