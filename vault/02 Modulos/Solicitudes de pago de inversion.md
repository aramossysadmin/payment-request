---
tipo: modulo
aliases:
  - Solicitudes de pago de inversión
  - Pagos de inversión
tags:
  - payment-request/modulos
  - payment-request/inversion
fuentes:
  - app/Models/InvestmentPaymentRequest.php
  - app/Http/Controllers/InvestmentPaymentRequestController.php
  - app/Http/Requests/StoreInvestmentPaymentRequest.php
  - app/Http/Requests/UpdateInvestmentPaymentRequest.php
  - app/Http/Requests/Concerns/ResolvesInvestmentStrategy.php
  - app/Enums/InvestmentPaymentType.php
  - app/Enums/IvaRate.php
actualizado: 2026-10-15
---

# Solicitudes de pago de inversión

Un pago de inversión (`InvestmentPaymentRequest`) es una solicitud de pago a un proveedor que consume el presupuesto de una [[Solicitudes de inversion|solicitud de inversión]]. Nace como borrador dentro de un [[Lotes de pago|lote]] semanal y recorre el [[Flujo de pago de inversion]].

## Identificadores

| Campo | Regla | Fuente |
|---|---|---|
| `uuid` | UUID generado en `creating`; es la llave de ruta | `InvestmentPaymentRequest::booted()` |
| `folio_number` | Máximo histórico (incluye borrados) + 1; independiente del folio de las solicitudes de inversión | `InvestmentPaymentRequest::booted()` |
| `payment_week_number` | Semana ISO (`Carbon::isoWeek`) de `payment_provision_date`; se recalcula al crear, editar y en la corrección de fecha | `InvestmentPaymentRequestController` |
| `batch_id` | Lote draft de la semana ISO actual del departamento + proyecto | `findOrCreateActiveBatch()` |

Normalización: `provider`, `rfc`, `invoice_folio` y `description` se guardan en mayúsculas. Usa `SoftDeletes` y `LogsActivity`.

## Tipos de pago

Enum `App\Enums\InvestmentPaymentType`:

| Valor | Etiqueta | Adjuntos al capturar (opcionales) | Adjuntos al subir documentos (obligatorios) |
|---|---|---|---|
| `factura` | Factura | `invoice_documents`: exactamente 1 PDF + 1 XML | `pdf` + `xml` |
| `reembolso` | Reembolso | `advance_documents`: 1 archivo PDF/JPG/JPEG/PNG | `document`: 1 PDF o imagen |
| `estrategia` | Estrategia | Igual que reembolso | Igual que reembolso |
| `anticipo` | Anticipo | Igual que reembolso | Igual que reembolso |
| `cotizacion` | Cotización | Igual que reembolso | Igual que reembolso |
| `pagare` | Pagaré | Igual que reembolso | Igual que reembolso |
| `domiciliado` | Domiciliado | Igual que reembolso | Igual que reembolso |
| `factura_espana` | Factura España | Igual que reembolso | Igual que reembolso |

Regla de fondo: **factura = PDF + XML; el resto = 1 archivo PDF o imagen**. Las reglas declarativas aceptan hasta 2 archivos de factura y hasta 10 adjuntos de 10 MB, pero `withValidator()` reduce el resto de tipos a un único archivo. El tipo puede cambiar al subir documentos (ver [[Documentos de pago]]).

## Campos de monto

`iva_rate` (enum `IvaRate`: 0, 4, 8, 10, 16 y 21 %), `subtotal`, `iva`, `total`, `retention` (booleano). En el flujo de aprobación se agrega `approved_amount` (monto que fija el PM, ≤ `total`). Para el tipo estrategia también `strategy_id`, `strategy_amount`, `strategy_factor_snapshot` y `strategy_name_snapshot` (ver [[Estrategias]]).

## Captura (`store`)

Ruta `POST investment-payment-requests`, validada por `StoreInvestmentPaymentRequest`:

