---
tipo: modelo
aliases:
  - Aprobación de programación semanal
tags:
  - payment-request/datos
  - payment-request/modelos
fuentes:
  - app/Models/WeeklyPaymentScheduleApproval.php
  - app/Services/WeeklyPaymentScheduleApprovalService.php
  - database/migrations/2026_04_16_160910_create_weekly_payment_schedule_approvals_table.php
actualizado: 2026-10-15
---

# WeeklyPaymentScheduleApproval

Aprobación por correo (token) de una programación semanal de pagos.

## Tabla `weekly_payment_schedule_approvals`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint | PK |
| `weekly_payment_schedule_id` | FK | `cascadeOnDelete` |
| `user_id` | FK → `users` | Aprobador |
| `status` | string, default `pending` | `pending` / `approved` / `rejected` |
| `comments` | text, nullable | Motivo de rechazo |
| `approval_token` | string, nullable, único | 48 h |
| `approval_token_expires_at` | timestamp | |
| `responded_at` | timestamp | |
| `created_at`, `updated_at` | timestamps | |

## Casts y fillable

- Casts: `approval_token_expires_at`, `responded_at` → `datetime`.
- Fillable: todas las columnas de negocio, incluido el token.

## Relaciones

| Método | Tipo | Destino |
|---|---|---|
| `schedule()` | BelongsTo | [[WeeklyPaymentSchedule]] |
| `user()` | BelongsTo | [[User]] |

## Métodos

`hasValidToken()`, `isTokenExpired()`, `isPending()`, `isApproved()`, `isRejected()`. Al responder, el servicio limpia el token.

## Traits

`HasFactory`, `LogsActivity`.

## Dónde se usa

- [[Programacion semanal]] · [[Catalogo de correos]]

## Relacionado
- [[Modelo de datos]] · [[WeeklyPaymentSchedule]]
