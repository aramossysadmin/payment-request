<?php

namespace App\Services;

use App\Enums\InvestmentPaymentType;
use App\Models\Department;
use App\Models\InvestmentPaymentRequest;
use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StrategyPaymentHistory
{
    public const ALL_PROJECTS = 'all';

    /**
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        'draft' => 'Borrador',
        'submitted' => 'Enviado',
        'ceo_approved' => 'CEO Aprobó',
        'ceo_rejected' => 'CEO Rechazó',
        'projectmanager_review' => 'En revisión PM',
        'projectmanager_approved' => 'PM Aprobó (histórico)',
        'projectmanager_rejected' => 'PM Rechazó',
        'final_pending' => 'Pendiente Final (histórico)',
        'final_approved' => 'Aprobado Final',
        'final_rejected' => 'Rechazado Final',
        'documents_pending' => 'Esperando docs',
        'pending_approval' => 'Pendiente',
        'approved' => 'Aprobado',
        'rejected' => 'Rechazado',
        'completed' => 'Completado',
        'scheduled_for_bank' => 'Programado en banco',
        'receipt_attached' => 'Comprobante adjunto',
        'auto_cancelled' => 'Cancelado',
    ];

    /**
     * Pagos de tipo estrategia y los que se capturaron como estrategia y después cambiaron de tipo.
     * Sin proyecto devuelve los de todas las Hojas de Inversión que no estén borradas.
     *
     * @return Collection<int, InvestmentPaymentRequest>
     */
    public function payments(?int $projectId, ?int $departmentId = null, ?string $status = null): Collection
    {
        return InvestmentPaymentRequest::query()
            ->where(fn ($query) => $query
                ->where('payment_type', InvestmentPaymentType::Estrategia->value)
                ->orWhereNotNull('strategy_factor_snapshot'))
            ->whereHas('investmentRequest', fn ($query) => $query
                ->whereHas('project')
                ->when($projectId, fn ($query) => $query->where('project_id', $projectId)))
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->with(['user', 'department', 'currency', 'investmentRequest.investmentExpenseConcept', 'investmentRequest.project'])
            ->orderByDesc('folio_number')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function row(InvestmentPaymentRequest $payment): array
    {
        $breakdown = $payment->strategyBreakdown();

        return [
            'uuid' => $payment->uuid,
            'folio_number' => $payment->folio_number,
            'project_name' => $payment->investmentRequest?->project?->name,
            'department_id' => $payment->department_id,
            'department_name' => $payment->department?->name,
            'requester_name' => $payment->user?->name,
            'concept_name' => $payment->investmentRequest?->investmentExpenseConcept?->name,
            'provider' => $payment->provider,
            'strategy_name' => $breakdown ? $payment->strategy_name_snapshot : null,
            'strategy_rate' => $breakdown['rate'] ?? null,
            'strategy_amount' => $breakdown['amount'] ?? null,
            'strategy_surcharge' => $breakdown['surcharge'] ?? null,
            'total' => (string) $payment->total,
            'approved_amount' => $payment->approved_amount !== null ? (string) $payment->approved_amount : null,
            'currency_prefix' => $payment->currency?->prefix ?? 'MXN',
            'payment_type' => $payment->payment_type,
            'status' => $payment->status,
            'captured_at' => $payment->created_at?->format('Y-m-d'),
            'capture_week' => $payment->created_at?->isoWeek,
            'capture_week_year' => $payment->created_at?->isoWeekYear,
            'payment_provision_date' => $payment->payment_provision_date?->format('Y-m-d'),
            'provision_week' => $payment->payment_provision_date?->isoWeek,
            'provision_week_year' => $payment->payment_provision_date?->isoWeekYear,
        ];
    }

    /**
     * Datos de los reportes PDF y Excel, con los mismos filtros que la pantalla.
     *
     * @return array{projectName: string, rows: array<int, array<string, mixed>>, department: ?string, status: ?string, captureWeek: ?string, provisionWeek: ?string}
     */
    public function report(Request $request): array
    {
        abort_unless($request->user()->hasPortalPermission('view_portal::strategies'), Response::HTTP_FORBIDDEN);

        $project = $request->input('project_id') === self::ALL_PROJECTS
            ? null
            : Project::query()->findOrFail($request->integer('project_id'));
        $departmentId = $request->integer('department_id') ?: null;
        $status = array_key_exists((string) $request->input('status'), self::STATUS_LABELS) ? (string) $request->input('status') : null;

        $captureWeek = $this->weekFilter($request->input('capture_week'));
        $provisionWeek = $this->weekFilter($request->input('provision_week'));

        $rows = $this->payments($project?->id, $departmentId, $status)
            ->map(fn (InvestmentPaymentRequest $payment) => $this->row($payment))
            ->when($captureWeek, fn ($rows) => $rows->filter(fn (array $row) => $row['capture_week_year'] === $captureWeek['year'] && $row['capture_week'] === $captureWeek['week']))
            ->when($provisionWeek, fn ($rows) => $rows->filter(fn (array $row) => $row['provision_week_year'] === $provisionWeek['year'] && $row['provision_week'] === $provisionWeek['week']));

        return [
            'projectName' => $project?->name ?? 'Todas las Hojas de Inversión',
            'rows' => $rows->values()->all(),
            'department' => $departmentId ? Department::query()->whereKey($departmentId)->value('name') : null,
            'status' => $status ? self::STATUS_LABELS[$status] : null,
            'captureWeek' => $captureWeek ? "Semana {$captureWeek['week']} ({$captureWeek['year']})" : null,
            'provisionWeek' => $provisionWeek ? "Semana {$provisionWeek['week']} ({$provisionWeek['year']})" : null,
        ];
    }

    /**
     * Interpreta un filtro de semana ISO con formato `2026-45`; un valor inválido se ignora.
     *
     * @return array{year: int, week: int}|null
     */
    private function weekFilter(mixed $value): ?array
    {
        if (! is_string($value) || preg_match('/^(\d{4})-(\d{1,2})$/', $value, $matches) !== 1) {
            return null;
        }

        $week = (int) $matches[2];

        return $week >= 1 && $week <= 53 ? ['year' => (int) $matches[1], 'week' => $week] : null;
    }
}
