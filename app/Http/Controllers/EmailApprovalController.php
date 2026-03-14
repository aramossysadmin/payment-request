<?php

namespace App\Http\Controllers;

use App\Models\PaymentRequestApproval;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailApprovalController extends Controller
{
    public function __construct(private ApprovalService $approvalService) {}

    public function show(string $token): View
    {
        $approval = PaymentRequestApproval::where('approval_token', $token)->first();

        if (! $approval) {
            return view('approval.result', [
                'success' => false,
                'message' => 'El enlace de autorización no es válido o ya fue utilizado.',
            ]);
        }

        if ($approval->isTokenExpired()) {
            return view('approval.result', [
                'success' => false,
                'message' => 'El enlace de autorización ha expirado. Por favor, ingresa al portal web para realizar esta acción.',
            ]);
        }

        if (! $approval->isPending()) {
            $statusLabel = $approval->isApproved() ? 'autorizada' : 'rechazada';

            return view('approval.result', [
                'success' => false,
                'message' => "Esta solicitud ya fue {$statusLabel} previamente.",
            ]);
        }

        $approval->load(['paymentRequest.user', 'paymentRequest.department', 'paymentRequest.currency', 'paymentRequest.branch', 'paymentRequest.expenseConcept', 'user']);

        return view('approval.show', [
            'approval' => $approval,
            'paymentRequest' => $approval->paymentRequest,
            'token' => $token,
        ]);
    }

    public function approve(string $token): View
    {
        $approval = $this->resolveValidApproval($token);

        if ($approval instanceof View) {
            return $approval;
        }

        $this->approvalService->approve(
            $approval->paymentRequest,
            $approval->user,
        );

        return view('approval.result', [
            'success' => true,
            'message' => 'La solicitud ha sido autorizada correctamente. Puedes cerrar esta ventana.',
            'action' => 'approved',
            'folioNumber' => $approval->paymentRequest->folio_number,
            'provider' => $approval->paymentRequest->provider,
        ]);
    }

    public function reject(Request $request, string $token): View
    {
        $approval = $this->resolveValidApproval($token);

        if ($approval instanceof View) {
            return $approval;
        }

        $validated = $request->validate([
            'comments' => ['required', 'string', 'min:10'],
        ], [
            'comments.required' => 'Los comentarios son obligatorios al rechazar.',
            'comments.min' => 'Los comentarios deben tener al menos 10 caracteres.',
        ]);

        $this->approvalService->reject(
            $approval->paymentRequest,
            $approval->user,
            $validated['comments'],
        );

        return view('approval.result', [
            'success' => true,
            'message' => 'La solicitud ha sido rechazada. Puedes cerrar esta ventana.',
            'action' => 'rejected',
            'folioNumber' => $approval->paymentRequest->folio_number,
            'provider' => $approval->paymentRequest->provider,
        ]);
    }

    private function resolveValidApproval(string $token): PaymentRequestApproval|View
    {
        $approval = PaymentRequestApproval::with(['paymentRequest', 'user'])
            ->where('approval_token', $token)
            ->first();

        if (! $approval) {
            return view('approval.result', [
                'success' => false,
                'message' => 'El enlace de autorización no es válido o ya fue utilizado.',
            ]);
        }

        if ($approval->isTokenExpired()) {
            return view('approval.result', [
                'success' => false,
                'message' => 'El enlace de autorización ha expirado. Por favor, ingresa al portal web para realizar esta acción.',
            ]);
        }

        if (! $approval->isPending()) {
            $statusLabel = $approval->isApproved() ? 'autorizada' : 'rechazada';

            return view('approval.result', [
                'success' => false,
                'message' => "Esta solicitud ya fue {$statusLabel} previamente.",
            ]);
        }

        return $approval;
    }
}
