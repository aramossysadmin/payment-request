<x-mail::message>

<div style="border-bottom: 1px solid #D4C9A9; padding-bottom: 4px; margin-bottom: 16px;">
<span style="font-size: 14px; font-weight: 600; color: #DC2626; text-transform: uppercase; letter-spacing: 0.5px;">⏰ Recordatorio — Cierre en 2 horas</span>
</div>

Hola {{ $user->name }},

Tienes **{{ count($pendingDrafts) }} {{ count($pendingDrafts) === 1 ? 'pago en borrador' : 'pagos en borrador' }}** sin enviar al CEO. La ventana de envío cierra hoy a las **{{ $closesAt->locale('es_MX')->translatedFormat('H:i') }}** (en aproximadamente 2 horas).

Si no se envían antes del cierre, **se cancelarán automáticamente** y tendrás que volver a capturarlos en la próxima ventana.

<div style="border-bottom: 1px solid #D4C9A9; padding-bottom: 4px; margin-top: 24px; margin-bottom: 8px;">
<span style="font-size: 14px; font-weight: 600; color: #b8860b; text-transform: uppercase; letter-spacing: 0.5px;">Tus drafts pendientes ({{ count($pendingDrafts) }})</span>
</div>

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 8px 0 12px 0; border-collapse: collapse;">
<thead>
<tr style="background: #191731; color: #fff;">
<th align="left" style="padding: 8px 10px; font-size: 12px;">Folio</th>
<th align="left" style="padding: 8px 10px; font-size: 12px;">Proveedor</th>
<th align="right" style="padding: 8px 10px; font-size: 12px;">Monto</th>
</tr>
</thead>
<tbody>
@foreach ($pendingDrafts as $p)
<tr>
<td style="padding: 8px 10px; border-bottom: 1px solid #E5E7EB; font-size: 13px; font-family: monospace;">#{{ str_pad($p->folio_number, 5, '0', STR_PAD_LEFT) }}</td>
<td style="padding: 8px 10px; border-bottom: 1px solid #E5E7EB; font-size: 13px;">{{ $p->provider }}</td>
<td align="right" style="padding: 8px 10px; border-bottom: 1px solid #E5E7EB; font-size: 13px; font-family: monospace;"><span style="color: #6B7280; font-size: 11px;">{{ $p->currency?->prefix ?? 'MXN' }}</span> ${{ number_format((float) $p->total, 2) }}</td>
</tr>
@endforeach
<tr style="background: #FEF3C7;">
<td colspan="2" style="padding: 10px; font-size: 13px; color: #92400E; font-weight: 700;">TOTAL EN RIESGO</td>
<td align="right" style="padding: 10px; font-size: 13px; color: #92400E; font-weight: 700; font-family: monospace;">
@foreach ($totalByCcy as $ccy => $sum)
<div>{{ $ccy }} ${{ number_format($sum, 2) }}</div>
@endforeach
</td>
</tr>
</tbody>
</table>

<x-mail::button :url="url('/investment-sheets/consolidated')" color="error">
Enviar ahora
</x-mail::button>

<div style="margin-top: 24px; font-size: 11px; color: #6B7280; border-top: 1px solid #E5E7EB; padding-top: 12px;">
Este recordatorio se envía automáticamente 2 horas antes del cierre cuando tienes drafts sin enviar.
</div>

Linking — payment-request
</x-mail::message>
