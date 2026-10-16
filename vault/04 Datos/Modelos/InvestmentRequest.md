---
tipo: modelo
aliases:
  - Solicitud de inversión
  - Hoja de inversión
tags:
  - payment-request/datos
  - payment-request/modelos
fuentes:
  - app/Models/InvestmentRequest.php
  - app/States/InvestmentRequest/InvestmentRequestState.php
  - app/Policies/InvestmentRequestPolicy.php
  - app/Services/InvestmentApprovalService.php
  - database/migrations/2026_04_10_000001_create_investment_requests_table.php
actualizado: 2026-10-15
---

# InvestmentRequest

Solicitud de inversión (en la UI "hoja de inversión"): presupuesto pedido por un departamento para un concepto de gasto en un proyecto. Las solicitudes en estado `completed` forman el presupuesto contra el que se capturan los pagos.

## Tabla `investment_requests`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint | PK |
| `uuid` | char(36), único | Route key |
| `folio_number` | int, único | `max + 1` incluyendo borrados |
| `user_id` | FK → `users` | Solicitante |
| `department_id` | FK → `departments` | |
| `project_id` | FK → `projects`, nullable | Históricos pueden no tenerlo |
| `provider` | string, nullable | MAYÚSCULAS |
| `rfc` | string(13), nullable | |
| `contact_name`, `contact_email`, `contact_phone` | string, nullable | Contacto del proveedor |
| `invoice_folio` | string, nullable | |
| `currency_id` | FK → `currencies` | |
| `branch_id` | FK → `branches` | |
| `expense_concept_id` | FK, nullable | Concepto legacy |
| `investment_expense_concept_id` | FK, nullable | Concepto de inversión (llave de presupuesto) |
| `description` | text, nullable | MAYÚSCULAS |
| `payment_type_id` | FK → `payment_types`, nullable | |
| `advance_documents` | json | Documentos adjuntos |
| `status` | string, default `pending_department` | Estado spatie (ver [[Estados]]) |
| `is_addendum` | boolean, default `false` | Solicitud "aditiva": ya existía una completada del mismo proyecto+concepto |
| `is_deductive` | boolean, default `false` | Solicitud "deductiva" |
| `subtotal`, `iva`, `total` | decimal(14,2) | |
| `iva_rate` | string(4), default `0.16` | |
| `retention` | boolean | |
| `number_purchase_invoices`, `number_vendor_payments` | int, nullable | Folios SAP |
| `created_at`, `updated_at`, `deleted_at` | timestamps | Soft deletes |

> [!info]
> La tabla se recreó dos veces con SQL específico de SQLite (`PRAGMA`, `sqlite_master`) para volver nullable `expense_concept_id` y `provider`. Ver [[Migraciones]].

## Casts y fillable

- Casts: `status` → `InvestmentRequestState`, `iva_rate` → `IvaRate`, `advance_documents` → `array`, montos → `decimal:2`, `retention` → `boolean`.
- Fillable: datos de proveedor, moneda, sucursal, conceptos, descripción, tipo de pago, documentos y montos. `user_id`, `department_id`, `project_id`, `is_addendum` e `is_deductive` **no** son fillable: `InvestmentRequestController::store()` los asigna directamente.

> [!question] Por confirmar
> No se encontró en `app/` ningún punto que escriba `is_deductive = true`; solo se lee en `InvestmentSheetConsolidatedController`. Confirmar cómo se marcan las deductivas (¿solo carga histórica o corrección manual?).

## Relaciones

| Método | Tipo | Destino |
|---|---|---|
| `user()` | BelongsTo | [[User]] |
| `department()` | BelongsTo | [[Department]] |
| `currency()` | BelongsTo | [[Currency]] |
| `project()` | BelongsTo | [[Project]] |
| `branch()` | BelongsTo | [[Branch]] |
| `paymentType()` | BelongsTo | [[PaymentType]] |
| `expenseConcept()` | BelongsTo | [[ExpenseConcept]] |
| `investmentExpenseConcept()` | BelongsTo | [[InvestmentExpenseConcept]] |
| `approvals()` | HasMany | [[InvestmentRequestApproval]] |
| `investmentPaymentRequests()` | HasMany | [[InvestmentPaymentRequest]] |

## Métodos de negocio

- `budgetBreakdown(?int $excludePaymentId)` — **única fuente de verdad** del saldo del concepto. Si la solicitud tiene concepto y proyecto, agrupa todas las solicitudes `Completed` del mismo `project_id + investment_expense_concept_id + department_id`; si no, usa solo la propia solicitud. Todo normalizado a MXN (`× exchange_rate`). Devuelve `budget`, `committed` (pagos en `COMMITTED_STATUSES`), `paid` (pagos en `PAID_STATUSES`), `available = budget − committed − paid` y `scope` (`concept` o `request`). `$excludePaymentId` evita contar un borrador contra sí mismo al editarlo.
- `availableBudget()` — atajo a `budgetBreakdown()['available']`.
- Accessor `remaining_balance` — `availableBudget()` formateado a 2 decimales.

Ejemplo ficticio: presupuesto completado $100,000.00 MXN, borradores y enviados $30,000.00, pagados $50,000.00 → disponible $20,000.00.

## Scopes

`visibleTo(User $user)`:
1. `super_admin` ve todo.
2. Resto: solicitudes propias **o** de departamentos donde es autorizador nivel 1/2 **o** de su pivote `department_user` **o** donde tiene una aprobación asignada.
3. Además (AND), salvo `ceo` y `project_manager`: solo proyectos asignados en `project_user`; las solicitudes sin `project_id` no se bloquean.

## Estados y eventos

- `HasStates`: estado por defecto `pending_department`; transiciones a `completed` o `rejected`. Ver [[Estados]].
- `booted()` → `creating`: genera `uuid` y `folio_number`.
- `getRouteKeyName()` → `uuid`.

## Traits

`HasFactory`, `HasStates`, `LogsActivity`, `SoftDeletes`.

## Policy

`app/Policies/InvestmentRequestPolicy.php`: `view`, `update` y `delete` exigen el permiso Shield **y** acceso al registro (`super_admin`, dueño, autorizador del departamento o aprobador asignado). Ver [[Policies]].

## Dónde se usa

- [[Solicitudes de inversion]] · [[Consolidado por proyecto]] · [[Presupuesto por concepto]] · [[Dashboards]] · [[Acceso por proyecto]] · [[Flujo de solicitud de inversion]]

## Relacionado
- [[Modelo de datos]] · [[Estados]] · [[InvestmentPaymentRequest]]