1. **Ventana de captura:** si `canCaptureNow()` es falso, registra el intento bloqueado (`PaymentPolicyAuditService::logBlockedAttempt(…, 'create')`) y rechaza con la próxima apertura en hora CDMX.
2. **Fecha de programación:** debe ser exactamente `getNextProvisionDate()` (por defecto el martes de la siguiente semana).
3. **Estrategia activa** si el tipo es estrategia.
4. **Adjuntos por tipo** (tabla anterior).
5. **Presupuesto:** convierte el total a MXN con el `exchange_rate` de la moneda elegida y lo compara en centavos contra `InvestmentRequest::budgetBreakdown()['available']`. Si excede, error en `total`. Ver [[Presupuesto por concepto]].

Después, `InvestmentPaymentRequestController::store()`:

- 403 si el proyecto de la solicitud de inversión está cerrado.
- Obtiene o crea el lote draft (ver [[Lotes de pago]]).
- Rellena atributos de estrategia, `user_id`, `department_id` = **departamento de la solicitud de inversión** (no del usuario), `status = draft` y `payment_week_number`.
- Guarda adjuntos en disco `local` bajo `investment-payment-documents/AAAA/MM/{folio}` con nombre UUID.
- Mensaje: "Pago agregado al borrador de la semana."

## Edición de borrador (`update`)

Ruta `PATCH|POST investment-payment-requests/{uuid}`, validada por `UpdateInvestmentPaymentRequest`:

- `authorize()`: solo el dueño del pago y solo en estado `draft`.
- Bloqueo por ventana de captura (auditado como `edit`). **No** revalida que la fecha sea la de provisión esperada.
- Presupuesto: `budgetBreakdown(excludePaymentId: $payment->id)` para no contar el pago contra sí mismo.
- `keep_documents[]` indica qué adjuntos conservar; los que no se conservan **se eliminan del disco** (`Storage::delete`); los nuevos se agregan.

## Corrección de fecha (solo super_admin)

`PATCH investment-payment-requests/{uuid}/provision-date` (`correctProvisionDate`), con middleware `role:super_admin`: cambia `payment_provision_date` y recalcula `payment_week_number` sin pasar por la ventana de captura ni tocar el lote.

## Estados

| Estado | Significado | Consume presupuesto como |
|---|---|---|
| `draft` | En el lote borrador | Comprometido |
| `submitted` | Enviado al CEO | Comprometido |
| `ceo_approved` | Aprobado por CEO, en cola del PM | Comprometido |
| `ceo_rejected` | Rechazado por CEO | Libera |
| `final_approved` | Aprobación final del PM; espera documentos | Comprometido |
| `projectmanager_rejected` | Rechazado por el PM | Libera |
| `completed` | Documentos cargados | Pagado |
| `scheduled_for_bank` | Incluido en programación semanal autorizada | Pagado |
| `receipt_attached` | Comprobante adjunto; fin del proceso | Pagado |
| `auto_cancelled` | Borrador cancelado al cierre de la ventana de envío | Libera |
| `projectmanager_review`, `projectmanager_approved`, `documents_pending`, `final_pending` | Históricos de flujos anteriores | Comprometido (los listados en `COMMITTED_STATUSES`) |
| `pending_approval`, `approved`, `rejected` | Flujo legacy de aprobación por correo por pago | Pagado (`approved`, `pending_approval`) / libera (`rejected`) |

Constantes: `InvestmentPaymentRequest::COMMITTED_STATUSES` y `InvestmentPaymentRequest::PAID_STATUSES`. Diagrama completo en [[Flujo de pago de inversion]].

> [!info] Campo `status` sin máquina de estados
> A diferencia de `InvestmentRequest`, el `status` de los pagos es un string libre; las transiciones se controlan en cada controlador.

## Métodos de presentación

- `hasStrategy()`, `strategyRate()`, `strategySummary()`, `strategyBreakdown()` (ver [[Estrategias]]).
- `strategy()` incluye estrategias en papelera (`withTrashed()`).

## Relacionado
- [[Flujo de pago de inversion]] · [[Lotes de pago]] · [[Aprobacion del CEO]] · [[Revision del PM]] · [[Documentos de pago]]
- [[Consolidado por proyecto]] · [[Presupuesto por concepto]] · [[Estrategias]] · [[Politica de ventanas de pago]]
- [[InvestmentPaymentRequest]] · [[Enums]] · [[Multimoneda]]
