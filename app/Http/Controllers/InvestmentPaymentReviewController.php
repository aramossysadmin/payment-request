<?php

namespace App\Http\Controllers;

use App\Models\InvestmentPaymentBatch;
use App\Models\InvestmentPaymentRequest;
use App\Models\InvestmentRequest;
use App\Models\Project;
use App\Models\User;
use App\Notifications\InvestmentBatchFinalApprovalSessionNotification;
use App\Notifications\InvestmentBatchRequesterSummaryNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class InvestmentPaymentReviewController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Lista de proyectos visibles para el usuario (mismo patrón que en otros recursos).
        $projectIds = InvestmentRequest::query()
            ->visibleTo($user)
            ->whereNotNull('project_id')
            ->pluck('project_id')
            ->unique();

        $projects = Project::whereIn('id', $projectIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Project $p) => ['id' => $p->id, 'name' => $p->name])
            ->values();

        $selectedProjectId = $request->integer('project_id') ?: null;

        // Si no hay proyecto seleccionado (o no pertenece al usuario), retornar groups vacío.
        if (! $selectedProjectId || ! $projectIds->contains($selectedProjectId)) {
            return Inertia::render('investment-payment-review/index', [
                'groups' => [],
                'totalCount' => 0,
                'projects' => $projects,
                'selectedProjectId' => null,
            ]);
        }

        $payments = InvestmentPaymentRequest::query()
            ->whereIn('status', ['ceo_approved', 'projectmanager_review'])
            ->whereHas('investmentRequest', fn ($q) => $q->where('project_id', $selectedProjectId))
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

        // Precalcular el desglose de presupuesto UNA vez por grupo de concepto (evita N+1:
        // los pagos del mismo concepto comparten el mismo saldo disponible).
        $keyFor = fn (InvestmentRequest $ir): string => ($ir->investment_expense_concept_id && $ir->project_id)
            ? 'c:'.$ir->project_id.':'.$ir->investment_expense_concept_id.':'.$ir->department_id
            : 'r:'.$ir->id;

        $budgetByKey = [];
        foreach ($payments as $p) {
            if ($ir = $p->investmentRequest) {
                $budgetByKey[$keyFor($ir)] ??= $ir->budgetBreakdown();
            }
        }

        $grouped = $payments->groupBy(fn (InvestmentPaymentRequest $p) => $p->department?->name ?? 'Sin departamento')
            ->map(function ($departmentPayments, string $departmentName) use ($budgetByKey, $keyFor) {
                return [
                    'department' => $departmentName,
                    'count' => $departmentPayments->count(),
                    'total' => number_format((float) $departmentPayments->sum(fn ($p) => (float) $p->total), 2, '.', ''),
                    'payments' => $departmentPayments->map(function (InvestmentPaymentRequest $p) use ($budgetByKey, $keyFor) {
                        $bd = ($ir = $p->investmentRequest)
                            ? $budgetByKey[$keyFor($ir)]
                            : ['budget' => 0.0, 'committed' => 0.0, 'paid' => 0.0, 'available' => 0.0];

                        return [
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
                            'payment_week_number' => $p->payment_week_number,
                            'budget_total' => number_format($bd['budget'], 2, '.', ''),
                            'budget_committed' => number_format($bd['committed'], 2, '.', ''),
                            'budget_paid' => number_format($bd['paid'], 2, '.', ''),
                            'budget_available' => number_format($bd['available'], 2, '.', ''),
                        ];
                    })->values(),
                ];
            })
            ->values();

        return Inertia::render('investment-payment-review/index', [
            'groups' => $grouped,
            'totalCount' => $payments->count(),
            'projects' => $projects,
            'selectedProjectId' => $selectedProjectId,
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

        $affectedBatchIds = $payments->pluck('batch_id')->filter()->unique()->values();

        DB::transaction(function () use ($payments, $decisionsByUuid, $rejectionReason, $affectedBatchIds) {
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

            // For each affected batch, check if PM finished it and transition to final_pending
            foreach ($affectedBatchIds as $batchId) {
                $batch = InvestmentPaymentBatch::find($batchId);
                if (! $batch) {
                    continue;
                }

                // Check if any payment of this batch is still pending PM review
                $pendingPmCount = InvestmentPaymentRequest::query()
                    ->where('batch_id', $batchId)
                    ->whereIn('status', ['ceo_approved', 'projectmanager_review'])
                    ->count();

                if ($pendingPmCount > 0) {
                    continue;
                }

                // Check if there are any PM-approved payments to send to CEO final
                $pmApprovedCount = InvestmentPaymentRequest::query()
                    ->where('batch_id', $batchId)
                    ->where('status', 'projectmanager_approved')
                    ->count();

                if ($pmApprovedCount > 0) {
                    $batch->update([
                        'status' => 'final_pending',
                        'final_ceo_approval_token' => (string) Str::uuid(),
                        'final_ceo_approval_token_expires_at' => Carbon::now()->addHours(96),
                    ]);
                } else {
                    $batch->update(['status' => 'projectmanager_rejected']);
                }
            }
        });

        // Notify requesters — 1 consolidated email per requester (mismo patrón que Fase 2 CEO).
        $approvedCount = 0;
        $rejectedCount = 0;
        $approvedByUser = collect();
        $rejectedByUser = collect();

        foreach ($payments as $payment) {
            $decision = $decisionsByUuid[$payment->uuid];

            if ($decision['approved']) {
                $approvedCount++;
                $approvedByUser->push($payment);
            } else {
                $rejectedCount++;
                $rejectedByUser->push($payment);
            }
        }

        $allReviewedPayments = $approvedByUser->merge($rejectedByUser);
        $allReviewedPayments->groupBy('user_id')->each(function ($userPayments) use ($approvedByUser, $rejectionReason) {
            $user = $userPayments->first()->user;
            if (! $user) {
                return;
            }

            $approvedUuids = $approvedByUser->pluck('uuid');
            $approved = $userPayments->filter(fn ($p) => $approvedUuids->contains($p->uuid))->values();
            $rejected = $userPayments->reject(fn ($p) => $approvedUuids->contains($p->uuid))->values();

            $user->notify(new InvestmentBatchRequesterSummaryNotification(
                $approved,
                $rejected,
                $rejectionReason,
                'projectmanager',
            ));
        });

        // Batches transitioned to final_pending in this PM submit (en esta sesión del PM).
        // Por convención (Combobox en /investment-payment-review fuerza 1 proyecto por sesión),
        // todos estos batches deberían ser del mismo proyecto. Por seguridad defensiva agrupamos
        // por project_id por si la request fue manipulada y mezcla proyectos.
        $batchesToNotify = InvestmentPaymentBatch::query()
            ->whereIn('id', $affectedBatchIds)
            ->where('status', 'final_pending')
            ->whereNotNull('final_ceo_approval_token')
            ->with(['project'])
            ->get();

        // Asignar un session_token único POR PROYECTO. Todos los batches del mismo proyecto
        // comparten el mismo session_token + expiración. El CEO recibe 1 email por proyecto
        // con todos sus batches consolidados en una sola vista.
        $batchesByProject = $batchesToNotify->groupBy('project_id');

        foreach ($batchesByProject as $projectBatches) {
            $sessionToken = (string) Str::uuid();
            $sessionExpiresAt = Carbon::now()->addHours(96);

            foreach ($projectBatches as $batch) {
                $batch->update([
                    'final_session_token' => $sessionToken,
                    'final_session_token_expires_at' => $sessionExpiresAt,
                ]);
            }
        }

        // Notify CEO(s) — role-based, soporta múltiples personas con el rol 'ceo'.
        // Si nadie tiene el rol asignado, los emails no se envían (silencioso, sin error).
        // Se envía 1 notification consolidada por proyecto (no 1 por batch).
        User::role('ceo')->get()->each(function ($ceo) use ($batchesByProject) {
            foreach ($batchesByProject as $projectBatches) {
                $sessionToken = $projectBatches->first()->final_session_token;
                $ceo->notify(new InvestmentBatchFinalApprovalSessionNotification($projectBatches, $sessionToken));
            }
        });

        return redirect()
            ->route('investment-payment-review.index')
            ->with('success', sprintf(
                'Revisión enviada: %d aprobados, %d rechazados. Los solicitantes fueron notificados.',
                $approvedCount,
                $rejectedCount,
            ));
    }
}
