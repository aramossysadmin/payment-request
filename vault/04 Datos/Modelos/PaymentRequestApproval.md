---
tipo: modelo
aliases:
  - Aprobación de solicitud de pago (legacy)
tags:
  - payment-request/datos
  - payment-request/modelos
fuentes:
  - app/Models/PaymentRequestApproval.php
  - app/Services/ApprovalService.php
  - database/migrations/2026_02_16_181102_create_payment_request_approvals_table.php
  - database/migrations/2026_04_09_182810_add_authorizer_levels_to_departments_and_approvals.php
actualizado: 2026-10-15
---

# PaymentRequestApproval

Registro de aprobación por etapa y nivel de una solicitud de pago legacy.

## Tabla `payment_request_approvals`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint | PK |
| `payment_request_id` | FK | `cascadeOnDelete` |
| `user_id` | FK → `users` | Autorizador |
| `stage` | string, default `department` | `department` / `administration` / `treasury` |
| `level` | tinyint, default `1` | 1 o 2 |
| `status` | string, default `pending` | `pending` / `approved` / `rejected` |
| `comments` | text, nullable | |
| `responded_at` | timestamp | |
| `approval_token` | string, nullable, único | Enlace por correo (48 h) |
| `approval_token_expires_at` | timestamp | |
| `created_at`, `updated_at` | timestamps | |

El índice único `payment_request_id + user_id (+ stage)` se eliminó en la migración de niveles para permitir varias aprobaciones del mismo usuario.

## Casts y fillable

- Casts: `responded_at`, `approval_token_expires_at` → `datetime`.
- Fillable: `payment_request_id`, `user_id`, `stage`, `level`, `status`, `comments`, `responded_at`.

## Relaciones

| Método | Tipo | Destino |
|---|---|---|
| `paymentRequest()` | BelongsTo | [[PaymentRequest]] |
| `user()` | BelongsTo | [[User]] |

## Métodos

`hasValidToken()`, `isTokenExpired()`, `isPending()`, `isApproved()`, `isRejected()`.

## Lógica (`ApprovalService`)

- Al crear: aprobación `department` nivel 1 al `authorizerLevel1` del departamento.
- Al aprobar nivel 1: si el departamento tiene `authorizer_level_2_id` se crea nivel 2; si no, transiciona a la siguiente etapa y crea la aprobación nivel 1 de esa etapa (departamentos `ADMINISTRACIÓN` y `TESORERÍA`).
- Rechazo nivel 1: notifica al solicitante. Rechazo nivel 2: reabre la aprobación nivel 1 con token nuevo.

## Traits

`HasFactory`, `LogsActivity`.

## Dónde se usa

- [[Modulo legacy Payment Requests]] · [[Flujo legacy de solicitud de pago]]

## Relacionado
- [[Modelo de datos]] · [[PaymentRequest]] · [[Department]]
