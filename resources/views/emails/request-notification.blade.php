<x-mail::message>

@if (!empty($banner))
<div style="background-color: #F59E0B; color: #FFFFFF; text-align: center; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 16px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;">
{{ $banner }}
</div>
@endif

{{-- Section: Request Details --}}
<div style="border-bottom: 1px solid #D4C9A9; padding-bottom: 4px; margin-bottom: 16px;">
<span style="font-size: 14px; font-weight: 600; color: #191731; text-transform: uppercase; letter-spacing: 0.5px;">{{ $sectionTitle ?? 'Detalles de la Solicitud' }}</span>
</div>

{{ $greeting }}

{{ $description }}

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 16px 0;">
@foreach ($details as $detail)
<tr>
<td style="padding: 6px 0; vertical-align: top; width: 24px;">
<span style="color: #C5A059; font-size: 14px;">&#8226;</span>
</td>
<td style="padding: 6px 0; font-size: 14px; color: #191731;">
<strong>{{ $detail['label'] }}:</strong> {{ $detail['value'] }}
</td>
</tr>
@endforeach
@if (!empty($stageInfo))
<tr>
<td style="padding: 6px 0; vertical-align: top; width: 24px;">
<span style="color: #C5A059; font-size: 14px;">&#8226;</span>
</td>
<td style="padding: 6px 0; font-size: 14px; color: #191731;">
<strong>Departamento:</strong> {{ $stageInfo['department'] }}
</td>
</tr>
@if (!empty($stageInfo['stage']))
<tr>
<td style="padding: 6px 0; vertical-align: top; width: 24px;">
<span style="color: #C5A059; font-size: 14px;">&#8226;</span>
</td>
<td style="padding: 6px 0; font-size: 14px; color: #191731;">
<strong>Etapa actual:</strong> {{ $stageInfo['stage'] }}
</td>
</tr>
@endif
@endif
</table>

{{-- Section: Payment Items Table --}}
@if (!empty($paymentItems))
<div style="border-bottom: 1px solid #D4C9A9; padding-bottom: 4px; margin-top: 24px; margin-bottom: 16px;">
<span style="font-size: 14px; font-weight: 600; color: #191731; text-transform: uppercase; letter-spacing: 0.5px;">Detalle de Pagos</span>
</div>

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 8px 0; border-collapse: collapse;">
<thead>
<tr>
<th style="padding: 8px 6px; font-size: 12px; font-weight: 600; color: #191731; text-align: left; border-bottom: 2px solid #D4C9A9; text-transform: uppercase; letter-spacing: 0.5px;">Folio</th>
<th style="padding: 8px 6px; font-size: 12px; font-weight: 600; color: #191731; text-align: left; border-bottom: 2px solid #D4C9A9; text-transform: uppercase; letter-spacing: 0.5px;">Proveedor</th>
<th style="padding: 8px 6px; font-size: 12px; font-weight: 600; color: #191731; text-align: left; border-bottom: 2px solid #D4C9A9; text-transform: uppercase; letter-spacing: 0.5px;">Concepto</th>
<th style="padding: 8px 6px; font-size: 12px; font-weight: 600; color: #191731; text-align: right; border-bottom: 2px solid #D4C9A9; text-transform: uppercase; letter-spacing: 0.5px;">Monto</th>
<th style="padding: 8px 6px; font-size: 12px; font-weight: 600; color: #191731; text-align: left; border-bottom: 2px solid #D4C9A9; text-transform: uppercase; letter-spacing: 0.5px;">Descripción</th>
<th style="padding: 8px 6px; font-size: 12px; font-weight: 600; color: #191731; text-align: center; border-bottom: 2px solid #D4C9A9; text-transform: uppercase; letter-spacing: 0.5px;">Estado</th>
</tr>
</thead>
<tbody>
@foreach ($paymentItems as $item)
<tr>
<td style="padding: 6px; font-size: 13px; color: #191731; border-bottom: 1px solid #E5E7EB;">#{{ str_pad($item['folio'], 5, '0', STR_PAD_LEFT) }}</td>
<td style="padding: 6px; font-size: 13px; color: #191731; border-bottom: 1px solid #E5E7EB;">{{ $item['provider'] }}</td>
<td style="padding: 6px; font-size: 13px; color: #191731; border-bottom: 1px solid #E5E7EB;">{{ $item['concept'] }}</td>
<td style="padding: 6px; font-size: 13px; color: #191731; border-bottom: 1px solid #E5E7EB; text-align: right; font-weight: 600;">$ {{ $item['total'] }} {{ $item['currency'] }}</td>
<td style="padding: 6px; font-size: 13px; color: #191731; border-bottom: 1px solid #E5E7EB;">{{ $item['description'] ?? '-' }}</td>
<td style="padding: 6px; font-size: 13px; border-bottom: 1px solid #E5E7EB; text-align: center;">
@if ($item['included'])
<span style="color: #16A34A; font-weight: 600;">&#10003; Incluido</span>
@else
<span style="color: #DC2626; font-weight: 600;">&#10007; Excluido</span>
@if (!empty($item['exclusion_reason']))
<br><span style="font-size: 11px; color: #6B7280;">{{ $item['exclusion_reason'] }}</span>
@endif
@endif
</td>
</tr>
@endforeach
</tbody>
</table>
@endif

{{-- Section: Attached Documents --}}
@if (!empty($documents))
<div style="border-bottom: 1px solid #D4C9A9; padding-bottom: 4px; margin-top: 24px; margin-bottom: 16px;">
<span style="font-size: 14px; font-weight: 600; color: #191731; text-transform: uppercase; letter-spacing: 0.5px;">Documentos Adjuntos</span>
</div>

<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
@foreach ($documents as $doc)
<tr>
<td style="padding: 4px 0; vertical-align: top; width: 24px;">
<span style="color: #C5A059; font-size: 14px;">&#128206;</span>
</td>
<td style="padding: 4px 0; font-size: 14px;">
<a href="{{ $doc['url'] }}" style="color: #C5A059; text-decoration: underline;">{{ $doc['name'] }}</a>
</td>
</tr>
@endforeach
</table>
@endif

{{-- Section: Actions --}}
<div style="border-bottom: 1px solid #D4C9A9; padding-bottom: 4px; margin-top: 24px; margin-bottom: 16px;">
<span style="font-size: 14px; font-weight: 600; color: #191731; text-transform: uppercase; letter-spacing: 0.5px;">Acciones</span>
</div>

<x-mail::button :url="$actionUrl">
{{ $actionText }}
</x-mail::button>

@if (!empty($footerLines))
@foreach ($footerLines as $line)
{!! $line !!}

@endforeach
@endif

{{ $salutation }}
</x-mail::message>
