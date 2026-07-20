<?php

namespace App\Http\Controllers;

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
        // En sesión futura se aplicará un punto más fino que respete permisos
        // pero permita descubrir proyectos nuevos.
        $projects = Project::query()
            ->with('branch.society')
            ->where('is_active', true)
            ->withCount(['investmentRequests' => function ($q) use ($user) {
                $q->visibleTo($user);
            }])
            ->withSum(['investmentRequests' => function ($q) use ($user) {
                $q->visibleTo($user);
            }], 'total')
            ->orderBy('name')
            ->get();

        return Inertia::render('investment-sheets/consolidated-index', [
            'projects' => $projects->map(fn (Project $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'branch' => $p->branch?->name,
                'society_name' => $p->branch?->society?->name,
                'society_rfc' => $p->branch?->society?->rfc,
                'cost_center' => $p->branch?->cost_center,
                'sheets_count' => (int) $p->investment_requests_count,
                'total' => number_format((float) ($p->investment_requests_sum_total ?? 0), 2, '.', ''),
            ]),
        ]);
    }
}
