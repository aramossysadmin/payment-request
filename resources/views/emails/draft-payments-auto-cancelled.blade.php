<x-mail::message>

<div style="border-bottom: 1px solid #D4C9A9; padding-bottom: 4px; margin-bottom: 16px;">
<span style="font-size: 14px; font-weight: 600; color: #191731; text-transform: uppercase; letter-spacing: 0.5px;">Cancelación automática de pagos</span>
</div>

Hola {{ $user->name }},

La ventana de envío de pagos a Autorización cerró el **{{ $windowClosedAt->locale('es_MX')->translatedFormat('l j \\d\\e F H:i') }}** sin que enviaras los siguientes pagos en borrador al CEO.

Como medida operativa, esos pagos se **cancelaron automáticamente** y el saldo presupuestal de cada concepto **fue liberado**.

<div style="border-bottom: 1px solid #D4C9A9; padding-bottom: 4px; margin-top: 24px; margin-bottom: 8px;">
<span style="font-size: 14px; font-weight: 600; color: #DC2626; text-transform: uppercase; letter-spacing: 0.5px;">Pagos cancelados ({{ count($cancelledPayments) }})</span>
</div>

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 8px 0 12px 0; border-collapse: collapse;">
<thead>
<tr style="background: #191731; color: #fff;">
<th align="left" style="padding: 8px 10px; font-size: 12px;">Folio</th>
<th align="left" style="padding: 8px 10px; font-size: 12px;">Concepto</th>
<th align="left" style="padding: 8px 10px; font-size: 12px;">Proveedor</th>
<th align="right" style="padding: 8px 10px; font-size: 12px;">Monto</th>
</tr>
</thead>
<tbody>
@foreach ($cancelledPayments as $p)
<tr>
<td style="padding: 8px 10px; border-bottom: 1px solid #E5E7EB; font-size: 13px; font-family: monospace;">#{{ str_pad($p->folio_number, 5, '0', STR_PAD_LEFT) }}</td>
<td style="padding: 8px 10px; border-bottom: 1px solid #E5E7EB; font-size: 13px;">{{ $p->investmentRequest?->investmentExpenseConcept?->name ?? '—' }}</td>
<td style="padding: 8px 10px; border-bottom: 1px solid #E5E7EB; font-size: 13px;">{{ $p->provider }}</td>
<td align="right" style="padding: 8px 10px; border-bottom: 1px solid #E5E7EB; font-size: 13px; font-family: monospace;"><span style="color: #6B7280; font-size: 11px;">{{ $p->currency?->prefix ?? 'MXN' }}</span> ${{ number_format((float) $p->total, 2) }}</td>
</tr>
@endforeach
<tr style="background: #FEF2F2;">
<td colspan="3" style="padding: 10px; font-size: 13px; color: #991B1B; font-weight: 700;">TOTAL LIBERADO</td>
<td align="right" style="padding: 10px; font-size: 13px; color: #991B1B; font-weight: 700; font-family: monospace;">
@foreach ($totalByCcy as $ccy => $sum)
<div>{{ $ccy }} ${{ number_format($sum, 2) }}</div>
@endforeach
</td>
</tr>
</tbody>
</table>

<div style="margin-top: 16px; padding: 10px 12px; background: #f5f5f5; border-left: 3px solid #b8860b; font-size: 13px; color: #374151;">
&#9888; Puedes <strong>recrearlos</strong> en la próxima ventana de captura. Los datos no se mantienen — necesitarás capturarlos de nuevo.
</div>

<x-mail::button :url="url('/investment-sheets/consolidated')" color="primary">
Ir al portal
</x-mail::button>

<div style="margin-top: 24px; font-size: 11px; color: #6B7280; border-top: 1px solid #E5E7EB; padding-top: 12px;">
Este correo se envía automáticamente al cierre de la ventana de envío configurada en el portal.
</div>

Linking — payment-request
</x-mail::message>
