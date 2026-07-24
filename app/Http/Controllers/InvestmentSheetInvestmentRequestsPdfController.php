<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesDisplayCurrency;
use App\Models\Currency;
use App\Models\Department;
use App\Models\InvestmentPaymentRequest;
use App\Models\InvestmentRequest;
use App\Models\Project;
use App\Services\InvestmentDepartmentBreakdownService;
use App\States\InvestmentRequest\Completed;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvestmentSheetInvestmentRequestsPdfController extends Controller
{
    use ResolvesDisplayCurrency;

    /** @var array<int, string> */
    private const PAID_STATUSES = InvestmentPaymentRequest::PAID_STATUSES;

    /** @var array<int, string> */
    private const COMMITTED_STATUSES = InvestmentPaymentRequest::COMMITTED_STATUSES;

    public function __invoke(Request $request, Project $project): Response
    {
        $user = $request->user();

        abort_unless(
            $user->hasAnyRole(['super_admin', 'ceo', 'project_manager']),
            403,
            'No tienes permisos para descargar este reporte.'
        );

        $project->load('branch.society', 'currency');

        $displayCurrency = $this->resolveDisplayCurrency($request, $project);

        // Filtro: solo Departamento. Buscar y Estado se ignoran deliberadamente.
        $departmentIdRaw = $request->input('department_id');
        $departmentId = ($departmentIdRaw !== null && $departmentIdRaw !== '' && $departmentIdRaw !== 'all')
            ? (int) $departmentIdRaw
            : null;

        // Breakdown COMPLETO del proyecto (sin filtro) para la mini tarjeta del header.
        // Refleja el total del proyecto aunque estemos viendo un dpto específico.
        $fullBreakdown = InvestmentDepartmentBreakdownService::for($project, $user);
        $projectTotalCount = (int) $fullBreakdown->sum('count');
        $projectTotalAmount = $this->mxnToDisplay((float) $fullBreakdown->sum('total'), $displayCurrency);

        // Breakdown que va a las tarjetas "Inversión por Departamento".
        // Este SÍ se filtra por dpto para que la sección refleje el filtro aplicado.
        $departmentBreakdown = ($departmentId !== null
            ? $fullBreakdown->where('id', $departmentId)->values()
            : $fullBreakdown)
            ->map(function (array $d) use ($displayCurrency): array {
                $d['total'] = $this->mxnToDisplay((float) $d['total'], $displayCurrency);
                $d['committed'] = $this->mxnToDisplay((float) $d['committed'], $displayCurrency);
                $d['paid'] = $this->mxnToDisplay((float) $d['paid'], $displayCurrency);
                $d['pending'] = $this->mxnToDisplay((float) $d['pending'], $displayCurrency);

                return $d;
            });

        // Solicitudes: mismo scope de visibilidad que la vista, sin paginado.
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

        $investmentRequests = $requestsQuery->orderBy('department_id')->orderBy('folio_number')->get();

        // Nueva estructura: agrupar por DEPARTAMENTO, y dentro por CONCEPTO.
        // Los items de cada grupo se preservan para renderizar filas expandidas.
        $departmentsWithGroups = $investmentRequests
            ->groupBy(fn (InvestmentRequest $ir) => $ir->department_id)
            ->map(function ($deptItems, $deptId) use ($project, $displayCurrency) {
                $firstOfDept = $deptItems->first();

                $groups = $deptItems
                    ->groupBy(fn (InvestmentRequest $ir) => $ir->investment_expense_concept_id
                        ? "concept-{$ir->investment_expense_concept_id}"
                        : "no-concept-{$ir->id}"
                    )
                    ->map(function ($items) use ($project, $displayCurrency) {
                        // Ordenar iniciales primero, luego por folio ascendente.
                        $ordered = $items
                            ->sortBy([
                                fn (InvestmentRequest $a, InvestmentRequest $b) => (int) $a->is_addendum - (int) $b->is_addendum,
                                fn (InvestmentRequest $a, InvestmentRequest $b) => (int) $a->folio_number - (int) $b->folio_number,
                            ])
                            ->values();
                        $first = $ordered->first();

                        $itemsPayload = $ordered->map(function (InvestmentRequest $ir) use ($displayCurrency): array {
                            $mxn = (float) $ir->total * (float) ($ir->currency?->exchange_rate ?? 1);

                            return [
                                'folio_number' => str_pad((string) $ir->folio_number, 5, '0', STR_PAD_LEFT),
                                'is_addendum' => (bool) $ir->is_addendum,
                                'type_label' => $ir->is_addendum ? 'Aditiva' : 'Inicial',
                                'status_label' => $ir->status->label(),
                                'status_color' => $ir->status->color(),
                                'description' => $ir->description,
                                'total_display' => $this->mxnToDisplay($mxn, $displayCurrency),
                            ];
                        })->values();

                        return [
                            'concept_name' => $first->investmentExpenseConcept?->name ?? '—',
                            'concept_category' => $first->investmentExpenseConcept?->category?->name,
                            'items_count' => $items->count(),
                            'initials_count' => (int) $items->where('is_addendum', false)->count(),
                            'additives_count' => (int) $items->where('is_addendum', true)->count(),
                            'items' => $itemsPayload,
                            ...$this->groupBudgetAndRemaining($project, $first, $displayCurrency),
                        ];
                    })
                    ->sortBy(fn (array $g) => $g['concept_name'])
                    ->values();

                return [
                    'id' => (int) $deptId,
                    'name' => $firstOfDept->department?->name ?? '—',
                    'groups' => $groups,
                ];
            })
            ->sortBy(fn (array $d) => $d['name'])
            ->values();

        // Nombre de archivo.
        $deptSlug = $departmentId !== null
            ? (Department::find($departmentId)?->name ?? "dpto-{$departmentId}")
            : 'TODOS';

        $filename = sprintf(
            'Solicitudes de Inversion - %s - %s - %s.pdf',
            Str::of($project->name)->limit(60, '')->toString(),
            Str::of($deptSlug)->limit(40, '')->toString(),
            Carbon::now()->format('Y-m-d'),
        );

        $pdf = Pdf::loadView('pdf.investment-requests-consolidated', [
            'project' => $project,
            'projectTotalCount' => $projectTotalCount,
            'projectTotalAmount' => $projectTotalAmount,
            'departmentBreakdown' => $departmentBreakdown,
            'departmentsWithGroups' => $departmentsWithGroups,
            'isAllDepartments' => $departmentId === null,
            'departmentFilterName' => $departmentId !== null
                ? (Department::find($departmentId)?->name ?? '—')
                : 'Todos',
            'generatedAt' => Carbon::now(),
            'generatedBy' => $user->name,
            'displayPrefix' => $displayCurrency->prefix,
            'displayCurrencyName' => $displayCurrency->name,
            'exchangeRates' => $this->exchangeRateNote($displayCurrency),
        ])
            ->setOption('isPhpEnabled', true) // requerido para paginación via <script type="text/php">
            ->setPaper('legal', 'landscape');

        return $pdf->download($filename);
    }

    /**
     * Calcula presupuesto y saldo del grupo (concepto + dpto) al que pertenece un IR,
     * en la moneda de despliegue solicitada.
     *
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

        // IR sin concepto: el grupo es el propio IR.
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
}
