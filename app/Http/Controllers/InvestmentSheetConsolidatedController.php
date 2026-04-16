<?php

namespace App\Http\Controllers;

use App\Http\Resources\InvestmentRequestResource;
use App\Models\Branch;
use App\Models\Currency;
use App\Models\InvestmentPaymentRequest;
use App\Models\InvestmentRequest;
use App\Models\Project;
use App\States\InvestmentRequest\Completed;
use Illuminate\Http\Request;
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
                    ->orWhere('folio_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->whereState('status', $request->string('status')->toString());
        }

        if ($departmentId) {
            $query->where('department_id', (int) $departmentId);
        }

        $investmentRequests = $query->latest()->paginate(10)->withQueryString();

        // Compute grouped budget totals (base + addendums with same concept + project)
        $investmentRequests->getCollection()->each(function (InvestmentRequest $ir) use ($project) {
            $ir->setAttribute('remaining_balance', $ir->remaining_balance);

            if ($ir->investment_expense_concept_id) {
                $groupIds = InvestmentRequest::query()
                    ->where('project_id', $project->id)
                    ->where('investment_expense_concept_id', $ir->investment_expense_concept_id)
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

        $project->load('branch');

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
            'departmentBreakdown' => $departmentBreakdown->map(fn ($d) => [
                'id' => $d->department_id,
                'name' => $d->department_name,
                'total' => number_format((float) $d->department_total, 2, '.', ''),
                'count' => (int) $d->department_count,
            ]),
            'investmentRequests' => InvestmentRequestResource::collection($investmentRequests),
            'filters' => [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'department_id' => $departmentId,
            ],
            'userDepartmentId' => $user->department_id,
            'currencies' => Currency::all(['id', 'name', 'prefix']),
            'branches' => Branch::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
