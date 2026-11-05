---
tipo: salida
aliases:
  - PDFs
  - Reportes PDF
tags:
  - payment-request/pdf
  - payment-request/salidas
fuentes:
  - resources/views/pdf/payment-request.blade.php
  - resources/views/pdf/payment-history.blade.php
  - resources/views/pdf/investment-requests-consolidated.blade.php
  - resources/views/pdf/final-approval-summary-pm.blade.php
  - app/Http/Controllers/PaymentRequestPdfController.php
  - app/Http/Controllers/InvestmentRequestPdfController.php
  - app/Http/Controllers/InvestmentPaymentHistoryPdfController.php
  - app/Http/Controllers/InvestmentSheetInvestmentRequestsPdfController.php
  - app/Http/Controllers/Concerns/ResolvesDisplayCurrency.php
  - app/Notifications/InvestmentBatchFinalApprovalSummaryToPmNotification.php
  - app/Services/InvestmentDepartmentBreakdownService.php
  - composer.json
  - composer.lock
  - resources/views/pdf/strategies.blade.php
  - app/Http/Controllers/StrategyHistoryPdfController.php
  - app/Services/StrategyPaymentHistory.php
actualizado: 2026-11-04
---

# PDFs

El portal genera cinco tipos de PDF con **DomPDF** a partir de vistas Blade en `resources/views/pdf/`. Cuatro se descargan desde el portal y uno se adjunta a un correo.

## Librería

- `barryvdh/laravel-dompdf` v3.1.2 (sobre `dompdf/dompdf` v3.1.5), declarada como `"*"` en `composer.json`. Se usa la fachada `Barryvdh\DomPDF\Facade\Pdf::loadView()`.
- No hay `config/dompdf.php` publicado: aplica la configuración por defecto del paquete.
- Todas las vistas usan la fuente **DejaVu Sans** (y DejaVu Sans Mono para montos), que DomPDF incluye y soporta acentos.
- Los PDFs se generan **en el request** (sin cola), salvo el adjunto del correo, que se genera dentro de la notificación encolada.

## Resumen

| Vista | Disparador | Ruta / origen | Papel | Archivo |
|---|---|---|---|---|
| `pdf.payment-request` | `PaymentRequestPdfController` | `GET /payment-requests/{uuid}/pdf` | default del paquete (A4 vertical) | `solicitud-pago-{folio}.pdf` |
| `pdf.payment-request` | `InvestmentRequestPdfController` | `GET /investment-sheets/{investment_request}/pdf` | default del paquete (A4 vertical) | `solicitud-inversion-{folio}.pdf` |
| `pdf.payment-history` | `InvestmentPaymentHistoryPdfController` | `GET /investment-sheets/consolidated/{project}/payment-history-pdf` | oficio horizontal | `historial-pagos-{slug}-{AAAAMMDD}.pdf` |
| `pdf.investment-requests-consolidated` | `InvestmentSheetInvestmentRequestsPdfController` | `GET /investment-sheets/consolidated/{project}/investment-requests-pdf` | oficio horizontal | `Solicitudes de Inversion - {proyecto} - {depto o TODOS} - {AAAA-MM-DD}.pdf` |
| `pdf.final-approval-summary-pm` | `InvestmentBatchFinalApprovalSummaryToPmNotification` | Adjunto de correo | carta horizontal | `aprobacion-final-{slug}-{AAAAMMDD-HHmm}.pdf` |
| `pdf.strategies` | `StrategyHistoryPdfController` | `GET /strategies/pdf` | oficio horizontal | `estrategias-{slug}-{AAAAMMDD}.pdf` o `estrategias-todas-las-hojas-de-inversion-{AAAAMMDD}.pdf` |

## `pdf.payment-request` (Solicitud de Pago / de Inversión)

Vista compartida; el controller pasa `request` (el modelo) y `title` ("Solicitud de Pago" o "Concepto de Inversión").

- **Acceso**: `Gate::authorize('view', …)` y `abort_unless` estado `completed` (403 si no está finalizada).
- **Contenido**: encabezado con marca, título, folio a 5 dígitos, fecha de creación, UUID y badge de estado; Información General (solicitante, departamento, fecha, moneda); Detalles (sucursal, concepto de gasto, tipo de pago, descripción); Datos del Proveedor (razón social, RFC, folio factura); Montos (subtotal, IVA con su tasa, retención, total); Folios SAP (Factura Proveedores y Pago Efectuado, solo si alguno existe); nombres de Documentos Adjuntos; Historial de Aprobaciones (etapa, nivel, aprobador, estado, comentarios).

> [!question] Por confirmar
> Para Solicitudes de Inversión, el PDF muestra "Concepto de Gasto" desde la relación legacy `expenseConcept` y "Tipo de Pago" desde `paymentType`; las solicitudes nuevas usan `investmentExpenseConcept`, por lo que esos campos pueden salir como "-". Tampoco muestra proyecto ni si es aditiva.

Ver [[Modulo legacy Payment Requests]] y [[Solicitudes de inversion]].

## `pdf.payment-history` (Historial de Pagos de Inversión)

Generado por `InvestmentPaymentHistoryPdfController` desde el [[Consolidado por proyecto]].

