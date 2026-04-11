<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }} #{{ $request->folio_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #191731; line-height: 1.5; }
        .page { padding: 30px 40px; }

        /* Header */
        .header { display: table; width: 100%; margin-bottom: 25px; border-bottom: 3px solid #191731; padding-bottom: 15px; }
        .header-left { display: table-cell; vertical-align: middle; width: 50%; }
        .header-right { display: table-cell; vertical-align: middle; width: 50%; text-align: right; }
        .logo-text { font-size: 24px; font-weight: bold; color: #191731; letter-spacing: 2px; }
        .doc-title { font-size: 16px; font-weight: bold; color: #191731; }
        .doc-folio { font-size: 22px; font-weight: bold; color: #b8860b; margin-top: 2px; }
        .doc-date { font-size: 10px; color: #666; margin-top: 2px; }
        .doc-uuid { font-size: 8px; color: #999; margin-top: 2px; font-family: monospace; }

        /* Sections */
        .section { margin-bottom: 18px; }
        .section-title { font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1.5px; color: #b8860b; border-bottom: 1px solid #e5e5e5; padding-bottom: 5px; margin-bottom: 10px; }

        /* Grid */
        .row { display: table; width: 100%; margin-bottom: 6px; }
        .col-half { display: table-cell; width: 50%; vertical-align: top; padding-right: 15px; }
        .col-full { width: 100%; margin-bottom: 6px; }

        /* Fields */
        .field-label { font-size: 9px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
        .field-value { font-size: 11px; font-weight: bold; color: #191731; margin-top: 1px; }

        /* Amounts */
        .amounts-table { width: 100%; border-collapse: collapse; }
        .amounts-table td { padding: 5px 0; }
        .amounts-table .label { text-align: left; color: #666; }
        .amounts-table .value { text-align: right; font-weight: bold; }
        .amounts-table .total-row td { border-top: 2px solid #191731; padding-top: 8px; font-size: 14px; }
        .amounts-table .total-value { color: #b8860b; font-size: 14px; }

        /* Approvals */
        .approval-item { display: table; width: 100%; margin-bottom: 8px; padding: 8px 10px; background: #f9f9f9; border-left: 3px solid #28a745; }
        .approval-item.rejected { border-left-color: #dc3545; }
        .approval-item.pending { border-left-color: #ffc107; }
        .approval-left { display: table-cell; vertical-align: middle; width: 70%; }
        .approval-right { display: table-cell; vertical-align: middle; width: 30%; text-align: right; }
        .approval-stage { font-weight: bold; font-size: 11px; }
        .approval-user { font-size: 10px; color: #555; }
        .approval-status { font-size: 10px; font-weight: bold; }
        .approval-status.approved { color: #28a745; }
        .approval-status.rejected { color: #dc3545; }
        .approval-status.pending { color: #ffc107; }
        .approval-date { font-size: 9px; color: #888; }
        .approval-comments { font-size: 9px; color: #666; font-style: italic; margin-top: 2px; }

        /* Documents */
        .doc-item { padding: 4px 0; font-size: 10px; color: #333; }
        .doc-item:before { content: "📎 "; }

        /* Footer */
        .footer { margin-top: 25px; padding-top: 10px; border-top: 1px solid #e5e5e5; text-align: center; font-size: 8px; color: #999; }

        /* Status badge */
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 10px; font-weight: bold; color: white; }
        .status-completed { background: #28a745; }
    </style>
</head>
<body>
    <div class="page">
        {{-- Header --}}
        <div class="header">
            <div class="header-left">
                <div class="logo-text">COSTEÑO</div>
            </div>
            <div class="header-right">
                <div class="doc-title">{{ $title }}</div>
                <div class="doc-folio">Folio #{{ str_pad($request->folio_number, 5, '0', STR_PAD_LEFT) }}</div>
                <div class="doc-date">{{ $request->created_at->translatedFormat('d \d\e F \d\e Y') }}</div>
                <div class="doc-uuid">{{ $request->uuid }}</div>
            </div>
        </div>

        {{-- Estado --}}
        <div class="section">
            <div style="text-align: right; margin-bottom: 10px;">
                <span class="status-badge status-completed">{{ $request->status->label() }}</span>
            </div>
        </div>

        {{-- Información General --}}
        <div class="section">
            <div class="section-title">Información General</div>
            <div class="row">
                <div class="col-half">
                    <div class="field-label">Solicitante</div>
                    <div class="field-value">{{ $request->user->name ?? '-' }}</div>
                </div>
                <div class="col-half">
                    <div class="field-label">Departamento</div>
                    <div class="field-value">{{ $request->department->name ?? '-' }}</div>
                </div>
            </div>
            <div class="row">
                <div class="col-half">
                    <div class="field-label">Fecha de Creación</div>
                    <div class="field-value">{{ $request->created_at->translatedFormat('d/m/Y H:i') }}</div>
                </div>
                <div class="col-half">
                    <div class="field-label">Moneda</div>
                    <div class="field-value">{{ $request->currency->prefix ?? '-' }}</div>
                </div>
            </div>
        </div>

        {{-- Detalles de la Solicitud --}}
        <div class="section">
            <div class="section-title">Detalles de la Solicitud</div>
            <div class="row">
                <div class="col-half">
                    <div class="field-label">Sucursal</div>
                    <div class="field-value">{{ $request->branch->name ?? '-' }}</div>
                </div>
                <div class="col-half">
                    <div class="field-label">Concepto de Gasto</div>
                    <div class="field-value">{{ $request->expenseConcept->name ?? '-' }}</div>
                </div>
            </div>
            <div class="row">
                <div class="col-half">
                    <div class="field-label">Tipo de Pago</div>
                    <div class="field-value">{{ $request->paymentType->name ?? '-' }}</div>
                </div>
                <div class="col-half">
                    &nbsp;
                </div>
            </div>
            @if($request->description)
                <div class="col-full">
                    <div class="field-label">Descripción</div>
                    <div class="field-value" style="font-weight: normal;">{{ $request->description }}</div>
                </div>
            @endif
        </div>

        {{-- Datos del Proveedor --}}
        <div class="section">
            <div class="section-title">Datos del Proveedor</div>
            <div class="row">
                <div class="col-half">
                    <div class="field-label">Razón Social</div>
                    <div class="field-value">{{ $request->provider }}</div>
                </div>
                <div class="col-half">
                    <div class="field-label">RFC</div>
                    <div class="field-value">{{ $request->rfc ?? '-' }}</div>
                </div>
            </div>
            <div class="row">
                <div class="col-half">
                    <div class="field-label">Folio Factura</div>
                    <div class="field-value">{{ $request->invoice_folio }}</div>
                </div>
                <div class="col-half">
                    &nbsp;
                </div>
            </div>
        </div>

        {{-- Montos --}}
        @php $currencyCode = $request->currency->prefix ?? 'MXN'; @endphp
        <div class="section">
            <div class="section-title">Montos</div>
            <table class="amounts-table">
                <tr>
                    <td class="label">Subtotal</td>
                    <td class="value">$ {{ number_format($request->subtotal, 2) }} {{ $currencyCode }}</td>
                </tr>
                <tr>
                    <td class="label">IVA ({{ $request->iva_rate->label() }})</td>
                    <td class="value">$ {{ number_format($request->iva, 2) }} {{ $currencyCode }}</td>
                </tr>
                @if($request->retention)
                    <tr>
                        <td class="label">Retención</td>
                        <td class="value">Sí</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td class="label" style="font-weight: bold; font-size: 14px;">Total</td>
                    <td class="value total-value">$ {{ number_format($request->total, 2) }} {{ $currencyCode }}</td>
                </tr>
            </table>
        </div>

        {{-- Folios SAP --}}
        @if($request->number_purchase_invoices || $request->number_vendor_payments)
            <div class="section">
                <div class="section-title">Folios SAP</div>
                <div class="row">
                    <div class="col-half">
                        <div class="field-label">Factura Proveedores</div>
                        <div class="field-value">{{ $request->number_purchase_invoices ?? '-' }}</div>
                    </div>
                    <div class="col-half">
                        <div class="field-label">Pago Efectuado</div>
                        <div class="field-value">{{ $request->number_vendor_payments ?? '-' }}</div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Documentos Adjuntos --}}
        @if($request->advance_documents && count(array_filter($request->advance_documents, fn($doc) => is_string($doc) && $doc !== '')) > 0)
            <div class="section">
                <div class="section-title">Documentos Adjuntos</div>
                @foreach(array_filter($request->advance_documents, fn($doc) => is_string($doc) && $doc !== '') as $doc)
                    <div class="doc-item">{{ basename($doc) }}</div>
                @endforeach
            </div>
        @endif

        {{-- Historial de Aprobaciones --}}
        <div class="section">
            <div class="section-title">Historial de Aprobaciones</div>
            @foreach($request->approvals->sortBy('created_at') as $approval)
                @php
                    $stageLabel = match($approval->stage) {
                        'department' => 'Departamento',
                        'administration' => 'Administración',
                        'treasury' => 'Tesorería',
                        default => $approval->stage,
                    };
                    $statusLabel = match($approval->status) {
                        'approved' => 'Aprobada',
                        'rejected' => 'Rechazada',
                        'pending' => 'Pendiente',
                        default => $approval->status,
                    };
                @endphp
                <div class="approval-item {{ $approval->status }}">
                    <div class="approval-left">
                        <div class="approval-stage">{{ $stageLabel }} - Nivel {{ $approval->level }}</div>
                        <div class="approval-user">{{ $approval->user->name ?? '-' }}</div>
                        @if($approval->comments)
                            <div class="approval-comments">"{{ $approval->comments }}"</div>
                        @endif
                    </div>
                    <div class="approval-right">
                        <div class="approval-status {{ $approval->status }}">{{ $statusLabel }}</div>
                        @if($approval->responded_at)
                            <div class="approval-date">{{ $approval->responded_at->format('d/m/Y H:i') }}</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Footer --}}
        <div class="footer">
            Documento generado el {{ now()->translatedFormat('d/m/Y H:i') }} | UUID: {{ $request->uuid }}
        </div>
    </div>
</body>
</html>
