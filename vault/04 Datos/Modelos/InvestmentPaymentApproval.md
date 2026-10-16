---
tipo: modelo
aliases:
  - Aprobación de pago de inversión (legacy)
tags:
  - payment-request/datos
  - payment-request/modelos
fuentes:
  - app/Models/InvestmentPaymentApproval.php
  - app/Services/InvestmentPaymentApprovalService.php
  - app/Http/Controllers/EmailApprovalController.php
  - database/migrations/2026_04_15_182521_create_investment_payment_approvals_table.php
actualizado: 2026-10-15
---

# InvestmentPaymentApproval

Registro de aprobación individual (por correo con token) de un pago de inversión. Pertenece al **flujo anterior** de pagos de inversión, previo a los lotes ([[InvestmentPaymentBatch]]).

## Tabla `investment_payment_approvals`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint | PK |
| `investment_payment_request_id` | FK → `investment_payment_requests` | `cascadeOnDelete` |
| `user_id` | FK → `users` | Aprobador |
| `status` | string, default `pending` | `pending` / `approved` / `rejected` |
| `comments` | text, nullable | |
| `responded_at` | timestamp, nullable | |
| `approval_token` | uuid, nullable, único | Token del enlace del correo |
| `approval_token_expires_at` | timestamp, nullable | |
| `created_at`, `updated_at` | timestamps | Sin soft deletes |

## Casts y fillable

- Casts: `responded_at`, `approval_token_expires_at` → `datetime`.
- Fillable: todas las columnas de negocio, incluido `approval_token`.

## Relaciones

| Método | Tipo | Destino |
|---|---|---|
| `investmentPaymentRequest()` | BelongsTo | [[InvestmentPaymentRequest]] |
| `user()` | BelongsTo | [[User]] |

## Métodos

`hasValidToken()`, `isTokenExpired()`, `isPending()`, `isApproved()`, `isRejected()`.

## Estado actual

- `InvestmentPaymentApprovalService::approve()` / `reject()` cambian el pago a `approved` / `rejected` (estados legacy) y siguen invocándose desde `EmailApprovalController` cuando se abre un token existente.
- `InvestmentPaymentApprovalService::createApproval()` **no tiene llamadas** en `app/`: hoy no se generan aprobaciones nuevas de este tipo.

> [!question] Por confirmar
> Confirmar si quedan tokens vigentes de este tipo o si el modelo ya es solo histórico.

## Traits

`LogsActivity` (sin `HasFactory`, sin `SoftDeletes`).

## Policy

Ninguna.

## Dónde se usa

- [[Solicitudes de pago de inversion]] (histórico) · [[Flujo de pago de inversion]]

## Relacionado
- [[Modelo de datos]] · [[Estados]] · [[InvestmentPaymentRequest]]
