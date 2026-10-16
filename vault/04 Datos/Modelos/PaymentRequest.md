---
tipo: modelo
aliases:
  - Solicitud de pago (legacy)
tags:
  - payment-request/datos
  - payment-request/modelos
fuentes:
  - app/Models/PaymentRequest.php
  - app/States/PaymentRequest/PaymentRequestState.php
  - app/Policies/PaymentRequestPolicy.php
  - app/Services/ApprovalService.php
  - database/migrations/2026_02_12_230209_create_payment_requests_table.php
actualizado: 2026-10-15
---

# PaymentRequest

Solicitud de pago del módulo original (anterior a inversiones). Recorre tres etapas de autorización: departamento → administración → tesorería.

## Tabla `payment_requests`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint | PK |
| `uuid` | char(36), único | Route key |
| `folio_number` | int, único, nullable | `max + 1` incluyendo borrados |
| `user_id` | FK → `users` | Solicitante |
| `department_id` | FK → `departments`, nullable | |
| `provider` | string | MAYÚSCULAS |
| `rfc` | string(13), nullable | |
| `invoice_folio` | string | MAYÚSCULAS |
| `currency_id`, `branch_id`, `expense_concept_id` | FK | `restrictOnDelete` |
| `payment_type_id` | FK → `payment_types`, nullable | Reemplazó a la columna string `payment_type` |
| `description` | text, nullable | |
| `advance_documents` | json, nullable | |
| `status` | string | Estado spatie (ver [[Estados]]) |
| `subtotal`, `iva`, `total` | decimal(12,2) | |
| `iva_rate` | string(4), default `0.16` | |
| `retention` | decimal(12,2), default 0 | El modelo lo castea a `boolean` |
| `number_purchase_invoices`, `number_vendor_payments` | bigint, nullable | Folios SAP capturados al aprobar |
| `created_at`, `updated_at`, `deleted_at` | timestamps | Soft deletes |

> [!question] Por confirmar
> `retention` es `decimal(12,2)` en la migración pero el modelo lo castea a `boolean`. En inversiones la columna sí es boolean.

## Casts y fillable

- Casts: `status` → `PaymentRequestState`, `iva_rate` → `IvaRate`, `advance_documents` → `array`, montos → `decimal:2`, `retention` → `boolean`.
- Fillable: datos del proveedor, catálogos, documentos, montos y folios SAP.

## Relaciones

| Método | Tipo | Destino |
|---|---|---|
| `user()` | BelongsTo | [[User]] |
| `department()` | BelongsTo | [[Department]] |
| `currency()` | BelongsTo | [[Currency]] |
| `branch()` | BelongsTo | [[Branch]] |
| `paymentType()` | BelongsTo | [[PaymentType]] |
| `expenseConcept()` | BelongsTo | [[ExpenseConcept]] |
| `approvals()` | HasMany | [[PaymentRequestApproval]] |

## Scopes

`visibleTo(User $user)`: `super_admin` ve todo; el resto ve las propias, las de sus departamentos (autorizador nivel 1/2 o pivote `department_user`) y aquellas donde tiene una aprobación. No filtra por proyecto.

## Estados y eventos

- `HasStates`: `pending_department` → `pending_administration` → `pending_treasury` → `completed`. No existe estado de rechazo: un rechazo deja la solicitud en su etapa actual. Ver [[Estados]].
- `booted()` → `creating`: genera `uuid` y `folio_number`.

## Traits

`HasFactory`, `HasStates`, `LogsActivity`, `SoftDeletes`.

## Policy

`app/Policies/PaymentRequestPolicy.php` (permiso Shield + acceso al registro). Ver [[Policies]].

## Dónde se usa

- [[Modulo legacy Payment Requests]] · [[Flujo legacy de solicitud de pago]] · [[Dashboards]]

## Relacionado
- [[Modelo de datos]] · [[PaymentRequestApproval]] · [[Estados]]
