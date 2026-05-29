<x-mail::message>

{{-- Section: Header --}}
<div style="border-bottom: 1px solid #D4C9A9; padding-bottom: 4px; margin-bottom: 16px;">
<span style="font-size: 14px; font-weight: 600; color: #191731; text-transform: uppercase; letter-spacing: 0.5px;">Proyecto: {{ $projectName }}</span>
</div>

{{ $greeting }}

{{ $description }}

{{-- Section: PAGOS NUEVOS --}}
@if (count($newGroups) > 0)
<div style="border-bottom: 1px solid #D4C9A9; padding-bottom: 4px; margin-top: 24px; margin-bottom: 16px;">
<span style="font-size: 14px; font-weight: 600; color: #16A34A; text-transform: uppercase; letter-spacing: 0.5px;">&#9733; Pagos Nuevos ({{ $newCount }}) — Aprobados ahora por el CEO</span>
</div>

@foreach ($newGroups as $group)
<div style="margin-top: 12px; margin-bottom: 8px;">
<span style="font-size: 13px; font-weight: 600; color: #191731; text-transform: uppercase;">{{ $group['department'] }}</span>
<span style="font-size: 12px; color: #6B7280;">— {{ count($group['payments']) }} {{ count($group['payments']) === 1 ? 'pago' : 'pagos' }}</span>
</div>

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 4px 0 12px 0; border-collapse: collapse;">
@foreach ($group['payments'] as $item)
<tr>
<td style="padding: 10px 0; border-bottom: 1px solid #E5E7EB; vertical-align: top;">
<div style="font-size: 14px; font-weight: 600; color: #191731;">{{ $item['provider'] }}</div>
<div style="font-size: 12px; color: #6B7280; margin-top: 2px;">#{{ str_pad($item['folio'], 5, '0', STR_PAD_LEFT) }} · {{ $item['concept'] }}</div>
</td>
<td style="padding: 10px 0; border-bottom: 1px solid #E5E7EB; text-align: right; vertical-align: top; white-space: nowrap;">
<div style="font-size: 14px; font-weight: 700; color: #191731;">$ {{ $item['total'] }}</div>
<div style="font-size: 12px; color: #6B7280;">{{ $item['currency'] }}</div>
</td>
</tr>
@endforeach
</table>
@endforeach

<div style="margin-top: 8px; padding: 8px 12px; background-color: #F0FDF4; border-left: 4px solid #16A34A; font-size: 13px; color: #191731;">
<strong>Subtotal nuevos:</strong> $ {{ $newTotal }}
</div>
@endif

{{-- Section: PAGOS HISTÓRICOS PENDIENTES --}}
@if (count($historicalGroups) > 0)
<div style="border-bottom: 1px solid #D4C9A9; padding-bottom: 4px; margin-top: 24px; margin-bottom: 16px;">
<span style="font-size: 14px; font-weight: 600; color: #B45309; text-transform: uppercase; letter-spacing: 0.5px;">&#9203; Pagos Pendientes Anteriores ({{ $historicalCount }}) — {{ $projectName }}</span>
</div>

<p style="font-size: 13px; color: #6B7280; margin-bottom: 8px;">Estos pagos del mismo proyecto llegaron en revisiones anteriores y aún no los has procesado.</p>

@foreach ($historicalGroups as $group)
<div style="margin-top: 12px; margin-bottom: 8px;">
<span style="font-size: 13px; font-weight: 600; color: #191731; text-transform: uppercase;">{{ $group['department'] }}</span>
<span style="font-size: 12px; color: #6B7280;">— {{ count($group['payments']) }} {{ count($group['payments']) === 1 ? 'pago' : 'pagos' }}</span>
</div>

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 4px 0 12px 0; border-collapse: collapse;">
@foreach ($group['payments'] as $item)
<tr>
<td style="padding: 10px 0; border-bottom: 1px solid #E5E7EB; vertical-align: top;">
<div style="font-size: 14px; font-weight: 600; color: #191731;">{{ $item['provider'] }}</div>
<div style="font-size: 12px; color: #6B7280; margin-top: 2px;">#{{ str_pad($item['folio'], 5, '0', STR_PAD_LEFT) }} · {{ $item['concept'] }}</div>
</td>
<td style="padding: 10px 0; border-bottom: 1px solid #E5E7EB; text-align: right; vertical-align: top; white-space: nowrap;">
<div style="font-size: 14px; font-weight: 700; color: #191731;">$ {{ $item['total'] }}</div>
<div style="font-size: 12px; color: #6B7280;">{{ $item['currency'] }}</div>
</td>
</tr>
@endforeach
</table>
@endforeach

<div style="margin-top: 8px; padding: 8px 12px; background-color: #FEF3C7; border-left: 4px solid #B45309; font-size: 13px; color: #191731;">
<strong>Subtotal pendientes anteriores:</strong> $ {{ $historicalTotal }}
</div>
@endif

{{-- Section: TOTAL GENERAL --}}
<div style="margin-top: 16px; padding: 12px 16px; background-color: #F3F4F6; border-radius: 4px; font-size: 14px; color: #191731; text-align: right;">
<strong>TOTAL EN COLA: $ {{ $grandTotal }}</strong>
</div>

{{-- Section: Actions --}}
<div style="border-bottom: 1px solid #D4C9A9; padding-bottom: 4px; margin-top: 24px; margin-bottom: 16px;">
<span style="font-size: 14px; font-weight: 600; color: #191731; text-transform: uppercase; letter-spacing: 0.5px;">Acciones</span>
</div>

<x-mail::button :url="$actionUrl">
{{ $actionText }}
</x-mail::button>

{{ $salutation }}
</x-mail::message>
