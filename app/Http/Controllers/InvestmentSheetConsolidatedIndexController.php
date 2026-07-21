<?php

namespace App\Http\Controllers;

use App\Models\InvestmentRequest;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvestmentSheetConsolidatedIndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        // NOTA: el whereHas(investmentRequests, visibleTo) anterior ocultaba
        // proyectos nuevos sin Solicitudes capturadas todavía, dejándolos
        // invisibles para todos los usuarios hasta la primera captura.
        // Solución temporal Opción A: mostrar TODOS los proyectos activos.
        $projects = Project::query()
            ->with('branch.society')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Agregado por proyecto: N conceptos únicos + SUM total.
        // "concepto" = rubro presupuestal único identificado por (investment_expense_concept_id, department_id).
        // Las aditivas NO cuentan como conceptos adicionales — son extensiones al mismo concepto.
        $aggregates = InvestmentRequest::query()
            ->whereIn('project_id', $projects->pluck('id'))
            ->visibleTo($user)
            ->groupBy('investment_requests.project_id')
            ->select('investment_requests.project_id')
            ->selectRaw("COUNT(DISTINCT CONCAT(COALESCE(CAST(investment_requests.investment_expense_concept_id AS CHAR), CONCAT('ir-', investment_requests.id)), '-', investment_requests.department_id)) as concepts_count")
            ->selectRaw('SUM(investment_requests.total) as sum_total')
            ->get()
            ->keyBy('project_id');

        return Inertia::render('investment-sheets/consolidated-index', [
            'projects' => $projects->map(fn (Project $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'branch' => $p->branch?->name,
                'society_name' => $p->branch?->society?->name,
                'society_rfc' => $p->branch?->society?->rfc,
                'cost_center' => $p->branch?->cost_center,
                'sheets_count' => (int) ($aggregates[$p->id]?->concepts_count ?? 0),
                'total' => number_format((float) ($aggregates[$p->id]?->sum_total ?? 0), 2, '.', ''),
            ]),
        ]);
    }
}
