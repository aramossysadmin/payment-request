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
