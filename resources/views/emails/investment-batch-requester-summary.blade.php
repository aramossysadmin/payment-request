<x-mail::message>

{{-- Section: Header --}}
<div style="border-bottom: 1px solid #D4C9A9; padding-bottom: 4px; margin-bottom: 16px;">
<span style="font-size: 14px; font-weight: 600; color: #191731; text-transform: uppercase; letter-spacing: 0.5px;">Resultado de tu Lote de Pagos — {{ $projectName }}</span>
</div>

{{ $greeting }}

{{ $description }}

{{-- Resumen --}}
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 16px 0;">
<tr>
<td style="padding: 6px 0; vertical-align: top; width: 24px;">
<span style="color: #C5A059; font-size: 14px;">&#8226;</span>
</td>
<td style="padding: 6px 0; font-size: 14px; color: #191731;">
<strong>Aprobados:</strong> {{ count($approvedItems) }}
</td>
</tr>
<tr>
<td style="padding: 6px 0; vertical-align: top; width: 24px;">
<span style="color: #C5A059; font-size: 14px;">&#8226;</span>
</td>
<td style="padding: 6px 0; font-size: 14px; color: #191731;">
<strong>Rechazados:</strong> {{ count($rejectedItems) }}
</td>
</tr>
</table>

{{-- Section: Aprobados --}}
@if (count($approvedItems) > 0)
<div style="border-bottom: 1px solid #D4C9A9; padding-bottom: 4px; margin-top: 24px; margin-bottom: 16px;">
<span style="font-size: 14px; font-weight: 600; color: #16A34A; text-transform: uppercase; letter-spacing: 0.5px;">&#10004; Pagos Aprobados ({{ count($approvedItems) }})</span>
</div>

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 8px 0; border-collapse: collapse;">
@foreach ($approvedItems as $item)
<tr>
<td style="padding: 10px 0; border-bottom: 1px solid #E5E7EB; vertical-align: top;">
<div style="font-size: 14px; font-weight: 600; color: #191731;">{{ $item['provider'] }}</div>
<div style="font-size: 12px; color: #6B7280; margin-top: 2px;">#{{ str_pad($item['folio'], 5, '0', STR_PAD_LEFT) }} · {{ $item['concept'] }}</div>
@if (!empty($item['description']) && $item['description'] !== '-')
<div style="font-size: 12px; color: #6B7280; margin-top: 2px;">{{ $item['description'] }}</div>
@endif
</td>
<td style="padding: 10px 0; border-bottom: 1px solid #E5E7EB; text-align: right; vertical-align: top; white-space: nowrap;">
<div style="font-size: 14px; font-weight: 700; color: #191731;">$ {{ $item['total'] }}</div>
<div style="font-size: 12px; color: #6B7280;">{{ $item['currency'] }}</div>
</td>
</tr>
@endforeach
</table>
@endif

{{-- Section: Rechazados --}}
@if (count($rejectedItems) > 0)
<div style="border-bottom: 1px solid #D4C9A9; padding-bottom: 4px; margin-top: 24px; margin-bottom: 16px;">
<span style="font-size: 14px; font-weight: 600; color: #DC2626; text-transform: uppercase; letter-spacing: 0.5px;">&#10006; Pagos Rechazados ({{ count($rejectedItems) }})</span>
</div>

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 8px 0; border-collapse: collapse;">
@foreach ($rejectedItems as $item)
<tr>
<td style="padding: 10px 0; border-bottom: 1px solid #E5E7EB; vertical-align: top;">
<div style="font-size: 14px; font-weight: 600; color: #191731;">{{ $item['provider'] }}</div>
<div style="font-size: 12px; color: #6B7280; margin-top: 2px;">#{{ str_pad($item['folio'], 5, '0', STR_PAD_LEFT) }} · {{ $item['concept'] }}</div>
@if (!empty($item['description']) && $item['description'] !== '-')
<div style="font-size: 12px; color: #6B7280; margin-top: 2px;">{{ $item['description'] }}</div>
@endif
</td>
<td style="padding: 10px 0; border-bottom: 1px solid #E5E7EB; text-align: right; vertical-align: top; white-space: nowrap;">
<div style="font-size: 14px; font-weight: 700; color: #191731;">$ {{ $item['total'] }}</div>
<div style="font-size: 12px; color: #6B7280;">{{ $item['currency'] }}</div>
</td>
</tr>
@endforeach
</table>

@if (!empty($rejectionReason))
<div style="background-color: #FEF2F2; border-left: 4px solid #DC2626; padding: 12px 16px; margin: 16px 0; border-radius: 4px;">
<div style="font-size: 12px; font-weight: 600; color: #991B1B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Motivo del rechazo</div>
<div style="font-size: 14px; color: #191731;">{{ $rejectionReason }}</div>
</div>
@endif

<p style="font-size: 13px; color: #6B7280; margin-top: 8px;">El presupuesto de los pagos rechazados fue liberado. Puedes crear nuevos pagos si lo requieres.</p>
@endif

{{-- Section: Action --}}
<div style="border-bottom: 1px solid #D4C9A9; padding-bottom: 4px; margin-top: 24px; margin-bottom: 16px;">
<span style="font-size: 14px; font-weight: 600; color: #191731; text-transform: uppercase; letter-spacing: 0.5px;">Acciones</span>
</div>

<x-mail::button :url="$actionUrl">
{{ $actionText }}
</x-mail::button>

{{ $salutation }}
</x-mail::message>
