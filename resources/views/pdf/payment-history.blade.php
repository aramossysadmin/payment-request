<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Pagos — {{ $project->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #191731; line-height: 1.4; }
        .page { padding: 20px 25px; }

        /* Header */
        .header { border-bottom: 2px solid #191731; padding-bottom: 10px; margin-bottom: 12px; }
        .header-title { font-size: 16px; font-weight: bold; color: #191731; }
        .header-subtitle { font-size: 11px; color: #b8860b; margin-top: 2px; font-weight: bold; }
        .header-meta { font-size: 9px; color: #666; margin-top: 4px; }

        /* Filters applied */
        .filters { margin-bottom: 12px; padding: 8px 10px; background: #f5f5f5; border-radius: 3px; font-size: 9px; }
        .filters .label { font-weight: bold; color: #555; }
        .filters .value { color: #191731; }
        .filters .row { margin-bottom: 2px; }

        /* Table */
        table.history { width: 100%; border-collapse: collapse; table-layout: fixed; word-wrap: break-word; overflow-wrap: anywhere; }
        table.history th { background: #191731; color: #fff; padding: 6px 5px; text-align: left; font-size: 8px; text-transform: uppercase; letter-spacing: 0.5px; overflow: hidden; }
        table.history td { padding: 5px; border-bottom: 1px solid #e5e5e5; font-size: 9px; vertical-align: top; overflow: hidden; word-wrap: break-word; overflow-wrap: anywhere; }
        table.history tr:nth-child(even) td { background: #fafafa; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .nowrap { white-space: nowrap; }
        .muted { color: #888; }
        .small { font-size: 8px; }

        /* Status pill */
        .status { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 7px; font-weight: bold; text-transform: uppercase; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-other { background: #e5e7eb; color: #374151; }

        /* Totals */
        .totals { margin-top: 14px; padding: 8px 10px; background: #f5f5f5; border-radius: 3px; }
        .totals-title { font-size: 9px; font-weight: bold; text-transform: uppercase; color: #b8860b; margin-bottom: 4px; }
        .totals-row { display: table; width: 100%; margin: 2px 0; }
        .totals-label { display: table-cell; width: 70%; }
        .totals-value { display: table-cell; width: 30%; text-align: right; font-weight: bold; font-family: DejaVu Sans Mono, monospace; }

        /* Footer */
        .footer { margin-top: 20px; padding-top: 8px; border-top: 1px solid #ccc; font-size: 8px; color: #888; text-align: center; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="header-title">Historial de Pagos de Inversión</div>
        <div class="header-subtitle">{{ $project->name }}</div>
        <div class="header-meta">
            Generado el {{ $generatedAt->format('d/m/Y H:i') }} por {{ $generatedBy }}
            · {{ count($paymentsPayload) }} {{ count($paymentsPayload) === 1 ? 'pago' : 'pagos' }}
            · Moneda: {{ $displayCurrencyName }} ({{ $displayPrefix }})
        </div>
    </div>

    <div class="filters">
        <div class="row"><span class="label">Departamento:</span> <span class="value">{{ $filtersApplied['department'] }}</span></div>
        <div class="row"><span class="label">Semana:</span> <span class="value">{{ $filtersApplied['week'] }}</span></div>
        <div class="row"><span class="label">Status:</span> <span class="value">{{ $filtersApplied['status'] }}</span></div>
        @if ($filtersApplied['quick_filter'])
            <div class="row"><span class="label">Filtro rápido:</span> <span class="value">{{ ucfirst(str_replace('_', ' ', $filtersApplied['quick_filter'])) }}</span></div>
        @endif
        @if ($filtersApplied['search'])
            <div class="row"><span class="label">Búsqueda:</span> <span class="value">"{{ $filtersApplied['search'] }}"</span></div>
        @endif
    </div>

    @if (count($paymentsPayload) === 0)
        <p class="muted" style="text-align:center; padding: 20px;">No hay pagos que coincidan con los filtros aplicados.</p>
    @else
        <table class="history">
            <thead>
            <tr>
                <th style="width: 5%">Folio</th>
                <th style="width: {{ $showDepartmentColumn ? '7%' : '8%' }}">Estatus</th>
                <th style="width: {{ $showDepartmentColumn ? '7%' : '8%' }}">F. Programación Pago</th>
                <th style="width: {{ $showDepartmentColumn ? '11%' : '12%' }}">Concepto</th>
                <th style="width: {{ $showDepartmentColumn ? '14%' : '15%' }}">Descripción</th>
                <th style="width: {{ $showDepartmentColumn ? '9%' : '10%' }}">Categoría del Concepto</th>
                <th style="width: {{ $showDepartmentColumn ? '11%' : '12%' }}">Proveedor</th>
                @if ($showDepartmentColumn)
                    <th style="width: 6%">Depto</th>
                @endif
                <th class="text-right" style="width: 8%">Monto Solicitado ({{ $displayPrefix }})</th>
                <th class="text-right" style="width: 8%">Monto Aprobado ({{ $displayPrefix }})</th>
                <th style="width: 8%">Solicitante</th>
                <th style="width: 6%">F. Solicitud</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($paymentsPayload as $row)
                @php
                    $p = $row['model'];
                    $statusClass = in_array($p->status, ['completed', 'final_approved', 'approved'])
                        ? 'status-completed'
                        : (in_array($p->status, ['ceo_rejected', 'projectmanager_rejected', 'final_rejected', 'rejected'])
                            ? 'status-rejected'
                            : (in_array($p->status, ['submitted', 'pending_approval', 'documents_pending', 'final_pending', 'projectmanager_review'])
                                ? 'status-pending'
                                : 'status-other'));
                @endphp
                <tr>
                    <td class="nowrap">#{{ str_pad($p->folio_number, 5, '0', STR_PAD_LEFT) }}</td>
                    <td class="nowrap"><span class="status {{ $statusClass }}">{{ $statusLabels[$p->status] ?? $p->status }}</span></td>
                    <td class="nowrap">{{ $p->payment_provision_date?->format('Y-m-d') ?? '—' }}</td>
                    <td>{{ $p->investmentRequest?->investmentExpenseConcept?->name ?? '—' }}</td>
                    <td class="small">{{ $p->description ?? '—' }}</td>
                    <td class="small">{{ $p->investmentRequest?->investmentExpenseConcept?->category?->name ?? '—' }}</td>
                    <td>{{ $p->provider }}</td>
                    @if ($showDepartmentColumn)
                        <td class="small">{{ $p->department?->name ?? '—' }}</td>
                    @endif
                    <td class="text-right nowrap">
                        <span class="small muted">{{ $displayPrefix }}</span> ${{ number_format((float) $row['total_display'], 2) }}
                    </td>
                    <td class="text-right nowrap">
                        @if ($row['approved_display'] !== null)
                            <span class="small muted">{{ $displayPrefix }}</span> ${{ number_format((float) $row['approved_display'], 2) }}
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>
                    <td class="small">{{ $p->user?->name ?? '—' }}</td>
                    <td class="nowrap">{{ $p->created_at?->format('Y-m-d') ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="totals-title">Totales en {{ $displayPrefix }}</div>
            <div class="totals-row">
                <div class="totals-label">Total solicitado</div>
                <div class="totals-value">{{ $displayPrefix }} ${{ number_format($totalDisplayRequested, 2) }}</div>
            </div>
            <div class="totals-row">
                <div class="totals-label">Total aprobado (con fallback a solicitado si aún no hay aprobado)</div>
                <div class="totals-value">{{ $displayPrefix }} ${{ number_format($totalDisplayApproved, 2) }}</div>
            </div>
            @if (! empty($exchangeRates))
                <div class="muted small" style="margin-top: 6px;">
                    Convertido a {{ $displayCurrencyName }} ({{ $displayPrefix }}). Tipos de cambio (1 unidad = X MXN):
                    @foreach ($exchangeRates as $r)
                        {{ $r['prefix'] }} = {{ number_format($r['exchange_rate'], 4) }}@if (! $loop->last) · @endif
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    <div class="footer">
        Linking — payment-request · Documento confidencial
    </div>
</div>
</body>
</html>
