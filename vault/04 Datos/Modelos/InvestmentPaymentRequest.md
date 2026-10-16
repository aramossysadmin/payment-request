---
tipo: modelo
aliases:
  - Solicitud de pago de inversión
  - Pago de inversión
tags:
  - payment-request/datos
  - payment-request/modelos
fuentes:
  - app/Models/InvestmentPaymentRequest.php
  - app/Policies/InvestmentPaymentRequestPolicy.php
  - app/Filament/Resources/InvestmentPaymentRequestResource.php
  - database/migrations/2026_04_15_182435_create_investment_payment_requests_table.php
  - database/migrations/2026_09_22_135544_add_strategy_fields_to_investment_payment_requests_table.php
actualizado: 2026-10-15
---

# InvestmentPaymentRequest

Pago individual contra una solicitud de inversión autorizada. Es el registro central del flujo de pagos: nace como borrador dentro de un lote, pasa por CEO y PM, recibe documentos, se programa en banco y termina con comprobante.

## Tabla `investment_payment_requests`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint | PK |
| `uuid` | uuid, único | Route key, se genera en `creating` |
| `folio_number` | bigint, único | Consecutivo `max(folio_number) + 1` incluyendo borrados |
| `investment_request_id` | FK → `investment_requests` | `cascadeOnDelete` |
| `batch_id` | FK → `investment_payment_batches`, nullable | `nullOnDelete` |
| `user_id` | FK → `users` | Capturista |
| `department_id` | FK → `departments` | |
| `provider` | string | MAYÚSCULAS |
| `rfc` | string(13), nullable | MAYÚSCULAS |
| `invoice_folio` | string, nullable | MAYÚSCULAS |
| `currency_id` | FK → `currencies` | |
| `branch_id` | FK → `branches` | |
| `expense_concept_id` | FK, nullable | |
| `strategy_id` | FK → `strategies`, nullable | `nullOnDelete` |
| `strategy_amount` | decimal(14,2), nullable | Monto base de la estrategia |
| `strategy_factor_snapshot` | decimal(8,6), nullable | Copia fija del factor al capturar |
| `strategy_name_snapshot` | string(150), nullable | Copia fija del nombre |
| `payment_type` | string(20), default `anticipo` | Valor de [[Enums#InvestmentPaymentType]] |
| `description` | text, nullable | MAYÚSCULAS |
| `payment_provision_date` | date, nullable | Fecha de provisión del pago |
| `payment_week_number` | tinyint, nullable | |
| `status` | string | Ver [[Estados]] |
| `ceo_rejection_reason`, `ceo_reviewed_at` | text / timestamp | Revisión CEO |
| `pm_rejection_reason`, `pm_reviewed_at` | text / timestamp | Revisión PM |
| `final_rejection_reason`, `final_reviewed_at` | text / timestamp | Aprobación final (hoy la marca el PM) |
| `auto_cancelled_at`, `auto_cancellation_reason` | timestamp / string | Cancelación automática |
| `subtotal`, `iva`, `total` | decimal(14,2) | |
| `iva_rate` | string(4) | [[Enums#IvaRate]] |
| `retention` | boolean | |
| `approved_amount` | decimal(14,2), nullable | Monto aprobado por el PM (≤ `total`) |
| `advance_documents` | json | Rutas de documentos del pago (factura PDF+XML u otro) |
| `payment_receipt_documents` | json | Rutas del comprobante de pago |
| `receipt_uploaded_at` | timestamp | |
| `receipt_uploaded_by` | FK → `users`, nullable | Sin relación definida en el modelo |
| `created_at`, `updated_at`, `deleted_at` | timestamps | Soft deletes |

## Constantes de estado

```php
COMMITTED_STATUSES = ['draft', 'submitted', 'ceo_approved', 'projectmanager_review',
                      'projectmanager_approved', 'final_approved', 'documents_pending'];
PAID_STATUSES      = ['pending_approval', 'approved', 'completed',
                      'scheduled_for_bank', 'receipt_attached'];
```

- **Comprometido**: reserva presupuesto desde el borrador, aún no pagado.
- **Pagado**: desde que se cargan documentos (`completed`) hasta el comprobante, más los estados legacy.
- Rechazados (`ceo_rejected`, `projectmanager_rejected`, `rejected`) y `auto_cancelled` no consumen presupuesto.

Ver [[Estados]] y [[Presupuesto por concepto]].

## Casts

`payment_provision_date` → `date`; `payment_week_number` → `integer`; `iva_rate` → `IvaRate`; `advance_documents`, `payment_receipt_documents` → `array`; `subtotal`, `iva`, `total`, `approved_amount`, `strategy_amount` → `decimal:2`; `strategy_factor_snapshot` → `decimal:6`; `retention` → `boolean`; `receipt_uploaded_at`, `ceo_reviewed_at`, `pm_reviewed_at`, `final_reviewed_at` → `datetime`.

> [!warning] Por confirmar
> `auto_cancelled_at` y `auto_cancellation_reason` **no están en `$fillable`**, y `CancelExpiredPaymentDraftsCommand` los escribe con `$payment->update([...])`. Eloquent descarta en silencio los atributos no asignables, así que probablemente solo se guarda `status = auto_cancelled`. Verificar en datos reales.

Tampoco están en fillable `user_id`, `department_id` ni `uuid`: el controlador los asigna directamente.

## Relaciones

| Método | Tipo | Destino |
|---|---|---|
| `investmentRequest()` | BelongsTo | [[InvestmentRequest]] |
| `batch()` | BelongsTo | [[InvestmentPaymentBatch]] (`batch_id`) |
| `user()` | BelongsTo | [[User]] |
| `department()` | BelongsTo | [[Department]] |
| `currency()` | BelongsTo | [[Currency]] |
| `branch()` | BelongsTo | [[Branch]] |
| `expenseConcept()` | BelongsTo | [[ExpenseConcept]] |
| `strategy()` | BelongsTo | [[Strategy]] (incluye borradas: `withTrashed()`) |
| `approvals()` | HasMany | [[InvestmentPaymentApproval]] (legacy) |

Además es referenciado por [[WeeklyPaymentScheduleItem]].

## Métodos de negocio

- `hasStrategy()` — `true` si hay `strategy_amount` y `strategy_factor_snapshot` (se basa en la copia guardada, no en `payment_type`, porque el tipo puede cambiar al subir documentos).
- `strategyRate()` — tasa en porcentaje sin ceros sobrantes (factor × 100).
- `strategySummary()` — texto único para correos, PDFs y reportes, p. ej. "Estrategia 4.5% · Monto Estrategia $10,000.00 MXN".
- `strategyBreakdown()` — arreglo `rate`, `amount`, `surcharge` (total − monto), `total`, `currency`.

## Eventos

`booted()` → `creating`: genera `uuid` y `folio_number` si faltan. `getRouteKeyName()` → `uuid`.

## Traits

`HasFactory`, `LogsActivity`, `SoftDeletes`.

## Policy

`app/Policies/InvestmentPaymentRequestPolicy.php` (Shield, `*_investment::payment::request`). El recurso Filament es de solo lectura (create/edit/delete devuelven `false`). En el portal Inertia la autorización vive en los controladores (dueño del pago, departamento, `visibleTo` del proyecto, proyecto abierto, rol `super_admin` para correcciones).

## Dónde se usa

- [[Solicitudes de pago de inversion]] · [[Lotes de pago]] · [[Aprobacion del CEO]] · [[Revision del PM]] · [[Documentos de pago]] · [[Programacion semanal]] · [[Comprobantes de pago]] · [[Estrategias]] · [[Consolidado por proyecto]] · [[Cancelacion automatica de borradores]]

## Relacionado
- [[Modelo de datos]] · [[Estados]] · [[Flujo de pago de inversion]] · [[Enums]]
