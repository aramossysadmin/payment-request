---
tipo: modelo
aliases:
  - Bitácora de intentos de captura
tags:
  - payment-request/datos
  - payment-request/modelos
fuentes:
  - app/Models/PaymentCaptureAttemptLog.php
  - app/Services/PaymentPolicyAuditService.php
  - database/migrations/2026_06_11_230816_create_payment_capture_attempt_logs_table.php
actualizado: 2026-10-15
---

# PaymentCaptureAttemptLog

Bitácora de intentos de capturar, editar o enviar pagos fuera de la ventana permitida por la política de pagos, y de extensiones (snooze) de ventana.

## Tabla `payment_capture_attempt_logs`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint | PK |
| `user_id` | FK → `users` | `cascadeOnDelete` |
| `attempted_at` | timestamp | |
| `action` | string, indexado | `create` / `edit` / `submit` / `snooze_extended` |
| `was_blocked` | boolean, default `true` | `true` = intento bloqueado |
| `ip_address` | string, nullable | |
| `policy_snapshot` | json, nullable | Copia de la política en ese momento |
| `notes` | string, nullable | |
| `created_at`, `updated_at` | timestamps | |

Índice compuesto `user_id + attempted_at`.

## Casts y fillable

- Casts: `attempted_at` → `datetime`, `was_blocked` → `boolean`, `policy_snapshot` → `array`.
- Fillable: todas las columnas de negocio.

## Relaciones

| Método | Tipo | Destino |
|---|---|---|
| `user()` | BelongsTo | [[User]] |

## Quién escribe

`PaymentPolicyAuditService::logBlockedAttempt()` (p. ej. desde `InvestmentPaymentBatchController::submit()` cuando la ventana de envío está cerrada) y `logSnoozeExtended()`.

## Traits

Ninguno (sin `LogsActivity`, sin factory, sin soft deletes). No tiene policy ni recurso Filament.

## Dónde se usa

- [[Politica de ventanas de pago]]

## Relacionado
- [[Modelo de datos]] · [[PaymentRequestPolicy (modelo)]] · [[Auditoria y activity log]]
