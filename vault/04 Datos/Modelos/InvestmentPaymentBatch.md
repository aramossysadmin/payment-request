---
tipo: modelo
aliases:
  - Lote de pagos de inversión
tags:
  - payment-request/datos
  - payment-request/modelos
fuentes:
  - app/Models/InvestmentPaymentBatch.php
  - app/Policies/InvestmentPaymentBatchPolicy.php
  - app/Filament/Resources/InvestmentPaymentBatchResource.php
  - app/Http/Controllers/InvestmentPaymentBatchController.php
  - app/Http/Controllers/InvestmentBatchApprovalController.php
  - app/Http/Controllers/InvestmentPaymentReviewController.php
  - database/migrations/2026_05_17_155651_create_investment_payment_batches_table.php
actualizado: 2026-10-15
---

# InvestmentPaymentBatch

Lote semanal de pagos de inversión de un departamento en un proyecto. Agrupa los borradores que se envían juntos a autorización del CEO y lleva el token del enlace de aprobación por correo.

## Tabla `investment_payment_batches`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint | PK |
| `uuid` | uuid, único | Route key; se genera en `creating` |
| `department_id` | FK → `departments` | `cascadeOnDelete` |
| `user_id` | FK → `users` | Creador del lote, `cascadeOnDelete` |
| `project_id` | FK → `projects` | `cascadeOnDelete` |
| `week_number`, `year` | smallint | Semana ISO del lote |
| `status` | string, default `draft` | Ver [[Estados]] |
| `submitted_at` | timestamp, nullable | Envío a CEO |
| `deadline_at` | timestamp, nullable | |
| `ceo_approval_token` | uuid, nullable, único | Enlace del correo al CEO |
| `ceo_approval_token_expires_at` | timestamp | 96 horas tras el envío |
| `ceo_reviewed_at` | timestamp | |
| `final_ceo_approval_token`, `final_ceo_approval_token_expires_at`, `final_ceo_reviewed_at` | uuid / timestamps | Paso "aprobación final del CEO" (histórico; hoy `final_ceo_reviewed_at` lo marca la revisión del PM) |
| `final_session_token`, `final_session_token_expires_at` | uuid / timestamp, indexado | Histórico del mismo paso |
| `created_at`, `updated_at` | timestamps | Sin soft deletes |

Índice compuesto: `department_id, project_id, year, week_number, status`.

## Casts y fillable

- Casts: todos los `*_at` y `*_expires_at` → `datetime`; `week_number`, `year` → `integer`.
- Fillable: todas las columnas anteriores salvo `id`.

## Relaciones

| Método | Tipo | Destino |
|---|---|---|
| `department()` | BelongsTo | [[Department]] |
| `user()` | BelongsTo | [[User]] |
| `project()` | BelongsTo | [[Project]] |
| `paymentRequests()` | HasMany | [[InvestmentPaymentRequest]] (FK `batch_id`) |

## Métodos y eventos

- `booted()` → `creating`: asigna `uuid` si falta.
- `getRouteKeyName()` → `uuid`.
- `hasValidCeoToken(): bool` — token presente y `ceo_approval_token_expires_at` en el futuro.

## Ciclo de vida (resumen)

1. `draft`: se crea al capturar el primer pago de la semana.
2. `InvestmentPaymentBatchController::submit()` → `submitted`, genera `ceo_approval_token` (96 h) y notifica a todos los usuarios con rol `ceo`. Los pagos no seleccionados se mueven a un **nuevo** lote `draft`.
3. `InvestmentBatchApprovalController::review()` → `ceo_approved` (si aprobó al menos uno) o `ceo_rejected`; limpia el token.
4. `InvestmentPaymentReviewController::submit()` (PM) → `final_approved` (al menos un pago aprobado) o `projectmanager_rejected`, solo cuando ya no quedan pagos pendientes de revisión PM en el lote.
5. `CancelExpiredPaymentDraftsCommand` → `auto_cancelled` si todos sus pagos quedaron cancelados/rechazados y el lote seguía en `draft`.

Detalle en [[Estados]] y [[Flujo de pago de inversion]].

## Traits

`HasFactory`, `LogsActivity`. Sin `SoftDeletes`.

## Policy

`app/Policies/InvestmentPaymentBatchPolicy.php` (Shield, `*_investment::payment::batch`). El recurso Filament es de solo lectura: `canCreate`, `canEdit`, `canDelete`, `canDeleteAny` devuelven `false`.

## Dónde se usa

- [[Lotes de pago]] · [[Aprobacion del CEO]] · [[Revision del PM]] · [[Cancelacion automatica de borradores]]

## Relacionado
- [[Modelo de datos]] · [[InvestmentPaymentRequest]] · [[Estados]]
