<x-mail::message>

{{-- Section: Header --}}
<div style="border-bottom: 1px solid #D4C9A9; padding-bottom: 4px; margin-bottom: 16px;">
<span style="font-size: 14px; font-weight: 600; color: #191731; text-transform: uppercase; letter-spacing: 0.5px;">Proyecto: {{ $projectName }}</span>
</div>

{{ $greeting }}

El Project Manager terminó de revisar pagos del proyecto **{{ $projectName }}**. Tienes **{{ $batchesCount }} {{ $batchesCount === 1 ? 'lote' : 'lotes' }}** con un total de **{{ $totalPayments }} {{ $totalPayments === 1 ? 'pago' : 'pagos' }}** que requieren tu aprobación final.

{{-- Section: Resumen por departamento --}}
<div style="border-bottom: 1px solid #D4C9A9; padding-bottom: 4px; margin-top: 24px; margin-bottom: 16px;">
<span style="font-size: 14px; font-weight: 600; color: #191731; text-transform: uppercase; letter-spacing: 0.5px;">Resumen por Departamento</span>
</div>

@foreach ($departmentGroups as $group)
<div style="margin-top: 16px; margin-bottom: 8px;">
<span style="font-size: 13px; font-weight: 600; color: #191731; text-transform: uppercase;">{{ $group['department'] }}</span>
<span style="font-size: 12px; color: #6B7280;">— Semana {{ $group['week'] }} ({{ $group['year'] }}) · {{ count($group['payments']) }} {{ count($group['payments']) === 1 ? 'pago' : 'pagos' }}</span>
</div>

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 4px 0 8px 0; border-collapse: collapse;">
@foreach ($group['payments'] as $payment)
<tr>
<td style="padding: 8px 0; border-bottom: 1px solid #E5E7EB; vertical-align: top;">
<div style="font-size: 13px; font-weight: 600; color: #191731;">{{ $payment['provider'] }}</div>
<div style="font-size: 11px; color: #6B7280; margin-top: 2px;">#{{ str_pad($payment['folio'], 5, '0', STR_PAD_LEFT) }} · {{ $payment['concept'] }}</div>
@if ($payment['adjusted'])
<div style="font-size: 11px; color: #B45309; margin-top: 2px;">Ajustado por PM: solicitado $ {{ $payment['original_total'] }} → aprobado $ {{ $payment['amount'] }}</div>
@endif
</td>
<td style="padding: 8px 0; border-bottom: 1px solid #E5E7EB; text-align: right; vertical-align: top; white-space: nowrap;">
<div style="font-size: 13px; font-weight: 700; color: #191731;">$ {{ $payment['amount'] }}</div>
<div style="font-size: 11px; color: #6B7280;">{{ $payment['currency'] }}</div>
</td>
</tr>
@endforeach
</table>

<div style="margin-bottom: 8px; padding: 6px 10px; background-color: #F3F4F6; border-radius: 4px; font-size: 12px; color: #191731; text-align: right;">
<strong>Subtotal {{ $group['department'] }}: $ {{ $group['subtotal'] }}</strong>
</div>
@endforeach

{{-- Section: Total general --}}
<div style="margin-top: 16px; padding: 12px 16px; background-color: #191731; color: #FFFFFF; border-radius: 4px; font-size: 14px; text-align: right;">
<strong>Total general del proyecto: $ {{ $grandTotal }}</strong>
</div>

{{-- Section: Action --}}
<div style="border-bottom: 1px solid #D4C9A9; padding-bottom: 4px; margin-top: 24px; margin-bottom: 16px;">
<span style="font-size: 14px; font-weight: 600; color: #191731; text-transform: uppercase; letter-spacing: 0.5px;">Acción Requerida</span>
</div>

Al hacer clic en el botón verás una pantalla unificada con todos los lotes agrupados por departamento. Puedes aprobar/rechazar pagos individuales y procesarlo todo en una sola acción.

<x-mail::button :url="$actionUrl">
{{ $actionText }}
</x-mail::button>

Este enlace es válido por 96 horas.

{{ $salutation }}
</x-mail::message>
