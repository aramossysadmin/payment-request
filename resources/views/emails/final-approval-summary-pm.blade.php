<x-mail::message>

{{-- Header --}}
<div style="border-bottom: 1px solid #D4C9A9; padding-bottom: 4px; margin-bottom: 16px;">
<span style="font-size: 14px; font-weight: 600; color: #191731; text-transform: uppercase; letter-spacing: 0.5px;">Aprobación Final — {{ $project->name }}</span>
</div>

Hola {{ $pm->name }},

**{{ $approver->name }}** acaba de aplicar la **aprobación final** de pagos para el proyecto **{{ $project->name }}** el {{ $approvedAt->format('d/m/Y H:i') }}.

A continuación el resumen consolidado por departamento. El **detalle completo de cada pago** está en el PDF adjunto.

{{-- Resumen por departamento --}}
<div style="border-bottom: 1px solid #D4C9A9; padding-bottom: 4px; margin-top: 24px; margin-bottom: 8px;">
<span style="font-size: 14px; font-weight: 600; color: #16A34A; text-transform: uppercase; letter-spacing: 0.5px;">&#9733; Resumen por departamento ({{ $totalPayments }} {{ $totalPayments === 1 ? 'pago aprobado' : 'pagos aprobados' }})</span>
</div>

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 8px 0 12px 0; border-collapse: collapse;">
<thead>
<tr style="background: #191731; color: #fff;">
<th align="left" style="padding: 8px 10px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Departamento</th>
<th align="right" style="padding: 8px 10px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;"># Pagos</th>
<th align="right" style="padding: 8px 10px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Total</th>
</tr>
</thead>
<tbody>
@foreach ($byDepartment as $deptName => $dept)
<tr>
<td style="padding: 8px 10px; border-bottom: 1px solid #E5E7EB; font-size: 13px; color: #191731; font-weight: 600;">{{ $deptName }}</td>
<td align="right" style="padding: 8px 10px; border-bottom: 1px solid #E5E7EB; font-size: 13px; color: #191731;">{{ $dept['count'] }}</td>
<td align="right" style="padding: 8px 10px; border-bottom: 1px solid #E5E7EB; font-size: 13px; color: #191731; font-family: 'Courier New', monospace;">
@foreach ($dept['totalByCurrency'] as $prefix => $sum)
<div><span style="color: #6B7280; font-size: 11px;">{{ $prefix }}</span> ${{ number_format($sum, 2) }}</div>
@endforeach
</td>
</tr>
@endforeach
<tr style="background: #f0e6c8;">
<td style="padding: 10px; font-size: 14px; color: #191731; font-weight: 700; text-transform: uppercase;">Total general</td>
<td align="right" style="padding: 10px; font-size: 14px; color: #191731; font-weight: 700;">{{ $totalPayments }}</td>
<td align="right" style="padding: 10px; font-size: 14px; color: #191731; font-weight: 700; font-family: 'Courier New', monospace;">
@foreach ($grandTotalByCurrency as $prefix => $sum)
<div><span style="color: #6B7280; font-size: 11px;">{{ $prefix }}</span> ${{ number_format($sum, 2) }}</div>
@endforeach
</td>
</tr>
</tbody>
</table>

<div style="margin-top: 16px; padding: 10px 12px; background: #f5f5f5; border-left: 3px solid #b8860b; font-size: 13px; color: #374151;">
&#128206; <strong>PDF adjunto</strong> con el detalle de cada pago aprobado (folio, fecha, concepto, proveedor, solicitante y monto), agrupado por departamento.
</div>

@if ($rejectedCount > 0)
<div style="margin-top: 12px; padding: 8px 12px; background: #fff7ed; border-left: 3px solid #f97316; font-size: 12px; color: #7c2d12;">
Esta sesión incluyó además <strong>{{ $rejectedCount }} {{ $rejectedCount === 1 ? 'pago rechazado' : 'pagos rechazados' }}</strong>. Los solicitantes ya fueron notificados.
</div>
@endif

<x-mail::button :url="$portalUrl" color="primary">
Revisar en el portal
</x-mail::button>

<div style="margin-top: 24px; font-size: 11px; color: #6B7280; border-top: 1px solid #E5E7EB; padding-top: 12px;">
Este correo se envía automáticamente al cerrar la aprobación final. Recibes esta notificación porque tienes el rol de <strong>Project Manager</strong>.
</div>

Linking — payment-request
</x-mail::message>
