<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Aprobación Final — {{ $project->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #191731; line-height: 1.4; }
        .page { padding: 20px 25px; }

        .header { border-bottom: 2px solid #191731; padding-bottom: 10px; margin-bottom: 12px; }
        .header-title { font-size: 16px; font-weight: bold; color: #191731; }
        .header-subtitle { font-size: 11px; color: #b8860b; margin-top: 2px; font-weight: bold; }
        .header-meta { font-size: 9px; color: #666; margin-top: 4px; }

        .meta-box { margin-bottom: 12px; padding: 8px 10px; background: #f5f5f5; border-radius: 3px; font-size: 9px; }
        .meta-box .label { font-weight: bold; color: #555; }
        .meta-box .value { color: #191731; }
        .meta-box .row { margin-bottom: 2px; }

        .dept-section { margin-top: 14px; page-break-inside: avoid; }
        .dept-title { background: #f9f9f9; color: #b8860b; padding: 6px 10px; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1.5px; border-bottom: 1px solid #e5e5e5; }

        table.detail { width: 100%; border-collapse: collapse; }
        table.detail th { background: #191731; color: #fff; padding: 5px 4px; text-align: left; font-size: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        table.detail td { padding: 5px 4px; border-bottom: 1px solid #e5e5e5; font-size: 8.5px; vertical-align: top; }
        table.detail tr:nth-child(even) td { background: #fafafa; }
        .text-right { text-align: right; }
        .nowrap { white-space: nowrap; }
        .muted { color: #888; }
        .small { font-size: 8px; }

        .dept-subtotal { background: #f9f9f9; border-top: 2px solid #b8860b; padding: 6px 10px; font-size: 9px; font-weight: bold; text-align: right; color: #191731; }
        .dept-subtotal .ccy { display: inline-block; margin-left: 14px; font-family: DejaVu Sans Mono, monospace; }
        .dept-subtotal .ccy .muted { color: #888; font-weight: normal; }

        .totals { margin-top: 16px; padding: 10px 12px; background: #f5f5f5; border-top: 2px solid #191731; border-radius: 0 0 3px 3px; }
        .totals-title { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #b8860b; margin-bottom: 6px; letter-spacing: 0.5px; }
        .totals-row { display: table; width: 100%; margin: 2px 0; font-size: 11px; }
        .totals-label { display: table-cell; width: 70%; color: #555; }
        .totals-value { display: table-cell; width: 30%; text-align: right; font-weight: bold; color: #b8860b; font-family: DejaVu Sans Mono, monospace; }
        .totals .count { font-size: 8px; color: #888; margin-top: 4px; }

        .footer { margin-top: 20px; padding-top: 8px; border-top: 1px solid #ccc; font-size: 8px; color: #888; text-align: center; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="header-title">Resumen de Aprobación Final</div>
        <div class="header-subtitle">{{ $project->name }}</div>
        <div class="header-meta">
            Aprobado por {{ $approver->name }} · {{ $approvedAt->format('d/m/Y H:i') }}
            · {{ $totalPayments }} {{ $totalPayments === 1 ? 'pago aprobado' : 'pagos aprobados' }}
        </div>
    </div>

    <div class="meta-box">
        <div class="row"><span class="label">Proyecto:</span> <span class="value">{{ $project->name }}</span></div>
        <div class="row"><span class="label">Aprobador:</span> <span class="value">{{ $approver->name }} ({{ $approver->email }})</span></div>
        <div class="row"><span class="label">Fecha de aprobación:</span> <span class="value">{{ $approvedAt->format('d/m/Y H:i') }}</span></div>
        @if ($rejectedCount > 0)
            <div class="row"><span class="label">Pagos rechazados en esta sesión:</span> <span class="value">{{ $rejectedCount }}</span></div>
        @endif
    </div>

    @if ($byDepartment->isEmpty())
        <p class="muted" style="text-align:center; padding: 20px;">No hay pagos aprobados en esta sesión.</p>
    @else
        @foreach ($byDepartment as $deptName => $dept)
            <div class="dept-section">
                <div class="dept-title">{{ $deptName }} — {{ $dept['count'] }} {{ $dept['count'] === 1 ? 'pago' : 'pagos' }}</div>
                <table class="detail">
                    <thead>
                    <tr>
                        <th>Folio</th>
                        <th>F. Solicitud</th>
                        <th>F. Provisión</th>
                        <th>Concepto</th>
                        <th>Proveedor</th>
                        <th>Solicitante</th>
                        <th class="text-right">Monto Aprobado</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($dept['items'] as $p)
                        @php
                            $concept = $p->investmentRequest?->investmentExpenseConcept;
                            $conceptLabel = $concept?->category?->name
                                ? $concept->category->name . ' - ' . $concept->name
                                : ($concept?->name ?? '—');
                            $currencyPrefix = $p->currency?->prefix ?? 'MXN';
                        @endphp
                        <tr>
                            <td class="nowrap">#{{ str_pad($p->folio_number, 5, '0', STR_PAD_LEFT) }}</td>
                            <td class="nowrap">{{ $p->created_at?->format('Y-m-d') ?? '—' }}</td>
                            <td class="nowrap">{{ $p->payment_provision_date?->format('Y-m-d') ?? '—' }}</td>
                            <td>{{ $conceptLabel }}</td>
                            <td>{{ $p->provider }}</td>
                            <td class="small">{{ $p->user?->name ?? '—' }}</td>
                            <td class="text-right nowrap">
                                <span class="small muted">{{ $currencyPrefix }}</span> ${{ number_format((float) ($p->approved_amount ?? $p->total), 2) }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <div class="dept-subtotal">
                    Subtotal:
                    @foreach ($dept['totalByCurrency'] as $prefix => $sum)
                        <span class="ccy"><span class="muted">{{ $prefix }}</span> ${{ number_format($sum, 2) }}</span>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="totals">
            <div class="totals-title">Total General</div>
            @foreach ($grandTotalByCurrency as $prefix => $sum)
                <div class="totals-row">
                    <div class="totals-label">{{ $prefix }}</div>
                    <div class="totals-value">${{ number_format($sum, 2) }}</div>
                </div>
            @endforeach
            <div class="count">{{ $totalPayments }} pagos aprobados en {{ $byDepartment->count() }} {{ $byDepartment->count() === 1 ? 'departamento' : 'departamentos' }}</div>
        </div>
    @endif

    <div class="footer">
        Linking — payment-request · Documento confidencial · Generado automáticamente al cerrar la aprobación final
    </div>
</div>
</body>
</html>
