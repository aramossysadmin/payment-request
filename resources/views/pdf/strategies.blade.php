<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estrategias — {{ $projectName }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #191731; }
        .page { padding: 20px 25px; }
        .header { border-bottom: 2px solid #191731; padding-bottom: 10px; margin-bottom: 12px; }
        .header-title { font-size: 16px; font-weight: bold; color: #191731; }
        .header-subtitle { font-size: 11px; color: #b8860b; margin-top: 2px; font-weight: bold; }
        .header-meta { font-size: 9px; color: #666; margin-top: 4px; }
        .filters { margin-bottom: 12px; padding: 8px 10px; background: #f5f5f5; border-radius: 3px; font-size: 8px; }
        .filters .label { font-weight: bold; color: #555; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th { background: #191731; color: #fff; padding: 5px 4px; text-align: left; font-size: 7px; }
        td { padding: 4px; border-bottom: 1px solid #e5e5e5; font-size: 7px; vertical-align: top; word-wrap: break-word; }
        tr:nth-child(even) td { background: #fafafa; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        .text-right { text-align: right; }
        .muted { color: #888; }
        .footer { margin-top: 20px; padding-top: 8px; border-top: 1px solid #ccc; font-size: 8px; color: #888; }
    </style>
</head>
<body>
@php
    $money = fn (?string $amount) => $amount === null ? '—' : number_format((float) $amount, 2);
    $date = fn (?string $value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
@endphp
<div class="page">
    <div class="header">
        <div class="header-title">Estrategias Solicitadas</div>
        <div class="header-subtitle">{{ $projectName }}</div>
        <div class="header-meta">
            Generado el {{ $generatedAt->format('d/m/Y H:i') }} por {{ $generatedBy }}
            · {{ count($rows) }} {{ count($rows) === 1 ? 'estrategia' : 'estrategias' }}
            · Montos en su moneda original
        </div>
    </div>

    <div class="filters">
        <span class="label">Departamento:</span> {{ $department ?? 'Todos' }}
        &nbsp;·&nbsp;
        <span class="label">Estado:</span> {{ $status ?? 'Todos' }}
        &nbsp;·&nbsp;
        <span class="label">Semana captura:</span> {{ $captureWeek ?? 'Todas' }}
        &nbsp;·&nbsp;
        <span class="label">Semana programación:</span> {{ $provisionWeek ?? 'Todas' }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 4%">Folio</th>
                <th style="width: 8%">Hoja de Inversión</th>
                <th style="width: 9%">Departamento / Solicitante</th>
                <th style="width: 11%">Concepto / Proveedor</th>
                <th style="width: 7%">Código y tasa</th>
                <th style="width: 7%" class="text-right">Monto base</th>
                <th style="width: 5%" class="text-right">Recargo</th>
                <th style="width: 7%" class="text-right">Total</th>
                <th style="width: 7%" class="text-right">Aprobado</th>
                <th style="width: 4%">Moneda</th>
                <th style="width: 6%">Tipo</th>
                <th style="width: 7%">Estado</th>
                <th style="width: 6%">Fecha captura</th>
                <th style="width: 3%">Sem. capt.</th>
                <th style="width: 6%">Fecha prog.</th>
                <th style="width: 3%">Sem. prog.</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>#{{ str_pad((string) $row['folio_number'], 5, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $row['project_name'] ?? '—' }}</td>
                    <td>{{ $row['department_name'] ?? '—' }}<br><span class="muted">{{ $row['requester_name'] ?? '—' }}</span></td>
                    <td>{{ $row['concept_name'] ?? '—' }}<br><span class="muted">{{ $row['provider'] ?? '—' }}</span></td>
                    <td>
                        @if ($row['strategy_rate'] === null)
                            <span class="muted">Anterior al catálogo</span>
                        @else
                            {{ $row['strategy_name'] ?? '—' }}<br><span class="muted">{{ $row['strategy_rate'] }}%</span>
                        @endif
                    </td>
                    <td class="text-right">{{ $money($row['strategy_amount']) }}</td>
                    <td class="text-right">{{ $money($row['strategy_surcharge']) }}</td>
                    <td class="text-right">{{ $money($row['total']) }}</td>
                    <td class="text-right">{{ $money($row['approved_amount']) }}</td>
                    <td>{{ $row['currency_prefix'] }}</td>
                    <td>
                        @if ($row['payment_type'] === 'estrategia')
                            Estrategia
                        @else
                            Tipo actual: {{ \App\Enums\InvestmentPaymentType::labelFor($row['payment_type']) }}
                        @endif
                    </td>
                    <td>{{ $statusLabels[$row['status']] ?? $row['status'] }}</td>
                    <td>{{ $date($row['captured_at']) }}</td>
                    <td>{{ $row['capture_week'] ?? '—' }}</td>
                    <td>{{ $date($row['payment_provision_date']) }}</td>
                    <td>{{ $row['provision_week'] ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="16" class="muted">No hay estrategias con estos filtros.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Linking — payment-request · Documento confidencial</div>
</div>
</body>
</html>
