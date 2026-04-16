<?php

namespace App\Http\Controllers;

use App\Http\Resources\InvestmentRequestResource;
use App\Models\Branch;
use App\Models\Currency;
use App\Models\InvestmentRequest;
use App\Models\Project;
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

        // Compute remaining balance for each investment request
        $investmentRequests->getCollection()->each(function (InvestmentRequest $ir) {
            $ir->setAttribute('remaining_balance', $ir->remaining_balance);
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
