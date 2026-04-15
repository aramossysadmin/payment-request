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

        $projects = Project::query()
            ->with('branch')
            ->withCount(['investmentRequests' => function ($q) use ($user) {
                $q->visibleTo($user);
            }])
            ->withSum(['investmentRequests' => function ($q) use ($user) {
                $q->visibleTo($user);
            }], 'total')
            ->whereHas('investmentRequests', function ($q) use ($user) {
                $q->visibleTo($user);
            })
            ->orderBy('name')
            ->get();

        return Inertia::render('investment-sheets/consolidated-index', [
            'projects' => $projects->map(fn (Project $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'branch' => $p->branch?->name,
                'sheets_count' => (int) $p->investment_requests_count,
                'total' => number_format((float) ($p->investment_requests_sum_total ?? 0), 2, '.', ''),
            ]),
        ]);
    }
}
