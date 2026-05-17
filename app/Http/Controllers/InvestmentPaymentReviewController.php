<?php

namespace App\Http\Controllers;

use App\Models\InvestmentPaymentRequest;
use App\Notifications\InvestmentPaymentStatusNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InvestmentPaymentReviewController extends Controller
{
    public function index(): Response
    {
        $payments = InvestmentPaymentRequest::query()
            ->whereIn('status', ['ceo_approved', 'projectmanager_review'])
            ->with([
                'department',
                'user',
                'investmentRequest.investmentExpenseConcept',
                'investmentRequest.project',
                'currency',
                'branch',
            ])
            ->orderBy('department_id')
            ->latest('id')
            ->get();

        $grouped = $payments->groupBy(fn (InvestmentPaymentRequest $p) => $p->department?->name ?? 'Sin departamento')
            ->map(fn ($departmentPayments, string $departmentName) => [
                'department' => $departmentName,
                'count' => $departmentPayments->count(),
                'total' => number_format((float) $departmentPayments->sum(fn ($p) => (float) $p->total), 2, '.', ''),
                'payments' => $departmentPayments->map(fn (InvestmentPaymentRequest $p) => [
                    'uuid' => $p->uuid,
                    'folio_number' => $p->folio_number,
                    'concept' => $p->investmentRequest?->investmentExpenseConcept?->name ?? '-',
                    'project' => $p->investmentRequest?->project?->name ?? '-',
                    'provider' => $p->provider,
                    'rfc' => $p->rfc,
                    'requester' => $p->user?->name ?? '-',
                    'currency_prefix' => $p->currency?->prefix ?? 'MXN',
                    'branch' => $p->branch?->name ?? '-',
                    'subtotal' => (string) $p->subtotal,
                    'iva' => (string) $p->iva,
                    'total' => (string) $p->total,
                    'payment_provision_date' => $p->payment_provision_date?->toDateString(),
                ])->values(),
            ])
            ->values();

        return Inertia::render('investment-payment-review/index', [
            'groups' => $grouped,
            'totalCount' => $payments->count(),
        ]);
    }

    public function submit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'decisions' => ['required', 'array', 'min:1'],
            'decisions.*.uuid' => ['required', 'string', 'uuid'],
            'decisions.*.approved' => ['required', 'boolean'],
            'decisions.*.approved_amount' => ['nullable', 'numeric', 'min:0'],
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $rejectionReason = $validated['rejection_reason'] ?? null;
        $decisionsByUuid = collect($validated['decisions'])->keyBy('uuid');

        $payments = InvestmentPaymentRequest::query()
            ->whereIn('uuid', $decisionsByUuid->keys())
            ->whereIn('status', ['ceo_approved', 'projectmanager_review'])
            ->with('user')
            ->get();

        if ($payments->isEmpty()) {
            return back()->withErrors(['decisions' => 'No se encontraron pagos válidos para procesar.']);
        }

        // Validate approved_amount <= total for each approved payment
        foreach ($payments as $payment) {
            $decision = $decisionsByUuid[$payment->uuid];
            if ($decision['approved']) {
                $approvedAmount = (float) ($decision['approved_amount'] ?? $payment->total);
                if ($approvedAmount > (float) $payment->total) {
                    return back()->withErrors([
                        "decisions.{$payment->uuid}.approved_amount" => 'El monto aprobado no puede ser mayor al monto solicitado ($'.number_format((float) $payment->total, 2).').',
                    ]);
                }
                if ($approvedAmount <= 0) {
                    return back()->withErrors([
                        "decisions.{$payment->uuid}.approved_amount" => 'El monto aprobado debe ser mayor a cero.',
                    ]);
                }
            }
        }

        DB::transaction(function () use ($payments, $decisionsByUuid, $rejectionReason) {
            foreach ($payments as $payment) {
                $decision = $decisionsByUuid[$payment->uuid];
                if ($decision['approved']) {
                    $approvedAmount = (float) ($decision['approved_amount'] ?? $payment->total);
                    $payment->update([
                        'status' => 'projectmanager_approved',
                        'approved_amount' => $approvedAmount,
                        'pm_reviewed_at' => now(),
                    ]);
                } else {
                    $payment->update([
                        'status' => 'projectmanager_rejected',
                        'pm_rejection_reason' => $rejectionReason,
                        'pm_reviewed_at' => now(),
                    ]);
                }
            }
        });

        // Notify requesters
        $approvedCount = 0;
        $rejectedCount = 0;
        foreach ($payments as $payment) {
            $decision = $decisionsByUuid[$payment->uuid];

            if ($decision['approved']) {
                $approvedCount++;
                $approvedAmount = (float) ($decision['approved_amount'] ?? $payment->total);
                $adjustedAmount = $approvedAmount < (float) $payment->total ? number_format($approvedAmount, 2, '.', '') : null;

                if ($payment->user) {
                    $payment->user->notify(new InvestmentPaymentStatusNotification(
                        $payment,
                        'projectmanager_approved',
                        null,
                        $adjustedAmount,
                    ));
                }
            } else {
                $rejectedCount++;

                if ($payment->user) {
                    $payment->user->notify(new InvestmentPaymentStatusNotification(
                        $payment,
                        'projectmanager_rejected',
                        $rejectionReason,
                    ));
                }
            }
        }

        return redirect()
            ->route('investment-payment-review.index')
            ->with('success', sprintf(
                'Revisión enviada: %d aprobados, %d rechazados. Los solicitantes fueron notificados.',
                $approvedCount,
                $rejectedCount,
            ));
    }
}
