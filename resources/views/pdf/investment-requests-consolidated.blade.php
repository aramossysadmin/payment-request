<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitudes de Inversión — {{ $project->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #191731; line-height: 1.4; }
        .page { padding: 20px 25px; }

        /* Top brand band */
        .top-band { height: 5px; background: #191731; margin: -20px -25px 14px -25px; }

        /* Header */
        .header { border-bottom: 1px solid #d5d5df; padding-bottom: 14px; margin-bottom: 16px; }
        .header-title { font-size: 22px; font-weight: bold; color: #191731; text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 12px; }
        .header-columns { width: 100%; border-collapse: separate; border-spacing: 10px 0; }
        .header-columns .col-left, .header-columns .col-right { background: #fafbfd; border: 1px solid #d5d5df; border-radius: 6px; padding: 12px 14px; vertical-align: top; box-shadow: 0 1px 2px rgba(25, 23, 49, 0.06); }
        .header-columns .col-left { width: 55%; }
        .header-columns .col-right { width: 45%; }
        .col-label { font-size: 9px; text-transform: uppercase; color: #888; letter-spacing: 0.4px; margin-bottom: 6px; }
        .branch-row { font-size: 11px; margin-bottom: 5px; color: #444; }
        .branch-row .lbl { color: #888; }
        .branch-row .val { color: #191731; font-weight: 500; }

        /* Mini project card (right column: no border of its own, inherits column card) */
        .project-card-label { font-size: 9px; text-transform: uppercase; color: #888; letter-spacing: 0.4px; }
        .project-card-name { font-size: 13px; font-weight: bold; color: #191731; margin-top: 3px; }
        .project-card-branch { font-size: 10px; color: #555; margin-top: 4px; }
        .project-card-rfc { font-size: 10px; color: #555; font-family: DejaVu Sans Mono, monospace; margin-left: 10px; }
        .project-card-footer { display: table; width: 100%; margin-top: 8px; padding-top: 8px; border-top: 1px solid #e5e5e9; font-size: 10.5px; }
        .project-card-footer .lbl { display: table-cell; color: #666; }
        .project-card-footer .val { display: table-cell; text-align: right; font-weight: bold; color: #191731; font-family: DejaVu Sans Mono, monospace; }

        /* Section titles */
        .section-title { font-size: 12.5px; font-weight: bold; text-transform: uppercase; color: #191731; margin: 18px 0 8px 0; letter-spacing: 0.5px; }

        /* Department breakdown cards */
        .dept-grid { width: 100%; border-collapse: separate; border-spacing: 7px; margin-bottom: 8px; }
        .dept-grid tr { page-break-inside: avoid; }
        .dept-card { border: 1px solid #d5d5df; border-radius: 5px; padding: 10px 12px; background: #ffffff; vertical-align: top; box-shadow: 0 1px 2px rgba(25, 23, 49, 0.06); }
        .dept-header-inner { width: 100%; margin-bottom: 6px; }
        .dept-header-inner .name-col { vertical-align: middle; }
        .dept-header-inner .total-col { vertical-align: middle; text-align: right; white-space: nowrap; }
        .dept-name { font-size: 12px; font-weight: bold; color: #191731; text-transform: uppercase; letter-spacing: 0.3px; }
        .dept-count { font-size: 9.5px; color: #888; margin-top: 1px; }
        .dept-total { font-size: 15px; font-weight: bold; color: #191731; font-family: DejaVu Sans Mono, monospace; }
        .dept-bar { height: 7px; background: #e5e5e9; border-radius: 4px; margin-top: 4px; overflow: hidden; }
        .dept-bar-fill { height: 100%; }
        .dept-bar-fill.ok { background: #10b981; }
        .dept-bar-fill.warn { background: #f59e0b; }
        .dept-bar-fill.danger { background: #dc2626; }
        .dept-percent { font-size: 9px; color: #666; text-align: right; margin-top: 3px; }
        .dept-rows { margin-top: 6px; font-size: 11px; }
        .dept-row { display: table; width: 100%; margin-bottom: 4px; }
        .dept-row .lbl { display: table-cell; }
        .dept-row .val { display: table-cell; text-align: right; font-family: DejaVu Sans Mono, monospace; }
        .dept-committed { color: #b91c1c; }
        .dept-paid { color: #059669; }
        .dept-pending { color: #191731; }

        /* Wide card layout for single-department reports */
        .dept-card-wide { width: 100%; margin-bottom: 6px; }
        .dept-card-wide table { width: 100%; }
        .dept-card-wide .col-summary { width: 55%; vertical-align: top; padding-right: 25px; }
        .dept-card-wide .col-details { width: 45%; vertical-align: middle; padding-left: 20px; border-left: 1px solid #e5e5e9; }
        .dept-card-wide .dept-rows { margin-top: 0; font-size: 10.5px; }
        .dept-card-wide .dept-row { margin-bottom: 3px; }

        /* Department banner (detail section) */
        .dept-banner { background: #eef2f7; border-left: 4px solid #191731; padding: 14px 18px; font-size: 19px; font-weight: bold; color: #191731; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 16px; margin-bottom: 0; page-break-after: avoid; box-shadow: 0 1px 2px rgba(25, 23, 49, 0.08); border-top-left-radius: 4px; border-top-right-radius: 4px; }

        /* Detail table */
        table.detail { width: 100%; border-collapse: collapse; table-layout: fixed; word-wrap: break-word; overflow-wrap: anywhere; margin-bottom: 14px; }
        table.detail th { background: #191731; color: #fff; padding: 7px 6px; text-align: left; font-size: 9.5px; text-transform: uppercase; letter-spacing: 0.5px; overflow: hidden; }
        table.detail th.text-right { text-align: right; }
        table.detail td { padding: 6px; border-bottom: 1px solid #e5e5e5; font-size: 10.5px; vertical-align: top; overflow: hidden; word-wrap: break-word; overflow-wrap: anywhere; }
        table.detail tr.group-parent-row { page-break-inside: avoid; }
        table.detail tr.group-parent-row td { background: #f5f6f9; }
        table.detail tr.group-child-row td { background: #fafbfd; font-size: 9.5px; padding-left: 8px; color: #4a4a5a; }
        table.detail tr.group-child-row td:first-child { border-left: 3px solid #d5d5df; padding-left: 24px; }
        table.detail tr.group-child-row td.child-concept { color: #999; }
        table.detail tr.flat-row td { background: #ffffff; }

        .text-right { text-align: right; }
        .nowrap { white-space: nowrap; }
        .muted { color: #888; }
        .small { font-size: 9px; }
        .mono { font-family: DejaVu Sans Mono, monospace; }
        .saldo-zero { color: #dc2626; font-weight: 600; }
        .curr-prefix { color: #999; font-size: 0.75em; font-weight: normal; letter-spacing: 0.3px; margin-right: 3px; }

        /* Folio + status badges */
        .folio-cell { font-family: DejaVu Sans Mono, monospace; font-size: 9.5px; color: #191731; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 6px; font-size: 8.5px; font-weight: bold; text-transform: uppercase; margin-left: 3px; letter-spacing: 0.3px; }
        .badge-inicial { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }
        .badge-aditiva { background: #fef3c7; color: #92400e; border: 1px solid #fbbf24; }
        .badge-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        .badge-info { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }
        .badge-warning { background: #fef3c7; color: #92400e; border: 1px solid #fbbf24; }
        .badge-purple { background: #ede9fe; color: #5b21b6; border: 1px solid #c4b5fd; }
        .badge-neutral { background: #e5e7eb; color: #374151; border: 1px solid #d1d5db; }

        /* Footer */
        .footer { margin-top: 22px; padding-top: 10px; border-top: 1px solid #ccc; font-size: 9px; color: #888; text-align: center; }
        .footer-meta { margin-bottom: 2px; color: #444; }
    </style>
</head>
<body>
<div class="top-band"></div>
<div class="page">
    <div class="header">
        <div class="header-title">{{ $project->name }}</div>
        <table class="header-columns">
            <tr>
                <td class="col-left">
                    <div class="col-label">Datos de Sucursal</div>
                    <div class="branch-row"><span class="lbl">Sucursal:</span> <span class="val">{{ $project->branch?->name ?? '—' }}</span></div>
                    <div class="branch-row"><span class="lbl">Razón social:</span> <span class="val">{{ $project->branch?->society?->name ?? '—' }}</span></div>
                    <div class="branch-row"><span class="lbl">RFC:</span> <span class="val mono">{{ $project->branch?->society?->rfc ?? '—' }}</span></div>
                    <div class="branch-row"><span class="lbl">CeCo:</span> <span class="val mono">{{ $project->branch?->cost_center ?? '—' }}</span></div>
                </td>
                <td class="col-right">
                    {{-- Mini tarjeta del proyecto (mismo estilo del card en el listado consolidado) --}}
                    <div class="project-card-label">Proyecto</div>
                    <div class="project-card-name">{{ $project->name }}</div>
                    <div>
                        <span class="project-card-branch">{{ $project->branch?->name ?? '—' }}</span>
                        <span class="project-card-rfc">{{ $project->branch?->society?->rfc ?? '—' }}</span>
                    </div>
                    <div class="project-card-footer">
                        <span class="lbl">{{ $projectTotalCount }} {{ $projectTotalCount === 1 ? 'concepto' : 'conceptos' }}</span>
                        <span class="val"><span class="curr-prefix">{{ $displayPrefix }}</span>${{ number_format($projectTotalAmount, 2) }}</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title">Inversión por Departamento</div>

    @php
        $percentClass = fn (float $p) => $p >= 90 ? 'danger' : ($p >= 70 ? 'warn' : 'ok');

        $groupLabel = function (int $initials, int $additives): string {
            $initialsPart = null;
            if ($initials === 1) {
                $initialsPart = 'presupuesto inicial';
            } elseif ($initials > 1) {
                $initialsPart = "{$initials} presupuestos iniciales";
            }

            $additivesPart = null;
            if ($additives > 0) {
                $additivesPart = "{$additives} ".($additives === 1 ? 'aditiva' : 'aditivas');
            }

            if ($initialsPart && $additivesPart) {
                return "{$initialsPart} + {$additivesPart}";
            }

            return $initialsPart ?? $additivesPart ?? '';
        };
    @endphp

    @if ($departmentBreakdown->isEmpty())
        <p class="muted" style="padding: 10px 0;">Este proyecto no tiene inversión registrada en los departamentos visibles.</p>
    @elseif ($departmentBreakdown->count() === 1)
        @php
            $d = $departmentBreakdown->first();
            $percent = $d['percent_consumed'];
            $barWidth = min(100, max(0, $percent));
        @endphp
        <div class="dept-card dept-card-wide">
            <table>
                <tr>
                    <td class="col-summary">
                        <table class="dept-header-inner">
                            <tr>
                                <td class="name-col">
                                    <div class="dept-name">{{ $d['name'] }}</div>
                                    <div class="dept-count">{{ $d['count'] }} {{ $d['count'] === 1 ? 'concepto' : 'conceptos' }}</div>
                                </td>
                                <td class="total-col">
                                    <div class="dept-total"><span class="curr-prefix">{{ $displayPrefix }}</span>{{ number_format($d['total'], 2) }}</div>
                                </td>
                            </tr>
                        </table>
                        <div class="dept-bar"><div class="dept-bar-fill {{ $percentClass($percent) }}" style="width: {{ $barWidth }}%"></div></div>
                        <div class="dept-percent">{{ number_format($percent, 0) }}% consumido</div>
                    </td>
                    <td class="col-details">
                        <div class="dept-rows">
                            <div class="dept-row"><span class="lbl dept-committed">Comprometido</span><span class="val dept-committed"><span class="curr-prefix">{{ $displayPrefix }}</span>{{ number_format($d['committed'], 2) }}</span></div>
                            <div class="dept-row"><span class="lbl dept-paid">Pagado</span><span class="val dept-paid"><span class="curr-prefix">{{ $displayPrefix }}</span>{{ number_format($d['paid'], 2) }}</span></div>
                            <div class="dept-row"><span class="lbl dept-pending">Disponible</span><span class="val dept-pending"><span class="curr-prefix">{{ $displayPrefix }}</span>{{ number_format($d['pending'], 2) }}</span></div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    @else
        @php
            $chunkSize = min(3, $departmentBreakdown->count());
            $chunks = $departmentBreakdown->chunk($chunkSize);
            $colWidth = 100 / $chunkSize;
        @endphp
        @foreach ($chunks as $chunk)
            <table class="dept-grid">
                <tr>
                    @foreach ($chunk as $d)
                        @php
                            $percent = $d['percent_consumed'];
                            $barWidth = min(100, max(0, $percent));
                        @endphp
                        <td class="dept-card" style="width: {{ $colWidth }}%">
                            <table class="dept-header-inner">
                                <tr>
                                    <td class="name-col">
                                        <div class="dept-name">{{ $d['name'] }}</div>
                                        <div class="dept-count">{{ $d['count'] }} {{ $d['count'] === 1 ? 'concepto' : 'conceptos' }}</div>
                                    </td>
                                    <td class="total-col">
                                        <div class="dept-total"><span class="curr-prefix">{{ $displayPrefix }}</span>{{ number_format($d['total'], 2) }}</div>
                                    </td>
                                </tr>
                            </table>
                            <div class="dept-bar"><div class="dept-bar-fill {{ $percentClass($percent) }}" style="width: {{ $barWidth }}%"></div></div>
                            <div class="dept-percent">{{ number_format($percent, 0) }}% consumido</div>
                            <div class="dept-rows">
                                <div class="dept-row"><span class="lbl dept-committed">Comprometido</span><span class="val dept-committed"><span class="curr-prefix">{{ $displayPrefix }}</span>{{ number_format($d['committed'], 2) }}</span></div>
                                <div class="dept-row"><span class="lbl dept-paid">Pagado</span><span class="val dept-paid"><span class="curr-prefix">{{ $displayPrefix }}</span>{{ number_format($d['paid'], 2) }}</span></div>
                                <div class="dept-row"><span class="lbl dept-pending">Disponible</span><span class="val dept-pending"><span class="curr-prefix">{{ $displayPrefix }}</span>{{ number_format($d['pending'], 2) }}</span></div>
                            </div>
                        </td>
                    @endforeach
                    @for ($i = count($chunk); $i < $chunkSize; $i++)
                        <td style="width: {{ $colWidth }}%"></td>
                    @endfor
                </tr>
            </table>
        @endforeach
    @endif

    <div class="section-title">Detalle de Solicitudes de Inversión</div>

    @if ($departmentsWithGroups->isEmpty())
        <p class="muted" style="padding: 10px 0;">No hay solicitudes de inversión para el filtro aplicado.</p>
    @else
        @foreach ($departmentsWithGroups as $dept)
            <div class="dept-banner">{{ $dept['name'] }}</div>
            <table class="detail">
                <thead>
                <tr>
                    <th style="width: 28%">Concepto de Inversión</th>
                    <th style="width: 30%">Descripción</th>
                    <th style="width: 18%">Folio</th>
                    <th class="text-right" style="width: 12%">Presupuesto <span class="muted" style="font-weight: normal;">({{ $displayPrefix }})</span></th>
                    <th class="text-right" style="width: 12%">Saldo <span class="muted" style="font-weight: normal;">({{ $displayPrefix }})</span></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($dept['groups'] as $g)
                    @if ($g['items_count'] === 1)
                        @php $item = $g['items'][0]; @endphp
                        <tr class="flat-row">
                            <td>{{ $g['concept_category'] ? $g['concept_category'].' - '.$g['concept_name'] : $g['concept_name'] }}</td>
                            <td>{{ $item['description'] ?: '—' }}</td>
                            <td class="folio-cell">
                                #{{ $item['folio_number'] }}
                                <span class="badge {{ $item['is_addendum'] ? 'badge-aditiva' : 'badge-inicial' }}">{{ $item['type_label'] }}</span>
                                <span class="badge badge-{{ $item['status_color'] ?? 'neutral' }}">{{ $item['status_label'] }}</span>
                            </td>
                            <td class="text-right mono">{{ number_format($g['group_budget'], 2) }}</td>
                            <td class="text-right mono {{ (float) $g['group_remaining'] === 0.0 ? 'saldo-zero' : '' }}">{{ number_format($g['group_remaining'], 2) }}</td>
                        </tr>
                    @else
                        {{-- Fila padre del grupo --}}
                        <tr class="group-parent-row">
                            <td>
                                {{ $g['concept_category'] ? $g['concept_category'].' - '.$g['concept_name'] : $g['concept_name'] }}
                                <div class="small muted" style="font-weight: normal;">{{ $groupLabel($g['initials_count'], $g['additives_count']) }}</div>
                            </td>
                            <td class="muted">—</td>
                            <td class="muted">—</td>
                            <td class="text-right mono">{{ number_format($g['group_budget'], 2) }}</td>
                            <td class="text-right mono {{ (float) $g['group_remaining'] === 0.0 ? 'saldo-zero' : '' }}">{{ number_format($g['group_remaining'], 2) }}</td>
                        </tr>
                        {{-- Sub-filas expandidas del grupo --}}
                        @foreach ($g['items'] as $item)
                            <tr class="group-child-row">
                                <td class="child-concept"></td>
                                <td>{{ $item['description'] ?: '—' }}</td>
                                <td class="folio-cell">
                                    #{{ $item['folio_number'] }}
                                    <span class="badge {{ $item['is_addendum'] ? 'badge-aditiva' : 'badge-inicial' }}">{{ $item['type_label'] }}</span>
                                    <span class="badge badge-{{ $item['status_color'] ?? 'neutral' }}">{{ $item['status_label'] }}</span>
                                </td>
                                <td class="text-right mono">{{ number_format($item['total_display'], 2) }}</td>
                                <td></td>
                            </tr>
                        @endforeach
                    @endif
                @endforeach
                </tbody>
            </table>
        @endforeach
    @endif

    <div class="footer">
        <div class="footer-meta">Generado el {{ $generatedAt->format('d/m/Y H:i') }} por {{ $generatedBy }} · Departamento: {{ $departmentFilterName }} · Moneda: {{ $displayCurrencyName }} ({{ $displayPrefix }})</div>
        @if (! empty($exchangeRates))
            <div class="footer-meta" style="margin-top: 4px;">
                Tipos de cambio usados (1 unidad = X MXN):
                @foreach ($exchangeRates as $r)
                    {{ $r['prefix'] }} = {{ number_format($r['exchange_rate'], 4) }}@if (! $loop->last) · @endif
                @endforeach
            </div>
        @endif
        <div>Reporte generado automáticamente por el portal de Payment Request</div>
    </div>
</div>

{{-- Paginación X de Y (DomPDF inline PHP). --}}
<script type="text/php">
    if (isset($pdf)) {
        $font = $fontMetrics->get_font('DejaVu Sans', 'normal');
        $pdf->page_text(870, 590, 'Página {PAGE_NUM} de {PAGE_COUNT}', $font, 8, [0.55, 0.55, 0.55]);
    }
</script>
</body>
</html>
