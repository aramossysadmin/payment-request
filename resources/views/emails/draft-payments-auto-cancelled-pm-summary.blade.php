<x-mail::message>

<div style="border-bottom: 1px solid #D4C9A9; padding-bottom: 4px; margin-bottom: 16px;">
<span style="font-size: 14px; font-weight: 600; color: #191731; text-transform: uppercase; letter-spacing: 0.5px;">Resumen — Cancelaciones automáticas</span>
</div>

Hola {{ $pm->name }},

La ventana de envío de pagos a Autorización cerró el **{{ $windowClosedAt->locale('es_MX')->translatedFormat('l j \\d\\e F H:i') }}**. Los siguientes drafts no fueron enviados a tiempo al CEO y se **cancelaron automáticamente** liberando su saldo presupuestal.

<div style="border-bottom: 1px solid #D4C9A9; padding-bottom: 4px; margin-top: 24px; margin-bottom: 8px;">
<span style="font-size: 14px; font-weight: 600; color: #DC2626; text-transform: uppercase; letter-spacing: 0.5px;">Resumen consolidado ({{ $totalCancelled }} pagos en {{ $byDepartment->count() }} {{ $byDepartment->count() === 1 ? 'departamento' : 'departamentos' }})</span>
</div>

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 8px 0 12px 0; border-collapse: collapse;">
<thead>
<tr style="background: #191731; color: #fff;">
<th align="left" style="padding: 8px 10px; font-size: 12px;">Departamento</th>
<th align="right" style="padding: 8px 10px; font-size: 12px;"># Pagos</th>
<th align="right" style="padding: 8px 10px; font-size: 12px;">Total Liberado</th>
</tr>
</thead>
<tbody>
@foreach ($byDepartment as $deptName => $dept)
<tr>
<td style="padding: 8px 10px; border-bottom: 1px solid #E5E7EB; font-size: 13px; font-weight: 600;">{{ $deptName }}</td>
<td align="right" style="padding: 8px 10px; border-bottom: 1px solid #E5E7EB; font-size: 13px;">{{ $dept['count'] }}</td>
<td align="right" style="padding: 8px 10px; border-bottom: 1px solid #E5E7EB; font-size: 13px; font-family: monospace;">
@foreach ($dept['totalByCurrency'] as $ccy => $sum)
<div><span style="color: #6B7280; font-size: 11px;">{{ $ccy }}</span> ${{ number_format($sum, 2) }}</div>
@endforeach
</td>
</tr>
@endforeach
<tr style="background: #FEF2F2;">
<td style="padding: 10px; font-size: 14px; color: #991B1B; font-weight: 700;">TOTAL GENERAL</td>
<td align="right" style="padding: 10px; font-size: 14px; color: #991B1B; font-weight: 700;">{{ $totalCancelled }}</td>
<td align="right" style="padding: 10px; font-size: 14px; color: #991B1B; font-weight: 700; font-family: monospace;">
@foreach ($grandTotalByCurrency as $ccy => $sum)
<div>{{ $ccy }} ${{ number_format($sum, 2) }}</div>
@endforeach
</td>
</tr>
</tbody>
</table>

{{-- Detalle por departamento --}}
@foreach ($byDepartment as $deptName => $dept)
<div style="margin-top: 16px; margin-bottom: 4px;">
<span style="font-size: 13px; font-weight: 600; color: #b8860b; text-transform: uppercase;">{{ $deptName }} — {{ $dept['count'] }} {{ $dept['count'] === 1 ? 'pago' : 'pagos' }}</span>
</div>
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 4px 0 12px 0; border-collapse: collapse;">
@foreach ($dept['items'] as $p)
<tr>
<td style="padding: 6px 0; border-bottom: 1px solid #E5E7EB; font-size: 12px;">
<span style="font-family: monospace;">#{{ str_pad($p->folio_number, 5, '0', STR_PAD_LEFT) }}</span>
· {{ $p->user?->name ?? '—' }}
· {{ $p->provider }}
</td>
<td align="right" style="padding: 6px 0; border-bottom: 1px solid #E5E7EB; font-size: 12px; font-family: monospace;">
<span style="color: #6B7280; font-size: 11px;">{{ $p->currency?->prefix ?? 'MXN' }}</span> ${{ number_format((float) $p->total, 2) }}
</td>
</tr>
@endforeach
</table>
@endforeach

<x-mail::button :url="url('/investment-sheets/consolidated')" color="primary">
Ir al portal
</x-mail::button>

<div style="margin-top: 24px; font-size: 11px; color: #6B7280; border-top: 1px solid #E5E7EB; padding-top: 12px;">
Recibes esta notificación porque tienes el rol de <strong>Project Manager</strong>. Los solicitantes individuales ya fueron notificados por separado.
</div>

Linking — payment-request
</x-mail::message>
