<?php

namespace App\Http\Controllers;

use App\Models\InvestmentPaymentRequest;
use App\Models\InvestmentRequest;
use App\Models\Project;
use App\States\InvestmentRequest\Completed;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class InvestmentDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $projects = Project::query()
            ->whereHas('investmentRequests')
            ->orderBy('name')
            ->get(['id', 'name']);

        $projectId = $request->filled('project_id')
            ? (int) $request->input('project_id')
            : $projects->first()?->id;

        $departmentId = $request->filled('department_id') && $request->input('department_id') !== 'all'
            ? (int) $request->input('department_id')
            : null;

        $data = [
            'kpis' => ['budget' => '0', 'executed' => '0', 'remaining' => '0', 'percent' => 0],
            'byDepartment' => [],
            'byConcept' => [],
            'conceptTable' => [],
            'departments' => [],
        ];

        if ($projectId) {
            $data = $this->computeDashboardData($projectId, $departmentId, $user);
        }

        return Inertia::render('investment-dashboard/index', [
            'projects' => $projects->map(fn (Project $p) => ['id' => $p->id, 'name' => $p->name]),
            'filters' => [
                'project_id' => $projectId ? (string) $projectId : '',
                'department_id' => $departmentId ? (string) $departmentId : 'all',
            ],
            ...$data,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function computeDashboardData(int $projectId, ?int $departmentId, mixed $user): array
    {
        $baseQuery = InvestmentRequest::query()
            ->where('project_id', $projectId)
            ->whereState('status', Completed::class)
            ->visibleTo($user);

        if ($departmentId) {
            $baseQuery->where('department_id', $departmentId);
        }

        $completedIds = (clone $baseQuery)->pluck('id');

        $totalBudget = (float) InvestmentRequest::whereIn('id', $completedIds)->sum('total');

        $totalExecuted = (float) InvestmentPaymentRequest::query()
            ->whereIn('investment_request_id', $completedIds)
            ->whereIn('status', ['pending_approval', 'approved'])
            ->sum('total');

        $remaining = $totalBudget - $totalExecuted;
        $percent = $totalBudget > 0 ? round(($totalExecuted / $totalBudget) * 100, 1) : 0;

        // Departments for filter
        $departments = InvestmentRequest::query()
            ->where('project_id', $projectId)
            ->whereState('status', Completed::class)
            ->visibleTo($user)
            ->join('departments', 'investment_requests.department_id', '=', 'departments.id')
            ->select('departments.id', 'departments.name')
            ->distinct()
            ->orderBy('departments.name')
            ->get()
            ->map(fn ($d) => ['id' => $d->id, 'name' => $d->name]);

        // By department
        $byDepartment = $this->getExecutionByDepartment($completedIds);

        // By concept
        $byConcept = $this->getExecutionByConcept($completedIds);

        // Concept table
        $conceptTable = $this->getConceptTable($completedIds);

        return [
            'kpis' => [
                'budget' => number_format($totalBudget, 2, '.', ''),
                'executed' => number_format($totalExecuted, 2, '.', ''),
                'remaining' => number_format($remaining, 2, '.', ''),
                'percent' => $percent,
            ],
            'byDepartment' => $byDepartment,
            'byConcept' => $byConcept,
            'conceptTable' => $conceptTable,
            'departments' => $departments,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getExecutionByDepartment(Collection $completedIds): array
    {
        $query = InvestmentRequest::query()
            ->whereIn('investment_requests.id', $completedIds)
            ->join('departments', 'investment_requests.department_id', '=', 'departments.id')
            ->selectRaw('departments.id as dept_id, departments.name as dept_name, SUM(investment_requests.total) as budget')
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('budget');

        return $query->get()->map(function ($row) use ($completedIds) {
            $deptConceptIds = InvestmentRequest::whereIn('id', $completedIds)
                ->where('department_id', $row->dept_id)
                ->pluck('id');

            $paid = (float) InvestmentPaymentRequest::query()
                ->whereIn('investment_request_id', $deptConceptIds)
                ->whereIn('status', ['pending_approval', 'approved'])
                ->sum('total');

            $budget = (float) $row->budget;

            return [
                'name' => $row->dept_name,
                'budget' => number_format($budget, 2, '.', ''),
                'executed' => number_format($paid, 2, '.', ''),
                'percent' => $budget > 0 ? round(($paid / $budget) * 100, 1) : 0,
            ];
        })->values()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getExecutionByConcept(Collection $completedIds): array
    {
        return InvestmentRequest::query()
            ->whereIn('investment_requests.id', $completedIds)
            ->join('investment_expense_concepts', 'investment_requests.investment_expense_concept_id', '=', 'investment_expense_concepts.id')
            ->selectRaw('
                investment_expense_concepts.name as concept_name,
                SUM(CASE WHEN investment_requests.is_addendum = 0 THEN investment_requests.total ELSE 0 END) as initial_budget,
                SUM(CASE WHEN investment_requests.is_addendum = 1 THEN investment_requests.total ELSE 0 END) as addendum_total,
                SUM(investment_requests.total) as total_budget
            ')
            ->groupBy('investment_expense_concepts.id', 'investment_expense_concepts.name')
            ->orderByDesc('total_budget')
            ->get()
            ->map(function ($row) {
                $initial = (float) $row->initial_budget;
                $addendum = (float) $row->addendum_total;
                $total = (float) $row->total_budget;
                $growthPercent = $initial > 0 ? round(($addendum / $initial) * 100, 1) : 0;

                return [
                    'name' => $row->concept_name,
                    'initial' => number_format($initial, 2, '.', ''),
                    'addendum' => number_format($addendum, 2, '.', ''),
                    'total' => number_format($total, 2, '.', ''),
                    'growthPercent' => $growthPercent,
                ];
            })->values()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getConceptTable(Collection $completedIds): array
    {
        return InvestmentRequest::query()
            ->whereIn('investment_requests.id', $completedIds)
            ->join('investment_expense_concepts', 'investment_requests.investment_expense_concept_id', '=', 'investment_expense_concepts.id')
            ->join('departments', 'investment_requests.department_id', '=', 'departments.id')
            ->selectRaw('
                investment_expense_concepts.id as concept_id,
                investment_expense_concepts.name as concept_name,
                departments.name as dept_name,
                SUM(CASE WHEN investment_requests.is_addendum = 0 THEN investment_requests.total ELSE 0 END) as base_budget,
                SUM(CASE WHEN investment_requests.is_addendum = 1 THEN investment_requests.total ELSE 0 END) as addendum_total,
                SUM(investment_requests.total) as total_budget,
                COUNT(CASE WHEN investment_requests.is_addendum = 1 THEN 1 END) as addendum_count
            ')
            ->groupBy('investment_expense_concepts.id', 'investment_expense_concepts.name', 'departments.name')
            ->orderByDesc('total_budget')
            ->get()
            ->map(function ($row) use ($completedIds) {
                $groupIds = InvestmentRequest::whereIn('id', $completedIds)
                    ->where('investment_expense_concept_id', $row->concept_id)
                    ->pluck('id');

                $paid = (float) InvestmentPaymentRequest::query()
                    ->whereIn('investment_request_id', $groupIds)
                    ->whereIn('status', ['pending_approval', 'approved'])
                    ->sum('total');

                $budget = (float) $row->total_budget;
                $remaining = $budget - $paid;

                return [
                    'concept' => $row->concept_name,
                    'department' => $row->dept_name,
                    'baseBudget' => number_format((float) $row->base_budget, 2, '.', ''),
                    'addendumTotal' => number_format((float) $row->addendum_total, 2, '.', ''),
                    'addendumCount' => (int) $row->addendum_count,
                    'totalBudget' => number_format($budget, 2, '.', ''),
                    'paid' => number_format($paid, 2, '.', ''),
                    'remaining' => number_format($remaining, 2, '.', ''),
                    'percent' => $budget > 0 ? round(($paid / $budget) * 100, 1) : 0,
                ];
            })->values()->all();
    }
}
