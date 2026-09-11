<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesDisplayCurrency;
use App\Models\Department;
use App\Models\InvestmentPaymentRequest;
use App\Models\Project;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class InvestmentPaymentHistoryPdfController extends Controller
{
    use ResolvesDisplayCurrency;

    public function __invoke(Request $request, Project $project): Response
    {
        $user = $request->user();

        abort_if(
            ! $user->canAccessProject($project),
            403,
            'No tienes acceso a este proyecto.',
        );

        $canSeeAllDepartments = $user->hasAnyRole(['super_admin', 'ceo', 'project_manager']);

        $project->loadMissing('currency');
        $displayCurrency = $this->resolveDisplayCurrency($request, $project);

        // Filtro de departamento: si no es privilegiado, restringir a los dptos del user.
        // Si especifica un departamento, debe ser uno al que el user pertenece.
        $departmentParam = $request->input('department_id');
        $departmentId = null;
        $departmentIds = null; // array de IDs cuando user multi-dpto y no filtra específicamente
        $departmentAll = false;
        $userDepartmentIds = $user->departments()->pluck('departments.id')->all();

        if (! $canSeeAllDepartments) {
            if ($departmentParam !== null && $departmentParam !== '' && $departmentParam !== 'all') {
                $requestedId = (int) $departmentParam;
                abort_unless(in_array($requestedId, $userDepartmentIds, true), 403, 'No tienes acceso a ese departamento.');
                $departmentId = $requestedId;
            } else {
                // Sin filtro: ve TODOS sus dptos
                $departmentIds = $userDepartmentIds;
            }
        } elseif ($departmentParam === 'all' || $departmentParam === null || $departmentParam === '') {
            $departmentAll = true;
        } else {
            $departmentId = (int) $departmentParam;
        }

        // Filtro de semana: privilegiados pueden omitirlo (todo el período);
        // usuarios normales DEBEN enviar week_number + week_year (validación 422).
        $weekNumber = $request->integer('week_number') ?: null;
        $weekYear = $request->integer('week_year') ?: null;

        if (! $canSeeAllDepartments) {
            $request->validate([
                'week_number' => ['required', 'integer', 'between:1,53'],
                'week_year' => ['required', 'integer', 'min:2000'],
            ]);
        }

        $search = trim((string) $request->input('search', ''));
        $statusFilter = $request->input('status', 'all');
        $quickFilter = $request->input('quick_filter', 'all');

        $statusGroups = [
            'in_process' => ['submitted', 'ceo_approved', 'projectmanager_review', 'projectmanager_approved', 'final_pending', 'documents_pending', 'pending_approval'],
            'completed' => ['final_approved', 'completed', 'approved'],
            'rejected' => ['ceo_rejected', 'projectmanager_rejected', 'final_rejected', 'rejected'],
        ];

        $query = InvestmentPaymentRequest::query()
            ->where('status', '!=', 'draft')
            ->whereHas('investmentRequest', fn ($q) => $q->where('project_id', $project->id))
            ->when($departmentId !== null, fn ($q) => $q->where('department_id', $departmentId))
            ->when($departmentIds !== null, fn ($q) => $q->whereIn('department_id', $departmentIds))
            ->when($statusFilter !== 'all' && $statusFilter !== null, fn ($q) => $q->where('status', $statusFilter))
            ->when(isset($statusGroups[$quickFilter]), fn ($q) => $q->whereIn('status', $statusGroups[$quickFilter]))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('provider', 'like', "%{$search}%")
                        ->orWhere('folio_number', 'like', "%{$search}%")
                        ->orWhere('rfc', 'like', "%{$search}%")
                        ->orWhere('invoice_folio', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($qq) => $qq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('investmentRequest.investmentExpenseConcept', fn ($qq) => $qq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('investmentRequest.investmentExpenseConcept.category', fn ($qq) => $qq->where('name', 'like', "%{$search}%"));
                });
            })
            ->with(['user', 'currency', 'investmentRequest.investmentExpenseConcept.category', 'department', 'branch'])
            ->latest('created_at');

        $payments = $query->get();

        if ($weekNumber !== null && $weekYear !== null) {
            $payments = $payments->filter(function (InvestmentPaymentRequest $p) use ($weekNumber, $weekYear) {
                if (! $p->payment_provision_date) {
                    return false;
                }

                return (int) $p->payment_provision_date->isoWeek === $weekNumber
                    && (int) $p->payment_provision_date->isoWeekYear === $weekYear;
            })->values();
        }

        // Enriquecer cada pago con montos convertidos a la moneda de display.
        $paymentsPayload = $payments->map(function (InvestmentPaymentRequest $p) use ($displayCurrency): array {
            $nativeRate = (float) ($p->currency?->exchange_rate ?? 1);
            $totalMxn = (float) $p->total * $nativeRate;
            $approvedMxn = $p->approved_amount !== null
                ? (float) $p->approved_amount * $nativeRate
                : null;

            return [
                'model' => $p,
                'total_display' => $this->mxnToDisplay($totalMxn, $displayCurrency),
                'approved_display' => $approvedMxn !== null
                    ? $this->mxnToDisplay($approvedMxn, $displayCurrency)
                    : null,
            ];
        })->values();

        // Total consolidado en la moneda de display.
        $totalDisplayApproved = $paymentsPayload->reduce(
            fn (float $sum, array $row) => $sum + (float) ($row['approved_display'] ?? $row['total_display']),
            0.0
        );
        $totalDisplayRequested = $paymentsPayload->reduce(
            fn (float $sum, array $row) => $sum + (float) $row['total_display'],
            0.0
        );

        $statusLabels = [
            'submitted' => 'Enviado',
            'ceo_approved' => 'CEO Aprobó (1ra)',
            'projectmanager_review' => 'PM Revisando',
            'projectmanager_approved' => 'PM Aprobó',
            'final_pending' => 'Final Pendiente',
            'documents_pending' => 'Docs Pendientes',
            'final_approved' => 'Final Aprobado',
            'completed' => 'Completado',
            'scheduled_for_bank' => 'Programado en banco',
            'receipt_attached' => 'Comprobante adjunto',
            'approved' => 'Aprobado (Legacy)',
            'ceo_rejected' => 'CEO Rechazó',
            'projectmanager_rejected' => 'PM Rechazó',
            'final_rejected' => 'Final Rechazó',
            'rejected' => 'Rechazado',
            'pending_approval' => 'Pendiente',
        ];

        $filtersApplied = [
            'department' => $departmentAll
                ? 'Todos los departamentos'
                : ($departmentId !== null
                    ? (Department::find($departmentId)?->name ?? '—')
                    : ($departmentIds !== null
                        ? 'Mis departamentos ('.Department::whereIn('id', $departmentIds)->pluck('name')->implode(', ').')'
                        : '—')),
            'week' => ($weekNumber !== null && $weekYear !== null) ? "Semana {$weekNumber} / {$weekYear}" : 'Todas las semanas',
            'status' => $statusFilter !== 'all' && $statusFilter !== null ? ($statusLabels[$statusFilter] ?? $statusFilter) : 'Todos',
            'quick_filter' => $quickFilter !== 'all' ? $quickFilter : null,
            'search' => $search !== '' ? $search : null,
        ];

        $pdf = Pdf::loadView('pdf.payment-history', [
            'project' => $project,
            'paymentsPayload' => $paymentsPayload,
            'showDepartmentColumn' => $departmentAll,
            'filtersApplied' => $filtersApplied,
            'totalDisplayRequested' => $totalDisplayRequested,
            'totalDisplayApproved' => $totalDisplayApproved,
            'statusLabels' => $statusLabels,
            'generatedAt' => Carbon::now(),
            'generatedBy' => $user->name,
            'displayPrefix' => $displayCurrency->prefix,
            'displayCurrencyName' => $displayCurrency->name,
            'exchangeRates' => $this->exchangeRateNote($displayCurrency),
        ])->setPaper('legal', 'landscape');

        $slug = Str::slug($project->name);
        $stamp = Carbon::now()->format('Ymd');

        return $pdf->download("historial-pagos-{$slug}-{$stamp}.pdf");
    }
}
