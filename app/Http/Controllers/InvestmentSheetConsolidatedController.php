<?php

namespace App\Http\Controllers;

use App\Http\Resources\InvestmentRequestResource;
use App\Models\Branch;
use App\Models\Currency;
use App\Models\Department;
use App\Models\InvestmentExpenseConcept;
use App\Models\InvestmentPaymentBatch;
use App\Models\InvestmentPaymentRequest;
use App\Models\InvestmentRequest;
use App\Models\Project;
use App\Services\PaymentRequestPolicyService;
use App\States\InvestmentRequest\Completed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class InvestmentSheetConsolidatedController extends Controller
{
    /**
     * Suma de `total` normalizada a MXN (cada fila × su tipo de cambio).
     */
    private function sumTotalMxn(Builder $query): float
    {
        $table = $query->getModel()->getTable();

        return (float) $query->sum(DB::raw(
            "{$table}.total * (select exchange_rate from currencies where currencies.id = {$table}.currency_id)"
        ));
    }

    public function __invoke(Request $request, Project $project): Response
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole('super_admin');
        $canSeeAllDepartments = $user->hasAnyRole(['super_admin', 'ceo', 'project_manager']);
        $canEditRequestConcept = $user->hasAnyRole(['super_admin', 'ceo', 'project_manager']);

        // Default department filter:
        // - User mono-dpto: default a su único dpto (comportamiento original).
        // - User multi-dpto: default a "Todos" (null) para que vea todas sus solicitudes.
        $isMultiDept = $user->departments()->count() > 1;

        $departmentId = $request->filled('department_id')
            ? ($request->input('department_id') ?: null)
            : ($isMultiDept ? null : (string) $user->department_id);

        $query = InvestmentRequest::query()
            ->with(['user', 'department', 'currency', 'branch', 'expenseConcept', 'investmentExpenseConcept.category', 'approvals.user'])
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
            $irRate = (float) ($ir->currency?->exchange_rate ?? 1);
            $irBudgetMxn = (float) $ir->total * $irRate;

            $paidMxn = $this->sumTotalMxn(
                InvestmentPaymentRequest::query()
                    ->where('investment_request_id', $ir->id)
                    ->whereIn('status', ['pending_approval', 'approved'])
            );

            $ir->setAttribute('remaining_balance', number_format($irBudgetMxn - $paidMxn, 2, '.', ''));

            if ($ir->investment_expense_concept_id) {
                $groupIds = InvestmentRequest::query()
                    ->where('project_id', $project->id)
                    ->where('investment_expense_concept_id', $ir->investment_expense_concept_id)
                    ->where('department_id', $ir->department_id)
                    ->whereState('status', Completed::class)
                    ->pluck('id');

                $groupBudget = $this->sumTotalMxn(
                    InvestmentRequest::query()->whereIn('id', $groupIds)
                );

                $groupPaid = $this->sumTotalMxn(
                    InvestmentPaymentRequest::query()
                        ->whereIn('investment_request_id', $groupIds)
                        ->whereIn('status', ['pending_approval', 'approved'])
                );

                $ir->setAttribute('group_budget', number_format($groupBudget, 2, '.', ''));
                $ir->setAttribute('group_paid', number_format($groupPaid, 2, '.', ''));
                $ir->setAttribute('group_remaining', number_format($groupBudget - $groupPaid, 2, '.', ''));
            } else {
                $ir->setAttribute('group_budget', number_format($irBudgetMxn, 2, '.', ''));
                $ir->setAttribute('group_paid', '0.00');
                $ir->setAttribute('group_remaining', number_format($irBudgetMxn - $paidMxn, 2, '.', ''));
            }
        });

        $totals = InvestmentRequest::query()
            ->where('project_id', $project->id)
            ->visibleTo($user)
            ->join('currencies', 'investment_requests.currency_id', '=', 'currencies.id')
            ->selectRaw("SUM(investment_requests.subtotal * currencies.exchange_rate) as total_subtotal, SUM(investment_requests.total * currencies.exchange_rate) as total_total, COUNT(*) as total_count, SUM(CASE WHEN investment_requests.status = 'completed' THEN investment_requests.total * currencies.exchange_rate ELSE 0 END) as authorized_total, SUM(CASE WHEN investment_requests.status != 'completed' THEN investment_requests.total * currencies.exchange_rate ELSE 0 END) as pending_total")
            ->first();

        $departmentBreakdown = InvestmentRequest::query()
            ->where('project_id', $project->id)
            ->visibleTo($user)
            ->join('departments', 'investment_requests.department_id', '=', 'departments.id')
            ->join('currencies', 'investment_requests.currency_id', '=', 'currencies.id')
            ->selectRaw('departments.id as department_id, departments.name as department_name, SUM(investment_requests.total * currencies.exchange_rate) as department_total, COUNT(*) as department_count')
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('department_total')
            ->get();

        $visibleIrIds = InvestmentRequest::query()
            ->where('project_id', $project->id)
            ->visibleTo($user)
            ->pluck('id');

        $paidByDepartment = InvestmentPaymentRequest::query()
            ->join('investment_requests', 'investment_payment_requests.investment_request_id', '=', 'investment_requests.id')
            ->join('currencies', 'investment_payment_requests.currency_id', '=', 'currencies.id')
            ->whereIn('investment_payment_requests.investment_request_id', $visibleIrIds)
            ->whereIn('investment_payment_requests.status', ['pending_approval', 'approved'])
            ->groupBy('investment_requests.department_id')
            ->selectRaw('investment_requests.department_id, SUM(investment_payment_requests.total * currencies.exchange_rate) as paid_total')
            ->pluck('paid_total', 'department_id');

        $project->load('branch', 'currency');

        $now = Carbon::now();
        $currentWeek = $now->isoWeek;
        $currentYear = $now->isoWeekYear;

        // El draftBatch se asocia al departamento del filtro actual de la vista
        // (que por default es el principal del user). Para users multi-dpto, al cambiar
        // el filtro de dpto, se muestra el draft correspondiente a ese dpto.
        $draftBatchDepartmentId = $departmentId ? (int) $departmentId : $user->department_id;

        $draftBatch = $draftBatchDepartmentId
            ? InvestmentPaymentBatch::query()
                ->where('department_id', $draftBatchDepartmentId)
                ->where('project_id', $project->id)
                ->where('week_number', $currentWeek)
                ->where('year', $currentYear)
                ->where('status', 'draft')
                ->first()
            : null;

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

                        $groupBudget = $this->sumTotalMxn(
                            InvestmentRequest::query()->whereIn('id', $groupIds)
                        );
                        $groupPaidExcludingThis = $this->sumTotalMxn(
                            InvestmentPaymentRequest::query()
                                ->whereIn('investment_request_id', $groupIds)
                                ->whereNotIn('status', ['rejected', 'ceo_rejected', 'projectmanager_rejected', 'final_rejected', 'auto_cancelled'])
                                ->where('id', '!=', $p->id)
                        );

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
                        'payment_type' => $p->payment_type,
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
            ->whereHas('investmentRequest', fn ($q) => $q->where('project_id', $project->id))
            ->when($isSuperAdmin,
                fn ($q) => $q->where(function ($q) {
                    $q->where('status', 'final_approved')
                        ->orWhere(fn ($q) => $q->where('status', 'approved')->whereNull('batch_id'));
                }),
                fn ($q) => $q->where('status', 'final_approved')->where('user_id', $user->id),
            )
            ->with(['currency', 'investmentRequest.investmentExpenseConcept.category', 'user', 'department'])
            ->latest()
            ->get()
            ->map(fn (InvestmentPaymentRequest $p) => [
                'id' => $p->id,
                'uuid' => $p->uuid,
                'folio_number' => $p->folio_number,
                'provider' => $p->provider,
                'concept_name' => $p->investmentRequest?->investmentExpenseConcept?->name ?? '—',
                'category_name' => $p->investmentRequest?->investmentExpenseConcept?->category?->name ?? '—',
                'department_name' => $p->department?->name ?? '—',
                'user_name' => $p->user?->name ?? '—',
                'description' => $p->description,
                'currency_prefix' => $p->currency?->prefix ?? 'MXN',
                'currency_id' => $p->currency_id,
                'total' => (string) $p->total,
                'approved_amount' => $p->approved_amount !== null ? (string) $p->approved_amount : (string) $p->total,
                'was_adjusted' => $p->approved_amount !== null && (float) $p->approved_amount < (float) $p->total,
                'status' => $p->status,
                'payment_type' => $p->payment_type,
                'is_legacy' => $p->batch_id === null,
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

        // Historial de pagos del proyecto actual. Por defecto solo del departamento del usuario;
        // los roles super_admin/ceo/project_manager reciben TODOS los pagos (filtrado por dpto se hace
        // en cliente con un selector). Excluye drafts (esos ya aparecen en la tarjeta Pagos en Borrador).
        $userPaymentHistory = InvestmentPaymentRequest::query()
            ->when(! $canSeeAllDepartments, fn ($q) => $q->whereIn('department_id', $user->departments()->pluck('departments.id')))
            ->where('status', '!=', 'draft')
            ->whereHas('investmentRequest', fn ($q) => $q->where('project_id', $project->id))
            ->with(['user', 'currency', 'investmentRequest.investmentExpenseConcept.category', 'branch', 'department'])
            ->latest('created_at')
            ->get()
            ->map(fn (InvestmentPaymentRequest $p) => [
                'id' => $p->id,
                'uuid' => $p->uuid,
                'folio_number' => $p->folio_number,
                'provider' => $p->provider,
                'rfc' => $p->rfc,
                'invoice_folio' => $p->invoice_folio,
                'concept_name' => $p->investmentRequest?->investmentExpenseConcept?->name ?? '—',
                'concept_folio' => $p->investmentRequest?->folio_number,
                'category_name' => $p->investmentRequest?->investmentExpenseConcept?->category?->name ?? '—',
                'user_name' => $p->user?->name ?? '—',
                'branch' => $p->branch?->name ?? '—',
                'department_id' => $p->department_id,
                'department_name' => $p->department?->name ?? '—',
                'payment_type' => $p->payment_type,
                'payment_provision_date' => $p->payment_provision_date?->toDateString(),
                'week_number' => $p->payment_provision_date ? (int) $p->payment_provision_date->isoWeek : null,
                'week_year' => $p->payment_provision_date ? (int) $p->payment_provision_date->isoWeekYear : null,
                'description' => $p->description,
                'currency_prefix' => $p->currency?->prefix ?? 'MXN',
                'currency_id' => $p->currency_id,
                'subtotal' => (string) $p->subtotal,
                'iva' => (string) $p->iva,
                'total' => (string) $p->total,
                'documents_count' => is_array($p->advance_documents) ? count(array_filter($p->advance_documents, fn ($d) => is_string($d) && $d !== '')) : 0,
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

        // Dashboard de presupuesto del proyecto (suma de Solicitudes con status=completed, normalizada a MXN)
        $originalBudget = $this->sumTotalMxn(
            InvestmentRequest::query()
                ->where('project_id', $project->id)
                ->where('is_addendum', false)
                ->where('is_deductive', false)
                ->whereState('status', Completed::class)
        );

        $additionalBudget = $this->sumTotalMxn(
            InvestmentRequest::query()
                ->where('project_id', $project->id)
                ->where('is_addendum', true)
                ->where('is_deductive', false)
                ->whereState('status', Completed::class)
        );

        $deductiveBudget = $this->sumTotalMxn(
            InvestmentRequest::query()
                ->where('project_id', $project->id)
                ->where('is_deductive', true)
                ->whereState('status', Completed::class)
        );

        // Aditivas y Deductivas capturadas pero aún pendientes de autorización
        // (status != completed). Se muestran como subsección "En proceso de
        // autorización" en el dashboard, cada una SOLO cuando es > 0.
        // NOTA: usamos where('status', '!=', ...) directo porque Spatie
        // ModelStates no expone un scope whereStateNot — solo whereState.
        $pendingAdditional = $this->sumTotalMxn(
            InvestmentRequest::query()
                ->where('project_id', $project->id)
                ->where('is_addendum', true)
                ->where('is_deductive', false)
                ->where('status', '!=', Completed::$name)
        );

        $pendingDeductive = $this->sumTotalMxn(
            InvestmentRequest::query()
                ->where('project_id', $project->id)
                ->where('is_deductive', true)
                ->where('status', '!=', Completed::$name)
        );

        $updatedBudget = $originalBudget + $additionalBudget - $deductiveBudget;

        $paidProjectTotal = $this->sumTotalMxn(
            InvestmentPaymentRequest::query()
                ->whereHas('investmentRequest', fn ($q) => $q->where('project_id', $project->id))
                ->whereIn('status', ['pending_approval', 'approved'])
        );

        $remainingBudget = $updatedBudget - $paidProjectTotal;

        $paymentPolicy = PaymentRequestPolicyService::fromCurrent()->buildFrontendPayload($user);

        return Inertia::render('investment-sheets/consolidated', [
            'paymentPolicy' => $paymentPolicy,
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'branch' => $project->branch?->name,
                'currency_id' => $project->currency_id,
                'currency_prefix' => $project->currency?->prefix ?? 'MXN',
                'start_date' => $project->start_date?->toDateString(),
                'opening_date' => $project->opening_date?->toDateString(),
                'authorized_budget' => $project->authorized_budget !== null ? number_format((float) $project->authorized_budget, 2, '.', '') : null,
            ],
            'projectDashboard' => [
                'current_week' => $currentWeek,
                'current_year' => $currentYear,
                'today_date' => $now->toDateString(),
                'original_budget' => number_format($originalBudget, 2, '.', ''),
                'additional_budget' => number_format($additionalBudget, 2, '.', ''),
                'deductive_budget' => number_format($deductiveBudget, 2, '.', ''),
                'pending_additional' => number_format($pendingAdditional, 2, '.', ''),
                'pending_deductive' => number_format($pendingDeductive, 2, '.', ''),
                'updated_budget' => number_format($updatedBudget, 2, '.', ''),
                'paid_total' => number_format($paidProjectTotal, 2, '.', ''),
                'remaining_budget' => number_format($remainingBudget, 2, '.', ''),
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
            'userDepartments' => $user->departments()->orderBy('name')->get(['departments.id', 'departments.name']),
            'isSuperAdmin' => $isSuperAdmin,
            'canSeeAllDepartments' => $canSeeAllDepartments,
            'departments' => $canSeeAllDepartments
                ? Department::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
            'currencies' => Currency::all(['id', 'name', 'prefix', 'exchange_rate']),
            'branches' => Branch::orderBy('name')->get(['id', 'name']),
            'availableConcepts' => InvestmentExpenseConcept::active()
                ->with('category:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'investment_expense_category_id']),
            'conceptDepartmentMap' => DB::table('department_investment_expense_category')
                ->select('investment_expense_category_id', 'department_id')
                ->get()
                ->groupBy('investment_expense_category_id')
                ->map(fn ($rows) => $rows->pluck('department_id')->all())
                ->toArray(),
            'canEditRequestConcept' => $canEditRequestConcept,
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
