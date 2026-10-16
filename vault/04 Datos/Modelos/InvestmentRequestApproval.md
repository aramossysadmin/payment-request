---
tipo: modelo
aliases:
  - Aprobación de solicitud de inversión
tags:
  - payment-request/datos
  - payment-request/modelos
fuentes:
  - app/Models/InvestmentRequestApproval.php
  - app/Services/InvestmentApprovalService.php
  - database/migrations/2026_04_10_000002_create_investment_request_approvals_table.php
actualizado: 2026-10-15
---

# InvestmentRequestApproval

Registro de aprobación de una solicitud de inversión: quién debe autorizarla, con qué token de correo y qué respondió.

## Tabla `investment_request_approvals`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint | PK |
| `investment_request_id` | FK | `cascadeOnDelete` |
| `user_id` | FK → `users` | Aprobador |
| `stage` | string, default `department` | Hoy siempre `department` |
| `level` | tinyint, default `1` | Hoy siempre 1 |
| `status` | string, default `pending` | `pending` / `approved` / `rejected` |
| `comments` | text, nullable | Primera letra en mayúscula |
| `responded_at` | timestamp | |
| `approval_token` | string, nullable, único | Enlace del correo (48 h) |
| `approval_token_expires_at` | timestamp | |
| `created_at`, `updated_at` | timestamps | Sin soft deletes |

## Casts y fillable

- Casts: `responded_at`, `approval_token_expires_at` → `datetime`.
- Fillable: `investment_request_id`, `user_id`, `stage`, `level`, `status`, `comments`, `responded_at`. El token se asigna directamente (`$approval->approval_token = ...`).

## Relaciones

| Método | Tipo | Destino |
|---|---|---|
| `investmentRequest()` | BelongsTo | [[InvestmentRequest]] |
| `user()` | BelongsTo | [[User]] |

## Métodos

`hasValidToken()`, `isTokenExpired()`, `isPending()`, `isApproved()`, `isRejected()`.

## Cómo se crean (`InvestmentApprovalService::createApprovals()`)

1. Proyecto con `requires_pm_approval = true` → una aprobación a nombre del primer usuario con rol `project_manager`; se notifica a todos los PM. Si no hay PM, cae al autorizador global.
2. `config('investment-requests.require_authorization') = false` → aprobación automática registrada a nombre del solicitante (`status = approved`, comentario de aprobación automática) y la solicitud pasa a `completed`.
3. Por defecto → una aprobación al usuario cuyo correo está en `config('investment-requests.authorizer_email')` (variable `INVESTMENT_AUTHORIZER_EMAIL`).

## Traits

`HasFactory`, `LogsActivity`.

## Policy

Ninguna propia; el acceso se resuelve por token en `EmailApprovalController` o por `InvestmentRequestPolicy`.

## Dónde se usa

- [[Solicitudes de inversion]] · [[Flujo de solicitud de inversion]] · [[Catalogo de correos]]

## Relacionado
- [[Modelo de datos]] · [[InvestmentRequest]] · [[Roles y actores]]
