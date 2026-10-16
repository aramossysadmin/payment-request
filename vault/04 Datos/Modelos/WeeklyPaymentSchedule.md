---
tipo: modelo
aliases:
  - Programación semanal de pagos
tags:
  - payment-request/datos
  - payment-request/modelos
fuentes:
  - app/Models/WeeklyPaymentSchedule.php
  - app/Http/Controllers/WeeklyPaymentScheduleController.php
  - app/Services/WeeklyPaymentScheduleApprovalService.php
  - database/migrations/2026_04_16_160908_create_weekly_payment_schedules_table.php
actualizado: 2026-10-15
---

# WeeklyPaymentSchedule

Programación de pagos de una semana: lista de pagos de inversión listos que se incluyen (o excluyen) para pago en banco y que requiere autorización por correo.

## Tabla `weekly_payment_schedules`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint | PK |
| `uuid` | uuid, único | Route key |
| `week_number`, `year` | smallint | Único compuesto `week_number + year` |
| `created_by` | FK → `users` | |
| `status` | string, default `draft` | `pending_approval` al crearse; `approved` / `rejected` al responder |
| `created_at`, `updated_at`, `deleted_at` | timestamps | Soft deletes |

> [!warning] Una programación por semana
> El índice único `week_number + year` es global, no por proyecto. Por confirmar si es intencional, ya que la pantalla filtra por proyecto.

## Casts y fillable

- Casts: `week_number`, `year` → `integer`.
- Fillable: `uuid`, `week_number`, `year`, `created_by`, `status`.

## Relaciones

| Método | Tipo | Destino |
|---|---|---|
| `creator()` | BelongsTo | [[User]] (`created_by`) |
| `items()` | HasMany | [[WeeklyPaymentScheduleItem]] |
| `approvals()` | HasMany | [[WeeklyPaymentScheduleApproval]] |

## Ciclo

1. `WeeklyPaymentScheduleController::store()` crea la programación en `pending_approval` con sus ítems; bloquea si algún pago es de proyecto cerrado. Los pagos excluidos se posponen a la semana siguiente.
2. `WeeklyPaymentScheduleApprovalService::createApproval()` crea la aprobación para el autorizador configurado en `investment-requests.authorizer_email` (token 48 h).
3. Al aprobar: programación `approved` y los pagos incluidos pasan a `scheduled_for_bank`. Al rechazar: programación `rejected`, los pagos no cambian.

## Eventos

`booted()` → `creating`: genera `uuid`. `getRouteKeyName()` → `uuid`.

## Traits

`HasFactory`, `LogsActivity`, `SoftDeletes`.

## Policy

Ninguna. El acceso se controla en el controlador con `InvestmentRequest::visibleTo()` por proyecto.

## Dónde se usa

- [[Programacion semanal]] · [[Comprobantes de pago]] · [[Exportaciones Excel]]

## Relacionado
- [[Modelo de datos]] · [[Estados]] · [[InvestmentPaymentRequest]]