- **Acceso**: `canAccessProject` (403 "No tienes acceso a este proyecto."). Ver [[Acceso por proyecto]].
- **Filtros** (query string): `department_id` (usuarios no privilegiados solo sus departamentos; 403 si piden otro), `week_number` + `week_year` (**obligatorios** para usuarios que no son super_admin/ceo/PM), `status`, `quick_filter` (`in_process`, `completed`, `rejected`), `search` (proveedor, folio, RFC, folio factura, descripción, solicitante, concepto o categoría) y `display_currency_id`.
- Excluye pagos en `draft`. La semana se filtra por `payment_provision_date` (semana ISO).
- **Columnas**: Folio, Estatus (etiquetas en español, con color), F. Programación Pago, Concepto (+ línea de estrategia si aplica), Descripción, Categoría del Concepto, Proveedor, Depto (solo si se piden todos los departamentos), Monto Solicitado, Monto Aprobado, Solicitante, F. Solicitud.
- **Encabezado**: proyecto, fecha y usuario que generó, número de pagos, moneda de despliegue y filtros aplicados.
- **Totales**: total solicitado y total aprobado (con *fallback* al solicitado), más nota de tipos de cambio.

## `pdf.strategies` (Estrategias Solicitadas)

Generado por `StrategyHistoryPdfController` desde la consulta de [[Estrategias]].

- **Acceso**: casilla `view_portal::strategies` (403 sin ella).
- **Filtros** (query string): `project_id` (ID o `all`; 404 si es inválido), `department_id`, `status`, `capture_week` y `provision_week` (formato `AAAA-S`). Un filtro de estado o semana inválido se ignora.
- **Columnas**: Folio, Hoja de Inversión, Departamento / Solicitante, Concepto / Proveedor, Código y tasa, Monto base, Recargo, Total, Aprobado, Moneda, Tipo, Estado, Fecha captura, Sem. capt., Fecha prog., Sem. prog.
- **Encabezado**: Hoja o "Todas las Hojas de Inversión", fecha y usuario que generó, número de estrategias y filtros aplicados.
- **Montos**: en su moneda original, sin conversión ni totales.

## `pdf.investment-requests-consolidated` (Solicitudes de Inversión del proyecto)

Generado por `InvestmentSheetInvestmentRequestsPdfController`.

- **Acceso**: middleware `role:super_admin|ceo|project_manager` en la ruta, `abort_unless` del mismo rol en el controller y `canAccessProject`.
- **Filtro**: solo `department_id` (búsqueda y estado se ignoran a propósito) y `display_currency_id`.
- **Contenido**:
  - Datos de sucursal: sucursal, razón social y RFC de la sociedad, CeCo.
  - Mini tarjeta del proyecto: número de conceptos y monto total (siempre del proyecto completo).
  - "Inversión por Departamento": tarjetas de `InvestmentDepartmentBreakdownService` (total, % consumido con color ok < 70 % ≤ warn < 90 % ≤ danger, comprometido, pagado, disponible), filtradas por departamento si aplica. Ver [[Dashboards]].
  - "Detalle de Solicitudes de Inversión": por departamento y, dentro, por concepto de inversión. Un concepto con varias solicitudes muestra una fila padre ("presupuesto inicial + N aditivas", presupuesto y saldo del grupo) y sub-filas por solicitud (descripción, folio, Inicial/Aditiva, estado, monto).
  - Pie: fecha, usuario, departamento, moneda, tipos de cambio y paginación "Página X de Y" (script DomPDF, requiere `setOption('isPhpEnabled', true)`).

## `pdf.final-approval-summary-pm` (adjunto de aprobación final)

Generado dentro de `InvestmentBatchFinalApprovalSummaryToPmNotification::toMail()` y adjuntado con `attachData()` (MIME `application/pdf`).

- **Contenido**: "Resumen de Aprobación Final" del proyecto, aprobador y fecha, número de pagos aprobados y rechazados en la sesión; por departamento, tabla con Folio, F. Solicitud, F. Provisión, Concepto (categoría – concepto, más línea de estrategia), Proveedor, Solicitante y Monto Aprobado (usa `approved_amount` si existe); subtotales y total general **por moneda** (sin conversión).
- Pie: "Documento confidencial · Generado automáticamente al cerrar la aprobación final".

Ver [[Catalogo de correos]] y [[Revision del PM]].

## Moneda de despliegue

Los reportes de proyecto usan el trait `ResolvesDisplayCurrency`:

1. `display_currency_id` del request, si existe.
2. Si no, la moneda por defecto del proyecto (`projects.currency_id`).
3. Si no, `MXN`.

Cada monto se normaliza a MXN (`total × exchange_rate` de su moneda) y se convierte con `mxnToDisplay()` (÷ `exchange_rate` de la moneda de despliegue). `exchangeRateNote()` imprime todos los tipos de cambio usados. Ver [[Multimoneda]].

## Relacionado
- [[Exportaciones Excel]] · [[Catalogo de correos]] · [[Rutas]] · [[Multimoneda]]
- [[Consolidado por proyecto]] · [[Estrategias]] · [[Modulo legacy Payment Requests]] · [[Acceso por proyecto]]
