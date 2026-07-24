<?php

namespace App\Http\Controllers;

use App\Exports\PaymentHistoryExport;
use App\Http\Controllers\Concerns\ResolvesDisplayCurrency;
use App\Models\InvestmentPaymentRequest;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InvestmentPaymentHistoryExcelController extends Controller
{
    use ResolvesDisplayCurrency;

    private const STATUS_LABELS = [
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

    public function __invoke(Request $request, Project $project): BinaryFileResponse
    {
        $user = $request->user();
        $canSeeAllDepartments = $user->hasAnyRole(['super_admin', 'ceo', 'project_manager']);

        $project->loadMissing('currency');
        $displayCurrency = $this->resolveDisplayCurrency($request, $project);

        $departmentParam = $request->input('department_id');
        $departmentId = null;
        $departmentIds = null;
        $userDepartmentIds = $user->departments()->pluck('departments.id')->all();

        if (! $canSeeAllDepartments) {
            if ($departmentParam !== null && $departmentParam !== '' && $departmentParam !== 'all') {
                $requestedId = (int) $departmentParam;
                abort_unless(in_array($requestedId, $userDepartmentIds, true), 403, 'No tienes acceso a ese departamento.');
                $departmentId = $requestedId;
            } else {
                $departmentIds = $userDepartmentIds;
            }
        } elseif ($departmentParam !== null && $departmentParam !== '' && $departmentParam !== 'all') {
            $departmentId = (int) $departmentParam;
        }

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

        $rows = $payments->map(function (InvestmentPaymentRequest $p) use ($displayCurrency): array {
            $nativeRate = (float) ($p->currency?->exchange_rate ?? 1);
            $totalMxn = (float) $p->total * $nativeRate;
            $approvedMxn = $p->approved_amount !== null
                ? (float) $p->approved_amount * $nativeRate
                : null;

            return [
                'folio' => '#'.str_pad((string) $p->folio_number, 5, '0', STR_PAD_LEFT),
                'status_label' => self::STATUS_LABELS[$p->status] ?? $p->status,
                'payment_provision_date' => $p->payment_provision_date?->format('Y-m-d'),
                'week' => $p->payment_provision_date
                    ? 'S'.$p->payment_provision_date->isoWeek.'/'.$p->payment_provision_date->isoWeekYear
                    : null,
                'concept' => $p->investmentRequest?->investmentExpenseConcept?->name,
                'description' => $p->description,
                'category' => $p->investmentRequest?->investmentExpenseConcept?->category?->name,
                'department' => $p->department?->name,
                'provider' => $p->provider,
                'rfc' => $p->rfc,
                'payment_type' => $p->payment_type,
                'total_display' => $this->mxnToDisplay($totalMxn, $displayCurrency),
                'approved_display' => $approvedMxn !== null
                    ? $this->mxnToDisplay($approvedMxn, $displayCurrency)
                    : null,
                'native_currency' => $p->currency?->prefix ?? 'MXN',
                'user' => $p->user?->name,
                'created_at' => $p->created_at?->format('Y-m-d'),
            ];
        })->values();

        $slug = Str::slug($project->name);
        $stamp = Carbon::now()->format('Ymd');
        $filename = "historial-pagos-{$slug}-{$stamp}.xlsx";

        return Excel::download(
            new PaymentHistoryExport($rows, $displayCurrency->prefix),
            $filename,
        );
    }
}
