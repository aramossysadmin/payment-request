<?php

namespace App\Http\Controllers;

use App\Http\Resources\InvestmentRequestResource;
use App\Models\Branch;
use App\Models\Currency;
use App\Models\InvestmentPaymentBatch;
use App\Models\InvestmentPaymentRequest;
use App\Models\InvestmentRequest;
use App\Models\Project;
use App\States\InvestmentRequest\Completed;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class InvestmentSheetConsolidatedController extends Controller
{
    public function __invoke(Request $request, Project $project): Response
    {
        $user = $request->user();

        // Default department filter to user's department on first load
        $departmentId = $request->filled('department_id')
            ? ($request->input('department_id') ?: null)
            : (string) $user->department_id;

        $query = InvestmentRequest::query()
            ->with(['user', 'department', 'currency', 'branch', 'expenseConcept', 'investmentExpenseConcept', 'approvals.user'])
            ->where('project_id', $project->id)
            ->visibleTo($user);

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('provider', 'like', "%{$search}%")
                    ->orWhere('invoice_folio', 'like', "%{$search}%")
                    ->orWhere('folio_number', 'like', "%{$search}%")
                    ->orWhereHas('investmentExpenseConcept', fn ($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->whereState('status', $request->string('status')->toString());
        }

        if ($departmentId) {
            $query->where('department_id', (int) $departmentId);
        }

        $investmentRequests = $query->latest()->paginate(50)->withQueryString();

        // Compute grouped budget totals (base + addendums with same concept + project)
        $investmentRequests->getCollection()->each(function (InvestmentRequest $ir) use ($project) {
            $ir->setAttribute('remaining_balance', $ir->remaining_balance);

            if ($ir->investment_expense_concept_id) {
                $groupIds = InvestmentRequest::query()
                    ->where('project_id', $project->id)
                    ->where('investment_expense_concept_id', $ir->investment_expense_concept_id)
                    ->where('department_id', $ir->department_id)
                    ->whereState('status', Completed::class)
                    ->pluck('id');

                $groupBudget = InvestmentRequest::query()
                    ->whereIn('id', $groupIds)
                    ->sum('total');

                $groupPaid = InvestmentPaymentRequest::query()
                    ->whereIn('investment_request_id', $groupIds)
                    ->whereIn('status', ['pending_approval', 'approved'])
                    ->sum('total');

                $ir->setAttribute('group_budget', number_format((float) $groupBudget, 2, '.', ''));
                $ir->setAttribute('group_paid', number_format((float) $groupPaid, 2, '.', ''));
                $ir->setAttribute('group_remaining', number_format((float) ($groupBudget - $groupPaid), 2, '.', ''));
            } else {
                $ir->setAttribute('group_budget', (string) $ir->total);
                $ir->setAttribute('group_paid', '0.00');
                $ir->setAttribute('group_remaining', $ir->remaining_balance);
            }
        });

        $totals = InvestmentRequest::query()
            ->where('project_id', $project->id)
            ->visibleTo($user)
            ->selectRaw("SUM(subtotal) as total_subtotal, SUM(total) as total_total, COUNT(*) as total_count, SUM(CASE WHEN status = 'completed' THEN total ELSE 0 END) as authorized_total, SUM(CASE WHEN status != 'completed' THEN total ELSE 0 END) as pending_total")
            ->first();

        $departmentBreakdown = InvestmentRequest::query()
            ->where('project_id', $project->id)
            ->visibleTo($user)
            ->join('departments', 'investment_requests.department_id', '=', 'departments.id')
            ->selectRaw('departments.id as department_id, departments.name as department_name, SUM(investment_requests.total) as department_total, COUNT(*) as department_count')
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('department_total')
            ->get();

        $visibleIrIds = InvestmentRequest::query()
            ->where('project_id', $project->id)
            ->visibleTo($user)
            ->pluck('id');

        $paidByDepartment = InvestmentPaymentRequest::query()
            ->join('investment_requests', 'investment_payment_requests.investment_request_id', '=', 'investment_requests.id')
            ->whereIn('investment_payment_requests.investment_request_id', $visibleIrIds)
            ->whereIn('investment_payment_requests.status', ['pending_approval', 'approved'])
            ->groupBy('investment_requests.department_id')
            ->selectRaw('investment_requests.department_id, SUM(investment_payment_requests.total) as paid_total')
            ->pluck('paid_total', 'department_id');

        $project->load('branch');

        $now = Carbon::now();
        $currentWeek = $now->isoWeek;
        $currentYear = $now->isoWeekYear;

        $draftBatch = InvestmentPaymentBatch::query()
            ->where('department_id', $user->department_id)
            ->where('project_id', $project->id)
            ->where('week_number', $currentWeek)
            ->where('year', $currentYear)
            ->where('status', 'draft')
            ->first();

        $draftPayments = $draftBatch
            ? InvestmentPaymentRequest::query()
                ->where('batch_id', $draftBatch->id)
                ->where('status', 'draft')
                ->with(['currency', 'investmentRequest.investmentExpenseConcept'])
                ->latest()
                ->get()
                ->map(function (InvestmentPaymentRequest $p) {
                    $effectiveRemaining = null;
                    $ir = $p->investmentRequest;
                    if ($ir && $ir->investment_expense_concept_id && $ir->project_id) {
                        $groupIds = InvestmentRequest::query()
                            ->where('project_id', $ir->project_id)
                            ->where('investment_expense_concept_id', $ir->investment_expense_concept_id)
                            ->where('department_id', $ir->department_id)
                            ->whereState('status', Completed::class)
                            ->pluck('id');

                        $groupBudget = (float) InvestmentRequest::whereIn('id', $groupIds)->sum('total');
                        $groupPaidExcludingThis = (float) InvestmentPaymentRequest::query()
                            ->whereIn('investment_request_id', $groupIds)
                            ->whereNotIn('status', ['rejected', 'ceo_rejected', 'projectmanager_rejected', 'final_rejected'])
                            ->where('id', '!=', $p->id)
                            ->sum('total');

                        $effectiveRemaining = $groupBudget - $groupPaidExcludingThis;
                    }

                    return [
                        'id' => $p->id,
                        'uuid' => $p->uuid,
                        'folio_number' => $p->folio_number,
                        'provider' => $p->provider,
                        'rfc' => $p->rfc,
                        'invoice_folio' => $p->invoice_folio,
                        'concept_name' => $p->investmentRequest?->investmentExpenseConcept?->name ?? '—',
                        'concept_folio' => $p->investmentRequest?->folio_number,
                        'description' => $p->description,
                        'currency_prefix' => $p->currency?->prefix ?? 'MXN',
                        'currency_id' => $p->currency_id,
                        'branch_id' => $p->branch_id,
                        'investment_request_id' => $p->investment_request_id,
                        'payment_provision_date' => $p->payment_provision_date?->toDateString(),
                        'is_invoice' => $p->payment_type === 'factura',
                        'iva_rate' => $p->iva_rate?->value,
                        'retention' => (bool) $p->retention,
                        'subtotal' => (string) $p->subtotal,
                        'iva' => (string) $p->iva,
                        'total' => (string) $p->total,
                        'advance_documents' => array_values(array_filter($p->advance_documents ?? [], fn ($d) => is_string($d) && $d !== '')),
                        'concept_effective_remaining' => $effectiveRemaining !== null ? number_format($effectiveRemaining, 2, '.', '') : null,
                    ];
                })
            : collect();

        $authorizedPayments = InvestmentPaymentRequest::query()
            ->where('status', 'final_approved')
            ->where('user_id', $user->id)
            ->whereHas('investmentRequest', fn ($q) => $q->where('project_id', $project->id))
            ->with(['currency', 'investmentRequest.investmentExpenseConcept'])
            ->latest()
            ->get()
            ->map(fn (InvestmentPaymentRequest $p) => [
                'id' => $p->id,
                'uuid' => $p->uuid,
                'folio_number' => $p->folio_number,
                'provider' => $p->provider,
                'concept_name' => $p->investmentRequest?->investmentExpenseConcept?->name ?? '—',
                'description' => $p->description,
                'currency_prefix' => $p->currency?->prefix ?? 'MXN',
                'total' => (string) $p->total,
                'approved_amount' => $p->approved_amount !== null ? (string) $p->approved_amount : (string) $p->total,
                'was_adjusted' => $p->approved_amount !== null && (float) $p->approved_amount < (float) $p->total,
                'status' => $p->status,
                'has_documents' => is_array($p->advance_documents) && count($p->advance_documents) >= 2,
            ]);

        // Historial de pagos del departamento del usuario para el proyecto actual.
        // Excluye drafts (esos ya aparecen en la tarjeta Pagos en Borrador).
        $userPaymentHistory = InvestmentPaymentRequest::query()
            ->where('department_id', $user->department_id)
            ->where('status', '!=', 'draft')
            ->whereHas('investmentRequest', fn ($q) => $q->where('project_id', $project->id))
            ->with(['currency', 'investmentRequest.investmentExpenseConcept', 'branch'])
            ->latest('created_at')
            ->get()
            ->map(fn (InvestmentPaymentRequest $p) => [
                'id' => $p->id,
                'uuid' => $p->uuid,
                'folio_number' => $p->folio_number,
                'provider' => $p->provider,
                'rfc' => $p->rfc,
                'concept_name' => $p->investmentRequest?->investmentExpenseConcept?->name ?? '—',
                'concept_folio' => $p->investmentRequest?->folio_number,
                'branch' => $p->branch?->name ?? '—',
                'payment_type' => $p->payment_type,
                'payment_provision_date' => $p->payment_provision_date?->toDateString(),
                'description' => $p->description,
                'currency_prefix' => $p->currency?->prefix ?? 'MXN',
                'subtotal' => (string) $p->subtotal,
                'iva' => (string) $p->iva,
                'total' => (string) $p->total,
                'approved_amount' => $p->approved_amount !== null ? (string) $p->approved_amount : null,
                'was_adjusted' => $p->approved_amount !== null && (float) $p->approved_amount < (float) $p->total,
                'status' => $p->status,
                'is_legacy' => $p->batch_id === null,
                'ceo_rejection_reason' => $p->ceo_rejection_reason,
                'pm_rejection_reason' => $p->pm_rejection_reason,
                'final_rejection_reason' => $p->final_rejection_reason,
                'created_at' => $p->created_at?->toISOString(),
                'ceo_reviewed_at' => $p->ceo_reviewed_at?->toISOString(),
                'pm_reviewed_at' => $p->pm_reviewed_at?->toISOString(),
                'final_reviewed_at' => $p->final_reviewed_at?->toISOString(),
                'has_documents' => is_array($p->advance_documents) && count($p->advance_documents) >= 2,
                'documents' => collect(is_array($p->advance_documents) ? $p->advance_documents : [])
                    ->filter(fn ($doc) => is_string($doc) && $doc !== '')
                    ->map(fn ($doc) => [
                        'name' => basename($doc),
                        'url' => URL::temporarySignedRoute(
                            'documents.view',
                            now()->addHours(48),
                            ['path' => $doc],
                        ),
                    ])
                    ->values()
                    ->toArray(),
            ]);

        return Inertia::render('investment-sheets/consolidated', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'branch' => $project->branch?->name,
            ],
            'totals' => [
                'subtotal' => number_format((float) ($totals->total_subtotal ?? 0), 2, '.', ''),
                'total' => number_format((float) ($totals->total_total ?? 0), 2, '.', ''),
                'authorized' => number_format((float) ($totals->authorized_total ?? 0), 2, '.', ''),
                'pending' => number_format((float) ($totals->pending_total ?? 0), 2, '.', ''),
                'count' => (int) ($totals->total_count ?? 0),
            ],
            'departmentBreakdown' => $departmentBreakdown->map(function ($d) use ($paidByDepartment) {
                $total = (float) $d->department_total;
                $paid = (float) ($paidByDepartment[$d->department_id] ?? 0);
                $pending = max(0, $total - $paid);
                $percentPaid = $total > 0 ? ($paid / $total) * 100 : 0;

                return [
                    'id' => $d->department_id,
                    'name' => $d->department_name,
                    'total' => number_format($total, 2, '.', ''),
                    'paid' => number_format($paid, 2, '.', ''),
                    'pending' => number_format($pending, 2, '.', ''),
                    'percent_paid' => round($percentPaid, 1),
                    'count' => (int) $d->department_count,
                ];
            }),
            'investmentRequests' => InvestmentRequestResource::collection($investmentRequests),
            'filters' => [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'department_id' => $departmentId,
            ],
            'userDepartmentId' => $user->department_id,
            'userDepartmentName' => $user->department?->name,
            'currencies' => Currency::all(['id', 'name', 'prefix']),
            'branches' => Branch::orderBy('name')->get(['id', 'name']),
            'draftBatch' => $draftBatch ? [
                'uuid' => $draftBatch->uuid,
                'week_number' => $draftBatch->week_number,
                'year' => $draftBatch->year,
                'payments' => $draftPayments,
                'total' => number_format((float) $draftPayments->sum(fn ($p) => (float) $p['total']), 2, '.', ''),
            ] : null,
            'authorizedPayments' => [
                'payments' => $authorizedPayments,
            ],
            'userPaymentHistory' => $userPaymentHistory,
        ]);
    }
}
