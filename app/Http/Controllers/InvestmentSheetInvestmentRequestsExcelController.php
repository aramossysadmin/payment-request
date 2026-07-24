<?php

namespace App\Http\Controllers;

use App\Exports\InvestmentRequestsConsolidatedExport;
use App\Http\Controllers\Concerns\ResolvesDisplayCurrency;
use App\Models\Currency;
use App\Models\Department;
use App\Models\InvestmentPaymentRequest;
use App\Models\InvestmentRequest;
use App\Models\Project;
use App\States\InvestmentRequest\Completed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InvestmentSheetInvestmentRequestsExcelController extends Controller
{
    use ResolvesDisplayCurrency;

    /** @var array<int, string> */
    private const PAID_STATUSES = InvestmentPaymentRequest::PAID_STATUSES;

    /** @var array<int, string> */
    private const COMMITTED_STATUSES = InvestmentPaymentRequest::COMMITTED_STATUSES;

    public function __invoke(Request $request, Project $project): BinaryFileResponse
    {
        $user = $request->user();

        abort_unless(
            $user->hasAnyRole(['super_admin', 'ceo', 'project_manager']),
            403,
            'No tienes permisos para descargar este reporte.'
        );

        $project->load('branch.society', 'currency');
        $displayCurrency = $this->resolveDisplayCurrency($request, $project);

        $departmentIdRaw = $request->input('department_id');
        $departmentId = ($departmentIdRaw !== null && $departmentIdRaw !== '' && $departmentIdRaw !== 'all')
            ? (int) $departmentIdRaw
            : null;

        $requestsQuery = InvestmentRequest::query()
            ->with([
                'department',
                'currency',
                'investmentExpenseConcept.category',
            ])
            ->where('project_id', $project->id)
            ->visibleTo($user);

        if ($departmentId !== null) {
            $requestsQuery->where('department_id', $departmentId);
        }

        $investmentRequests = $requestsQuery
            ->orderBy('department_id')
            ->orderBy('folio_number')
            ->get();

        // Cache de grupo (concepto+depto → [budget, remaining]) para no recomputar por cada IR del mismo grupo.
        $groupCache = [];

        $rows = $investmentRequests->map(function (InvestmentRequest $ir) use ($project, $displayCurrency, &$groupCache): array {
            $nativeRate = (float) ($ir->currency?->exchange_rate ?? 1);
            $totalMxn = (float) $ir->total * $nativeRate;

            $groupKey = $ir->investment_expense_concept_id
                ? "concept-{$ir->investment_expense_concept_id}-dpto-{$ir->department_id}"
                : "ir-{$ir->id}";

            if (! array_key_exists($groupKey, $groupCache)) {
                $groupCache[$groupKey] = $this->groupBudgetAndRemaining($project, $ir, $displayCurrency);
            }

            return [
                'department' => $ir->department?->name,
                'category' => $ir->investmentExpenseConcept?->category?->name,
                'concept' => $ir->investmentExpenseConcept?->name,
                'folio' => '#'.str_pad((string) $ir->folio_number, 5, '0', STR_PAD_LEFT),
                'type' => $ir->is_addendum ? 'Aditiva' : 'Inicial',
                'status' => $ir->status->label(),
                'description' => $ir->description,
                'total_display' => $this->mxnToDisplay($totalMxn, $displayCurrency),
                'group_budget' => $groupCache[$groupKey]['group_budget'],
                'group_remaining' => $groupCache[$groupKey]['group_remaining'],
            ];
        });

        $deptSlug = $departmentId !== null
            ? (Department::find($departmentId)?->name ?? "dpto-{$departmentId}")
            : 'TODOS';

        $filename = sprintf(
            'Solicitudes de Inversion - %s - %s - %s.xlsx',
            Str::of($project->name)->limit(60, '')->toString(),
            Str::of($deptSlug)->limit(40, '')->toString(),
            Carbon::now()->format('Y-m-d'),
        );

        return Excel::download(
            new InvestmentRequestsConsolidatedExport($rows, $displayCurrency->prefix),
            $filename,
        );
    }

    /**
     * @return array{group_budget: float, group_remaining: float}
     */
    private function groupBudgetAndRemaining(Project $project, InvestmentRequest $ir, Currency $displayCurrency): array
    {
        $rate = (float) ($ir->currency?->exchange_rate ?? 1);

        if ($ir->investment_expense_concept_id) {
            $groupIds = InvestmentRequest::query()
                ->where('project_id', $project->id)
                ->where('investment_expense_concept_id', $ir->investment_expense_concept_id)
                ->where('department_id', $ir->department_id)
                ->whereState('status', Completed::class)
                ->pluck('id');

            $groupBudget = $this->sumTotalMxn(InvestmentRequest::query()->whereIn('id', $groupIds));
            $groupPaid = $this->sumTotalMxn(
                InvestmentPaymentRequest::query()->whereIn('investment_request_id', $groupIds)->whereIn('status', self::PAID_STATUSES)
            );
            $groupCommitted = $this->sumTotalMxn(
                InvestmentPaymentRequest::query()->whereIn('investment_request_id', $groupIds)->whereIn('status', self::COMMITTED_STATUSES)
            );

            return [
                'group_budget' => $this->mxnToDisplay($groupBudget, $displayCurrency),
                'group_remaining' => $this->mxnToDisplay($groupBudget - $groupCommitted - $groupPaid, $displayCurrency),
            ];
        }

        $budget = (float) $ir->total * $rate;
        $paid = $this->sumTotalMxn(
            InvestmentPaymentRequest::query()->where('investment_request_id', $ir->id)->whereIn('status', self::PAID_STATUSES)
        );
        $committed = $this->sumTotalMxn(
            InvestmentPaymentRequest::query()->where('investment_request_id', $ir->id)->whereIn('status', self::COMMITTED_STATUSES)
        );

        return [
            'group_budget' => $this->mxnToDisplay($budget, $displayCurrency),
            'group_remaining' => $this->mxnToDisplay($budget - $committed - $paid, $displayCurrency),
        ];
    }

    private function sumTotalMxn(Builder $query): float
    {
        $table = $query->getModel()->getTable();

        return (float) $query->sum(DB::raw(
            "{$table}.total * (select exchange_rate from currencies where currencies.id = {$table}.currency_id)"
        ));
    }
}
