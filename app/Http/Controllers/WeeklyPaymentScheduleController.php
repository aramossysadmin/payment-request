<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWeeklyPaymentScheduleRequest;
use App\Models\InvestmentPaymentRequest;
use App\Models\WeeklyPaymentSchedule;
use App\Services\WeeklyPaymentScheduleApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class WeeklyPaymentScheduleController extends Controller
{
    public function __construct(private WeeklyPaymentScheduleApprovalService $approvalService) {}

    public function index(): Response
    {
        $currentWeek = (int) Carbon::now()->weekOfYear;
        $currentYear = (int) Carbon::now()->year;

        $payments = InvestmentPaymentRequest::query()
            ->where('status', 'approved')
            ->whereNotNull('payment_provision_date')
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
                'total' => (string) $p->total,
                'currency_prefix' => $p->currency?->prefix ?? 'MXN',
                'description' => $p->description,
            ]);

        $schedules = WeeklyPaymentSchedule::query()
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
                'total_amount' => $s->items->where('included', true)->sum(fn ($item) => (float) ($item->investmentPaymentRequest?->total ?? 0)),
                'approval_status' => $s->approvals->first()?->status ?? 'pending',
            ]);

        return Inertia::render('weekly-payment-schedule/index', [
            'payments' => $payments,
            'schedules' => $schedules,
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
