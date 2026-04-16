<?php

namespace App\Http\Controllers;

use App\Models\InvestmentPaymentApproval;
use App\Models\InvestmentRequestApproval;
use App\Models\PaymentRequestApproval;
use App\Models\WeeklyPaymentScheduleApproval;
use App\Services\ApprovalService;
use App\Services\InvestmentApprovalService;
use App\Services\InvestmentPaymentApprovalService;
use App\Services\WeeklyPaymentScheduleApprovalService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailApprovalController extends Controller
{
    public function __construct(
        private ApprovalService $approvalService,
        private InvestmentApprovalService $investmentApprovalService,
        private InvestmentPaymentApprovalService $investmentPaymentApprovalService,
        private WeeklyPaymentScheduleApprovalService $weeklyPaymentScheduleApprovalService,
    ) {}

    public function show(string $token): View
    {
        $approval = $this->findApprovalByToken($token);

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

        $request = $this->loadRequestRelation($approval);

        return view('approval.show', [
            'approval' => $approval,
            'paymentRequest' => $request,
            'token' => $token,
        ]);
    }

    public function approve(string $token): View
    {
        $approval = $this->resolveValidApproval($token);

        if ($approval instanceof View) {
            return $approval;
        }

        if ($approval instanceof WeeklyPaymentScheduleApproval) {
            $this->weeklyPaymentScheduleApprovalService->approve(
                $approval->schedule,
                $approval->user,
            );

            return view('approval.result', [
                'success' => true,
                'message' => 'La programación de pagos semanal ha sido autorizada correctamente. Puedes cerrar esta ventana.',
                'action' => 'approved',
                'folioNumber' => 'S'.$approval->schedule->week_number.'-'.$approval->schedule->year,
                'provider' => 'Programación Semana '.$approval->schedule->week_number,
            ]);
        } elseif ($approval instanceof InvestmentPaymentApproval) {
            $this->investmentPaymentApprovalService->approve(
                $approval->investmentPaymentRequest,
                $approval->user,
            );
            $request = $approval->investmentPaymentRequest;
        } elseif ($approval instanceof InvestmentRequestApproval) {
            $this->investmentApprovalService->approve(
                $approval->investmentRequest,
                $approval->user,
            );
            $request = $approval->investmentRequest;
        } else {
            $this->approvalService->approve(
                $approval->paymentRequest,
                $approval->user,
            );
            $request = $approval->paymentRequest;
        }

        return view('approval.result', [
            'success' => true,
            'message' => 'La solicitud ha sido autorizada correctamente. Puedes cerrar esta ventana.',
            'action' => 'approved',
            'folioNumber' => $request->folio_number,
            'provider' => $request->provider,
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

        if ($approval instanceof WeeklyPaymentScheduleApproval) {
            $this->weeklyPaymentScheduleApprovalService->reject(
                $approval->schedule,
                $approval->user,
                $validated['comments'],
            );

            return view('approval.result', [
                'success' => true,
                'message' => 'La programación de pagos semanal ha sido rechazada. Puedes cerrar esta ventana.',
                'action' => 'rejected',
                'folioNumber' => 'S'.$approval->schedule->week_number.'-'.$approval->schedule->year,
                'provider' => 'Programación Semana '.$approval->schedule->week_number,
            ]);
        } elseif ($approval instanceof InvestmentPaymentApproval) {
            $this->investmentPaymentApprovalService->reject(
                $approval->investmentPaymentRequest,
                $approval->user,
                $validated['comments'],
            );
            $requestModel = $approval->investmentPaymentRequest;
        } elseif ($approval instanceof InvestmentRequestApproval) {
            $this->investmentApprovalService->reject(
                $approval->investmentRequest,
                $approval->user,
                $validated['comments'],
            );
            $requestModel = $approval->investmentRequest;
        } else {
            $this->approvalService->reject(
                $approval->paymentRequest,
                $approval->user,
                $validated['comments'],
            );
            $requestModel = $approval->paymentRequest;
        }

        return view('approval.result', [
            'success' => true,
            'message' => 'La solicitud ha sido rechazada. Puedes cerrar esta ventana.',
            'action' => 'rejected',
            'folioNumber' => $requestModel->folio_number,
            'provider' => $requestModel->provider,
        ]);
    }

    private function findApprovalByToken(string $token): PaymentRequestApproval|InvestmentRequestApproval|InvestmentPaymentApproval|WeeklyPaymentScheduleApproval|null
    {
        return PaymentRequestApproval::where('approval_token', $token)->first()
            ?? InvestmentRequestApproval::where('approval_token', $token)->first()
            ?? InvestmentPaymentApproval::where('approval_token', $token)->first()
            ?? WeeklyPaymentScheduleApproval::where('approval_token', $token)->first();
    }

    private function loadRequestRelation(PaymentRequestApproval|InvestmentRequestApproval|InvestmentPaymentApproval|WeeklyPaymentScheduleApproval $approval): Model
    {
        if ($approval instanceof WeeklyPaymentScheduleApproval) {
            $approval->load(['schedule.creator', 'schedule.items.investmentPaymentRequest', 'user']);

            return $approval->schedule;
        }

        if ($approval instanceof InvestmentPaymentApproval) {
            $approval->load(['investmentPaymentRequest.user', 'investmentPaymentRequest.department', 'investmentPaymentRequest.currency', 'investmentPaymentRequest.branch', 'investmentPaymentRequest.expenseConcept', 'user']);

            return $approval->investmentPaymentRequest;
        }

        if ($approval instanceof InvestmentRequestApproval) {
            $approval->load(['investmentRequest.user', 'investmentRequest.department', 'investmentRequest.currency', 'investmentRequest.branch', 'investmentRequest.expenseConcept', 'investmentRequest.investmentExpenseConcept', 'investmentRequest.paymentType', 'user']);

            return $approval->investmentRequest;
        }

        $approval->load(['paymentRequest.user', 'paymentRequest.department', 'paymentRequest.currency', 'paymentRequest.branch', 'paymentRequest.expenseConcept', 'paymentRequest.paymentType', 'user']);

        return $approval->paymentRequest;
    }

    private function resolveValidApproval(string $token): PaymentRequestApproval|InvestmentRequestApproval|InvestmentPaymentApproval|WeeklyPaymentScheduleApproval|View
    {
        $approval = $this->findApprovalByToken($token);

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

        $this->loadRequestRelation($approval);

        return $approval;
    }
}
