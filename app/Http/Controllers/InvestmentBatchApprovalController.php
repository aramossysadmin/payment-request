<?php

namespace App\Http\Controllers;

use App\Models\InvestmentPaymentBatch;
use App\Models\InvestmentPaymentRequest;
use App\Notifications\InvestmentPaymentStatusNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InvestmentBatchApprovalController extends Controller
{
    public function show(string $token): Response
    {
        $batch = InvestmentPaymentBatch::query()
            ->where('ceo_approval_token', $token)
            ->with([
                'department',
                'project',
                'user',
                'paymentRequests' => fn ($q) => $q->where('status', 'submitted'),
                'paymentRequests.investmentRequest.investmentExpenseConcept',
                'paymentRequests.currency',
            ])
            ->first();

        if (! $batch || ! $batch->hasValidCeoToken() || $batch->status !== 'submitted') {
            return Inertia::render('investment-batch-approval/invalid', [
                'reason' => $this->resolveInvalidReason($batch),
            ]);
        }

        return Inertia::render('investment-batch-approval/show', [
            'batch' => [
                'uuid' => $batch->uuid,
                'token' => $token,
                'department' => $batch->department->name ?? '-',
                'project' => $batch->project->name ?? '-',
                'requester' => $batch->user->name ?? '-',
                'week_number' => $batch->week_number,
                'year' => $batch->year,
                'submitted_at' => $batch->submitted_at?->toISOString(),
                'expires_at' => $batch->ceo_approval_token_expires_at?->toISOString(),
                'payments' => $batch->paymentRequests->map(fn (InvestmentPaymentRequest $p) => [
                    'uuid' => $p->uuid,
                    'folio_number' => $p->folio_number,
                    'concept' => $p->investmentRequest?->investmentExpenseConcept?->name ?? '-',
                    'provider' => $p->provider,
                    'rfc' => $p->rfc,
                    'description' => $p->description,
                    'currency_prefix' => $p->currency?->prefix ?? 'MXN',
                    'subtotal' => (string) $p->subtotal,
                    'iva' => (string) $p->iva,
                    'total' => (string) $p->total,
                    'payment_provision_date' => $p->payment_provision_date?->toDateString(),
                ]),
                'total' => number_format((float) $batch->paymentRequests->sum(fn ($p) => (float) $p->total), 2, '.', ''),
            ],
        ]);
    }

    public function review(Request $request, string $token): RedirectResponse
    {
        $batch = InvestmentPaymentBatch::query()
            ->where('ceo_approval_token', $token)
            ->first();

        abort_if(! $batch || ! $batch->hasValidCeoToken() || $batch->status !== 'submitted', 410, 'El enlace de aprobación no es válido.');

        $validated = $request->validate([
            'approved_uuids' => ['array'],
            'approved_uuids.*' => ['string', 'uuid'],
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $approvedUuids = collect($validated['approved_uuids'] ?? []);
        $rejectionReason = $validated['rejection_reason'] ?? null;

        $payments = InvestmentPaymentRequest::query()
            ->where('batch_id', $batch->id)
            ->where('status', 'submitted')
            ->with('user')
            ->get();

        DB::transaction(function () use ($batch, $payments, $approvedUuids, $rejectionReason) {
            foreach ($payments as $payment) {
                if ($approvedUuids->contains($payment->uuid)) {
                    $payment->update([
                        'status' => 'ceo_approved',
                        'ceo_reviewed_at' => now(),
                    ]);
                } else {
                    $payment->update([
                        'status' => 'ceo_rejected',
                        'ceo_rejection_reason' => $rejectionReason,
                        'ceo_reviewed_at' => now(),
                    ]);
                }
            }

            $hasApproved = $payments->contains(fn ($p) => $approvedUuids->contains($p->uuid));

            $batch->update([
                'status' => $hasApproved ? 'ceo_approved' : 'ceo_rejected',
                'ceo_reviewed_at' => now(),
                'ceo_approval_token' => null,
                'ceo_approval_token_expires_at' => null,
            ]);
        });

        // Notify requesters
        foreach ($payments as $payment) {
            $stage = $approvedUuids->contains($payment->uuid) ? 'ceo_approved' : 'ceo_rejected';
            $reason = $stage === 'ceo_rejected' ? $rejectionReason : null;

            if ($payment->user) {
                $payment->user->notify(new InvestmentPaymentStatusNotification($payment, $stage, $reason));
            }
        }

        return back()->with('success', 'Revisión guardada exitosamente.');
    }

    private function resolveInvalidReason(?InvestmentPaymentBatch $batch): string
    {
        if (! $batch) {
            return 'El enlace de aprobación no es válido o no existe.';
        }

        if ($batch->status !== 'submitted') {
            return 'Este lote ya fue procesado anteriormente.';
        }

        if (! $batch->hasValidCeoToken()) {
            return 'El enlace de aprobación ha expirado.';
        }

        return 'El enlace de aprobación no es válido.';
    }
}
