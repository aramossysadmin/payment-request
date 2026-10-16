---
tipo: modelo
aliases:
  - Partida de programación semanal
tags:
  - payment-request/datos
  - payment-request/modelos
fuentes:
  - app/Models/WeeklyPaymentScheduleItem.php
  - database/migrations/2026_04_16_160909_create_weekly_payment_schedule_items_table.php
actualizado: 2026-10-15
---

# WeeklyPaymentScheduleItem

Renglón de una programación semanal: un pago de inversión y si quedó incluido o excluido (con motivo).

## Tabla `weekly_payment_schedule_items`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint | PK |
| `weekly_payment_schedule_id` | FK | `cascadeOnDelete` |
| `investment_payment_request_id` | FK | `cascadeOnDelete` |
| `included` | boolean, default `true` | |
| `exclusion_reason` | text, nullable | Solo si `included = false` |
| `created_at`, `updated_at` | timestamps | |

Índice único `schedule_payment_unique` (`weekly_payment_schedule_id + investment_payment_request_id`).

## Casts y fillable

- Casts: `included` → `boolean`.
- Fillable: `weekly_payment_schedule_id`, `investment_payment_request_id`, `included`, `exclusion_reason`.

## Relaciones

| Método | Tipo | Destino |
|---|---|---|
| `schedule()` | BelongsTo | [[WeeklyPaymentSchedule]] |
| `investmentPaymentRequest()` | BelongsTo | [[InvestmentPaymentRequest]] |

## Traits

`HasFactory`, `LogsActivity`.

## Dónde se usa

- [[Programacion semanal]]

## Relacionado
- [[Modelo de datos]] · [[WeeklyPaymentSchedule]]
