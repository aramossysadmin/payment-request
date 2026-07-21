<?php

namespace App\Services;

use App\Models\InvestmentPaymentRequest;
use App\Models\InvestmentRequest;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Agrega los totales de inversión por departamento para un proyecto y usuario.
 * Devuelve floats normalizados a MXN (cada monto × exchange_rate de su moneda).
 *
 * Consumido por:
 * - InvestmentSheetConsolidatedController (payload Inertia para la tarjeta "Inversión por Departamento")
 * - InvestmentSheetInvestmentRequestsPdfController (cabecera del reporte PDF)
 *
 * El scope visibleTo($user) se aplica automáticamente para respetar permisos del usuario.
 */
class InvestmentDepartmentBreakdownService
{
    /**
     * @return Collection<int, array{
     *     id: int,
     *     name: string,
     *     total: float,
     *     committed: float,
     *     paid: float,
     *     pending: float,
     *     percent_consumed: float,
     *     count: int,
     * }>
     */
    public static function for(Project $project, User $user): Collection
    {
        $departmentTotals = InvestmentRequest::query()
            ->where('project_id', $project->id)
            ->visibleTo($user)
            ->join('departments', 'investment_requests.department_id', '=', 'departments.id')
            ->join('currencies', 'investment_requests.currency_id', '=', 'currencies.id')
            ->selectRaw("departments.id as department_id, departments.name as department_name, SUM(investment_requests.total * currencies.exchange_rate) as department_total, COUNT(DISTINCT COALESCE(CAST(investment_requests.investment_expense_concept_id AS CHAR), CONCAT('ir-', investment_requests.id))) as department_count")
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
            ->whereIn('investment_payment_requests.status', InvestmentPaymentRequest::PAID_STATUSES)
            ->groupBy('investment_requests.department_id')
            ->selectRaw('investment_requests.department_id, SUM(investment_payment_requests.total * currencies.exchange_rate) as paid_total')
            ->pluck('paid_total', 'department_id');

        $committedByDepartment = InvestmentPaymentRequest::query()
            ->join('investment_requests', 'investment_payment_requests.investment_request_id', '=', 'investment_requests.id')
            ->join('currencies', 'investment_payment_requests.currency_id', '=', 'currencies.id')
            ->whereIn('investment_payment_requests.investment_request_id', $visibleIrIds)
            ->whereIn('investment_payment_requests.status', InvestmentPaymentRequest::COMMITTED_STATUSES)
            ->groupBy('investment_requests.department_id')
            ->selectRaw('investment_requests.department_id, SUM(investment_payment_requests.total * currencies.exchange_rate) as committed_total')
            ->pluck('committed_total', 'department_id');

        return $departmentTotals->map(function ($d) use ($paidByDepartment, $committedByDepartment) {
            $total = (float) $d->department_total;
            $paid = (float) ($paidByDepartment[$d->department_id] ?? 0);
            $committed = (float) ($committedByDepartment[$d->department_id] ?? 0);
            $pending = max(0, $total - $committed - $paid);
            $percentConsumed = $total > 0 ? (($committed + $paid) / $total) * 100 : 0;

            return [
                'id' => (int) $d->department_id,
                'name' => $d->department_name,
                'total' => $total,
                'committed' => $committed,
                'paid' => $paid,
                'pending' => $pending,
                'percent_consumed' => round($percentConsumed, 1),
                'count' => (int) $d->department_count,
            ];
        });
    }
}
