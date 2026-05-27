<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWeeklyPaymentScheduleRequest;
use App\Models\InvestmentPaymentRequest;
use App\Models\InvestmentRequest;
use App\Models\Project;
use App\Models\WeeklyPaymentSchedule;
use App\Services\WeeklyPaymentScheduleApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class WeeklyPaymentScheduleController extends Controller
{
    public function __construct(private WeeklyPaymentScheduleApprovalService $approvalService) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $currentWeek = (int) Carbon::now()->weekOfYear;
        $currentYear = (int) Carbon::now()->year;

        // Lista de proyectos visibles para el usuario (via InvestmentRequest::visibleTo)
        $projectIds = InvestmentRequest::query()
            ->visibleTo($user)
            ->whereNotNull('project_id')
            ->pluck('project_id')
            ->unique();

        $projects = Project::whereIn('id', $projectIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Project $p) => ['id' => $p->id, 'name' => $p->name])
            ->values();

        $selectedProjectId = $request->integer('project_id') ?: null;

        // Si no hay proyecto seleccionado, retornamos arrays vacíos.
        // El frontend muestra empty state hasta que el usuario elija una Hoja de Inversión.
        if (! $selectedProjectId) {
            return Inertia::render('weekly-payment-schedule/index', [
                'payments' => [],
                'schedules' => [],
                'projects' => $projects,
                'selectedProjectId' => null,
                'currentWeek' => $currentWeek,
                'currentYear' => $currentYear,
            ]);
        }

        // Validar que el proyecto seleccionado sea visible para el usuario.
        // Si manda un project_id al que no tiene acceso, tratamos como sin selección.
        if (! $projectIds->contains($selectedProjectId)) {
            return Inertia::render('weekly-payment-schedule/index', [
                'payments' => [],
                'schedules' => [],
                'projects' => $projects,
                'selectedProjectId' => null,
                'currentWeek' => $currentWeek,
                'currentYear' => $currentYear,
            ]);
        }

        // Solo se programan pagos completamente listos:
        // - 'approved': pagos del flujo anterior (legacy, 103 históricos)
        // - 'completed': pagos del flujo nuevo CON documentos cargados
        // Filtrados por el proyecto seleccionado.
        $payments = InvestmentPaymentRequest::query()
            ->whereIn('status', ['approved', 'completed'])
            ->whereNotNull('payment_provision_date')
            ->whereHas('investmentRequest', fn ($q) => $q->where('project_id', $selectedProjectId))
            ->with([
                'investmentRequest.project',
                'investmentRequest.investmentExpenseConcept',
                'currency',
            ])
            ->get()
            ->map(fn (InvestmentPaymentRequest $p) => [
                'id' => $p->id,
                'folio_number' => $p->folio_number,
                'provider' => $p->provider,
                'concept_name' => $p->investmentRequest?->investmentExpenseConcept?->name ?? '-',
                'project_name' => $p->investmentRequest?->project?->name ?? '-',
                'payment_provision_date' => $p->payment_provision_date?->format('Y-m-d'),
                'payment_week_number' => $p->payment_week_number,
                'total' => (string) ($p->approved_amount ?? $p->total),
                'currency_prefix' => $p->currency?->prefix ?? 'MXN',
                'description' => $p->description,
            ]);

        // Schedules que contengan al menos 1 pago del proyecto seleccionado.
        $schedules = WeeklyPaymentSchedule::query()
            ->whereHas(
                'items.investmentPaymentRequest.investmentRequest',
                fn ($q) => $q->where('project_id', $selectedProjectId),
            )
            ->with(['creator', 'items.investmentPaymentRequest', 'approvals.user'])
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (WeeklyPaymentSchedule $s) => [
                'id' => $s->id,
                'uuid' => $s->uuid,
                'week_number' => $s->week_number,
                'year' => $s->year,
                'status' => $s->status,
                'creator_name' => $s->creator?->name,
                'created_at' => $s->created_at?->toISOString(),
                'items_count' => $s->items->count(),
                'included_count' => $s->items->where('included', true)->count(),
                'total_amount' => $s->items->where('included', true)->sum(fn ($item) => (float) ($item->investmentPaymentRequest?->approved_amount ?? $item->investmentPaymentRequest?->total ?? 0)),
                'approval_status' => $s->approvals->first()?->status ?? 'pending',
            ]);

        return Inertia::render('weekly-payment-schedule/index', [
            'payments' => $payments,
            'schedules' => $schedules,
            'projects' => $projects,
            'selectedProjectId' => $selectedProjectId,
            'currentWeek' => $currentWeek,
            'currentYear' => $currentYear,
        ]);
    }

    public function store(StoreWeeklyPaymentScheduleRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $schedule = WeeklyPaymentSchedule::create([
            'week_number' => $validated['week_number'],
            'year' => $validated['year'],
            'created_by' => $user->id,
            'status' => 'pending_approval',
        ]);

        foreach ($validated['items'] as $item) {
            $schedule->items()->create([
                'investment_payment_request_id' => $item['id'],
                'included' => $item['included'],
                'exclusion_reason' => $item['included'] ? null : ($item['exclusion_reason'] ?? null),
            ]);
        }

        // Postpone excluded payments to next week
        $excludedIds = collect($validated['items'])
            ->filter(fn ($item) => ! $item['included'])
            ->pluck('id');

        if ($excludedIds->isNotEmpty()) {
            $nextWeek = $validated['week_number'] >= 52
                ? 1
                : $validated['week_number'] + 1;

            InvestmentPaymentRequest::whereIn('id', $excludedIds)
                ->update(['payment_week_number' => $nextWeek]);
        }

        $this->approvalService->createApproval($schedule);

        return back()->with('success', 'Programación de pagos semanal creada exitosamente.');
    }
}
