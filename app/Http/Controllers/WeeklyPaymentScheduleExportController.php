<?php

namespace App\Http\Controllers;

use App\Exports\WeeklyPaymentScheduleExport;
use App\Models\InvestmentPaymentRequest;
use App\Models\InvestmentRequest;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class WeeklyPaymentScheduleExportController extends Controller
{
    /**
     * Estados que viven en el módulo de programación semanal (programables + programados
     * + finalizados con comprobante). Mismo universo que muestra la vista.
     *
     * @var array<int, string>
     */
    private const EXPORTABLE_STATUSES = ['approved', 'completed', 'scheduled_for_bank', 'receipt_attached'];

    public function __invoke(Request $request): BinaryFileResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'integer'],
            'week' => ['nullable', 'integer', 'min:1', 'max:53'],
            'year' => ['nullable', 'integer', 'min:2020', 'max:2100', 'required_with:week'],
        ]);

        $user = $request->user();
        $projectId = (int) $validated['project_id'];

        $visible = InvestmentRequest::query()
            ->visibleTo($user)
            ->where('project_id', $projectId)
            ->exists();

        abort_unless($visible, Response::HTTP_FORBIDDEN, 'No tienes acceso a este proyecto.');

        $project = Project::findOrFail($projectId);
        $week = isset($validated['week']) ? (int) $validated['week'] : null;
        $year = isset($validated['year']) ? (int) $validated['year'] : null;

        $payments = InvestmentPaymentRequest::query()
            ->whereIn('status', self::EXPORTABLE_STATUSES)
            ->whereNotNull('payment_provision_date')
            ->whereHas('investmentRequest', fn ($q) => $q->where('project_id', $projectId))
            ->when($week !== null, fn ($q) => $q->where('payment_week_number', $week))
            ->with(['investmentRequest.investmentExpenseConcept', 'currency', 'department'])
            ->orderBy('payment_week_number')
            ->orderBy('folio_number')
            ->get();

        if ($week !== null && $year !== null) {
            $payments = $payments
                ->filter(fn (InvestmentPaymentRequest $p) => (int) $p->payment_provision_date->isoWeekYear === $year)
                ->values();
        }

        $suffix = $week !== null ? "S{$week}-{$year}" : 'todas-las-semanas';
        $filename = 'programacion-pagos-'.Str::slug($project->name).'-'.$suffix.'.xlsx';

        return Excel::download(new WeeklyPaymentScheduleExport($payments), $filename);
    }
}
