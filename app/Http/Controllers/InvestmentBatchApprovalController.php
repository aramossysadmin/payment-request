<?php

namespace App\Http\Controllers;

use App\Models\InvestmentPaymentBatch;
use App\Models\InvestmentPaymentRequest;
use App\Models\User;
use App\Notifications\InvestmentBatchReadyForReviewNotification;
use App\Notifications\InvestmentBatchRequesterSummaryNotification;
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

        $hasApproved = $payments->contains(fn ($p) => $approvedUuids->contains($p->uuid));

        DB::transaction(function () use ($batch, $payments, $approvedUuids, $rejectionReason, $hasApproved) {
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

            $batch->update([
                'status' => $hasApproved ? 'ceo_approved' : 'ceo_rejected',
                'ceo_reviewed_at' => now(),
                'ceo_approval_token' => null,
                'ceo_approval_token_expires_at' => null,
            ]);
        });

        // Notify each requester with their consolidated summary (1 email per requester)
        $payments->groupBy('user_id')->each(function ($userPayments) use ($approvedUuids, $rejectionReason) {
            $user = $userPayments->first()->user;
            if (! $user) {
                return;
            }

            $approved = $userPayments->filter(fn ($p) => $approvedUuids->contains($p->uuid))->values();
            $rejected = $userPayments->reject(fn ($p) => $approvedUuids->contains($p->uuid))->values();

            $user->notify(new InvestmentBatchRequesterSummaryNotification($approved, $rejected, $rejectionReason));
        });

        // Notify project managers with full queue snapshot (role-based, supports rotation and multi-PM)
        if ($hasApproved) {
            User::role('project_manager')->get()->each(function ($pm) use ($batch) {
                $pm->notify(new InvestmentBatchReadyForReviewNotification($batch));
            });
        }

        $approvedCount = $payments->filter(fn ($p) => $approvedUuids->contains($p->uuid))->count();
        $rejectedCount = $payments->count() - $approvedCount;

        return redirect()->route('investment-batch-approval.success', [
            'approved' => $approvedCount,
            'rejected' => $rejectedCount,
        ]);
    }

    public function success(Request $request): Response
    {
        return Inertia::render('investment-batch-approval/success', [
            'approved' => (int) $request->query('approved', 0),
            'rejected' => (int) $request->query('rejected', 0),
            'isFinal' => (bool) $request->query('final', false),
        ]);
    }

    public function showFinal(string $token): Response
    {
        $batch = InvestmentPaymentBatch::query()
            ->where('final_ceo_approval_token', $token)
            ->with([
                'department',
                'project',
                'user',
                'paymentRequests' => fn ($q) => $q->where('status', 'projectmanager_approved'),
                'paymentRequests.investmentRequest.investmentExpenseConcept',
                'paymentRequests.currency',
            ])
            ->first();

        if (! $batch || ! $batch->hasValidFinalCeoToken() || $batch->status !== 'final_pending') {
            return Inertia::render('investment-batch-approval/invalid', [
                'reason' => $this->resolveInvalidFinalReason($batch),
            ]);
        }

        return Inertia::render('investment-batch-approval/show-final', [
            'batch' => [
                'uuid' => $batch->uuid,
                'token' => $token,
                'department' => $batch->department->name ?? '-',
                'project' => $batch->project->name ?? '-',
                'requester' => $batch->user->name ?? '-',
                'week_number' => $batch->week_number,
                'year' => $batch->year,
                'expires_at' => $batch->final_ceo_approval_token_expires_at?->toISOString(),
                'payments' => $batch->paymentRequests->map(fn (InvestmentPaymentRequest $p) => [
                    'uuid' => $p->uuid,
                    'folio_number' => $p->folio_number,
                    'concept' => $p->investmentRequest?->investmentExpenseConcept?->name ?? '-',
                    'provider' => $p->provider,
                    'rfc' => $p->rfc,
                    'currency_prefix' => $p->currency?->prefix ?? 'MXN',
                    'total' => (string) $p->total,
                    'approved_amount' => $p->approved_amount !== null ? (string) $p->approved_amount : (string) $p->total,
                    'was_adjusted' => $p->approved_amount !== null && (float) $p->approved_amount < (float) $p->total,
                ]),
                'total_to_pay' => number_format(
                    (float) $batch->paymentRequests->sum(fn ($p) => (float) ($p->approved_amount ?? $p->total)),
                    2, '.', ''
                ),
            ],
        ]);
    }

    public function reviewFinal(Request $request, string $token): RedirectResponse
    {
        $batch = InvestmentPaymentBatch::query()
            ->where('final_ceo_approval_token', $token)
            ->first();

        abort_if(! $batch || ! $batch->hasValidFinalCeoToken() || $batch->status !== 'final_pending', 410, 'El enlace de aprobación final no es válido.');

        $validated = $request->validate([
            'approved_uuids' => ['array'],
            'approved_uuids.*' => ['string', 'uuid'],
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $approvedUuids = collect($validated['approved_uuids'] ?? []);
        $rejectionReason = $validated['rejection_reason'] ?? null;

        $payments = InvestmentPaymentRequest::query()
            ->where('batch_id', $batch->id)
            ->where('status', 'projectmanager_approved')
            ->with('user')
            ->get();

        DB::transaction(function () use ($batch, $payments, $approvedUuids, $rejectionReason) {
            foreach ($payments as $payment) {
                if ($approvedUuids->contains($payment->uuid)) {
                    $payment->update([
                        'status' => 'final_approved',
                        'final_reviewed_at' => now(),
                    ]);
                } else {
                    $payment->update([
                        'status' => 'final_rejected',
                        'final_rejection_reason' => $rejectionReason,
                        'final_reviewed_at' => now(),
                    ]);
                }
            }

            $hasApproved = $payments->contains(fn ($p) => $approvedUuids->contains($p->uuid));

            $batch->update([
                'status' => $hasApproved ? 'final_approved' : 'final_rejected',
                'final_ceo_reviewed_at' => now(),
                'final_ceo_approval_token' => null,
                'final_ceo_approval_token_expires_at' => null,
            ]);
        });

        $approvedCount = 0;
        $rejectedCount = 0;

        foreach ($payments as $payment) {
            $stage = $approvedUuids->contains($payment->uuid) ? 'final_approved' : 'final_rejected';
            $reason = $stage === 'final_rejected' ? $rejectionReason : null;

            if ($stage === 'final_approved') {
                $approvedCount++;
            } else {
                $rejectedCount++;
            }

            $adjustedAmount = null;
            if ($stage === 'final_approved' && $payment->approved_amount !== null && (float) $payment->approved_amount < (float) $payment->total) {
                $adjustedAmount = number_format((float) $payment->approved_amount, 2, '.', '');
            }

            if ($payment->user) {
                $payment->user->notify(new InvestmentPaymentStatusNotification($payment, $stage, $reason, $adjustedAmount));
            }
        }

        return redirect()->route('investment-batch-approval.success', [
            'approved' => $approvedCount,
            'rejected' => $rejectedCount,
            'final' => 1,
        ]);
    }

    public function showFinalSession(string $token): Response
    {
        $batches = InvestmentPaymentBatch::query()
            ->where('final_session_token', $token)
            ->with([
                'department',
                'project',
                'user',
                'paymentRequests' => fn ($q) => $q->where('status', 'projectmanager_approved'),
                'paymentRequests.investmentRequest.investmentExpenseConcept',
                'paymentRequests.currency',
            ])
            ->orderBy('department_id')
            ->get();

        if ($batches->isEmpty()) {
            return Inertia::render('investment-batch-approval/invalid', [
                'reason' => 'El enlace de aprobación final no es válido o no existe.',
            ]);
        }

        $firstBatch = $batches->first();

        if (! $firstBatch->hasValidFinalSessionToken()) {
            return Inertia::render('investment-batch-approval/invalid', [
                'reason' => 'El enlace de aprobación final ha expirado.',
            ]);
        }

        $pendingBatches = $batches->where('status', 'final_pending');

        if ($pendingBatches->isEmpty()) {
            return Inertia::render('investment-batch-approval/invalid', [
                'reason' => 'Estos lotes ya fueron procesados anteriormente.',
            ]);
        }

        $departmentGroups = $pendingBatches->map(fn (InvestmentPaymentBatch $batch) => [
            'batch_uuid' => $batch->uuid,
            'department' => $batch->department->name ?? '-',
            'week_number' => $batch->week_number,
            'year' => $batch->year,
            'payments' => $batch->paymentRequests->map(fn (InvestmentPaymentRequest $p) => [
                'uuid' => $p->uuid,
                'folio_number' => $p->folio_number,
                'concept' => $p->investmentRequest?->investmentExpenseConcept?->name ?? '-',
                'provider' => $p->provider,
                'rfc' => $p->rfc,
                'currency_prefix' => $p->currency?->prefix ?? 'MXN',
                'total' => (string) $p->total,
                'approved_amount' => $p->approved_amount !== null ? (string) $p->approved_amount : (string) $p->total,
                'was_adjusted' => $p->approved_amount !== null && (float) $p->approved_amount < (float) $p->total,
            ])->values(),
            'subtotal' => number_format(
                (float) $batch->paymentRequests->sum(fn ($p) => (float) ($p->approved_amount ?? $p->total)),
                2, '.', '',
            ),
        ])->values();

        $grandTotal = $pendingBatches->sum(
            fn ($b) => $b->paymentRequests->sum(fn ($p) => (float) ($p->approved_amount ?? $p->total)),
        );

        $totalPaymentsCount = $pendingBatches->sum(fn ($b) => $b->paymentRequests->count());

        return Inertia::render('investment-batch-approval/show-final-session', [
            'session' => [
                'token' => $token,
                'project' => $firstBatch->project->name ?? '-',
                'requester' => $firstBatch->user->name ?? '-',
                'expires_at' => $firstBatch->final_session_token_expires_at?->toISOString(),
                'batches_count' => $pendingBatches->count(),
                'total_payments_count' => $totalPaymentsCount,
                'grand_total' => number_format((float) $grandTotal, 2, '.', ''),
                'department_groups' => $departmentGroups,
            ],
        ]);
    }

    public function reviewFinalSession(Request $request, string $token): RedirectResponse
    {
        $batches = InvestmentPaymentBatch::query()
            ->where('final_session_token', $token)
            ->get();

        abort_if($batches->isEmpty(), 410, 'El enlace de aprobación final no es válido.');

        $firstBatch = $batches->first();

        abort_if(! $firstBatch->hasValidFinalSessionToken(), 410, 'El enlace de aprobación final ha expirado.');

        $pendingBatches = $batches->where('status', 'final_pending');

        abort_if($pendingBatches->isEmpty(), 410, 'Estos lotes ya fueron procesados anteriormente.');

        $validated = $request->validate([
            'approved_uuids' => ['array'],
            'approved_uuids.*' => ['string', 'uuid'],
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $approvedUuids = collect($validated['approved_uuids'] ?? []);
        $rejectionReason = $validated['rejection_reason'] ?? null;

        $pendingBatchIds = $pendingBatches->pluck('id');

        $payments = InvestmentPaymentRequest::query()
            ->whereIn('batch_id', $pendingBatchIds)
            ->where('status', 'projectmanager_approved')
            ->with('user')
            ->get();

        DB::transaction(function () use ($pendingBatches, $payments, $approvedUuids, $rejectionReason) {
            foreach ($payments as $payment) {
                if ($approvedUuids->contains($payment->uuid)) {
                    $payment->update([
                        'status' => 'final_approved',
                        'final_reviewed_at' => now(),
                    ]);
                } else {
                    $payment->update([
                        'status' => 'final_rejected',
                        'final_rejection_reason' => $rejectionReason,
                        'final_reviewed_at' => now(),
                    ]);
                }
            }

            // Update each batch's status independently based on its own payments.
            foreach ($pendingBatches as $batch) {
                $batchPayments = $payments->where('batch_id', $batch->id);
                $hasApproved = $batchPayments->contains(fn ($p) => $approvedUuids->contains($p->uuid));

                $batch->update([
                    'status' => $hasApproved ? 'final_approved' : 'final_rejected',
                    'final_ceo_reviewed_at' => now(),
                    'final_ceo_approval_token' => null,
                    'final_ceo_approval_token_expires_at' => null,
                    'final_session_token' => null,
                    'final_session_token_expires_at' => null,
                ]);
            }
        });

        $approvedCount = 0;
        $rejectedCount = 0;

        foreach ($payments as $payment) {
            $stage = $approvedUuids->contains($payment->uuid) ? 'final_approved' : 'final_rejected';
            $reason = $stage === 'final_rejected' ? $rejectionReason : null;

            if ($stage === 'final_approved') {
                $approvedCount++;
            } else {
                $rejectedCount++;
            }

            $adjustedAmount = null;
            if ($stage === 'final_approved' && $payment->approved_amount !== null && (float) $payment->approved_amount < (float) $payment->total) {
                $adjustedAmount = number_format((float) $payment->approved_amount, 2, '.', '');
            }

            if ($payment->user) {
                $payment->user->notify(new InvestmentPaymentStatusNotification($payment, $stage, $reason, $adjustedAmount));
            }
        }

        return redirect()->route('investment-batch-approval.success', [
            'approved' => $approvedCount,
            'rejected' => $rejectedCount,
            'final' => 1,
        ]);
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

    private function resolveInvalidFinalReason(?InvestmentPaymentBatch $batch): string
    {
        if (! $batch) {
            return 'El enlace de aprobación final no es válido o no existe.';
        }

        if ($batch->status !== 'final_pending') {
            return 'Este lote ya fue procesado anteriormente en la aprobación final.';
        }

        if (! $batch->hasValidFinalCeoToken()) {
            return 'El enlace de aprobación final ha expirado.';
        }

        return 'El enlace de aprobación final no es válido.';
    }
}
