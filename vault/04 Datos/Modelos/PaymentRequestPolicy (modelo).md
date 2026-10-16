---
tipo: modelo
aliases:
  - Política de ventanas de pago (modelo)
  - PaymentRequestPolicy modelo
tags:
  - payment-request/datos
  - payment-request/modelos
fuentes:
  - app/Models/PaymentRequestPolicy.php
  - app/Filament/Pages/PaymentPolicySettings.php
  - database/migrations/2026_06_11_230723_create_payment_request_policies_table.php
actualizado: 2026-10-15
---

# PaymentRequestPolicy (modelo)

Registro único (singleton) con la configuración de las ventanas de captura y envío de pagos de inversión, la fecha de provisión objetivo y la cancelación automática de borradores.

> [!warning] No confundir
> `App\Models\PaymentRequestPolicy` es un **modelo** de configuración. `App\Policies\PaymentRequestPolicy` es la **policy de autorización** de [[PaymentRequest]]. Ver [[Policies]].

## Tabla `payment_request_policies`

| Columna | Tipo | Default | Uso |
|---|---|---|---|
| `capture_window_is_active` | boolean | `true` | Ventana de captura (crear/editar borradores) |
| `capture_open_day` / `capture_open_time` | string / time | `monday` / `08:00:00` | |
| `capture_close_day` / `capture_close_time` | string / time | `wednesday` / `18:00:00` | |
| `submit_window_is_active` | boolean | `false` | Ventana de envío al CEO |
| `submit_open_day` / `submit_open_time` | string / time | `monday` / `08:00:00` | |
| `submit_close_day` / `submit_close_time` | string / time | `wednesday` / `18:00:00` | |
| `cancellation_activated_at` | timestamp | null | Solo se cancelan borradores creados después de esta marca |
| `provision_target_day` | string | `tuesday` | Día de provisión del pago |
| `provision_target_week_offset` | tinyint | `1` | Semanas hacia adelante |
| `override_role_names` | json | `["super_admin"]` | Roles que ignoran la ventana |
| `editor_role_names` | json | `["super_admin","project_manager"]` | Roles que editan la política |
| `warning_minutes_before_close` | tinyint | `5` | Aviso previo al cierre |
| `capture_window_snoozed_until` / `capture_window_snooze_reason` | timestamp / string | null | Extensión temporal |
| `submit_window_snoozed_until` / `submit_window_snooze_reason` | timestamp / string | null | Extensión temporal |
| `holidays` | json | null | Reservado |

## Casts

Booleans para `*_is_active`; horas como `string`; `cancellation_activated_at` y `*_snoozed_until` → `datetime`; `override_role_names`, `editor_role_names`, `holidays` → `array`; `warning_minutes_before_close`, `provision_target_week_offset` → `integer`.

## Métodos

- `static current(): self` — devuelve el primer registro; si no existe, lo **crea** con los defaults explícitos de la tabla anterior.

## Traits y auditoría

Sin traits (`LogsActivity` no está activo) y no está registrado en filament-logger: los cambios a la política no quedan en `activity_log`. Los intentos bloqueados sí quedan en [[PaymentCaptureAttemptLog]].

## Acceso

Se edita en la página Filament `PaymentPolicySettings`, accesible solo para `super_admin` y `project_manager` (`canAccess()`). Sin policy de autorización asociada.

## Dónde se usa

- [[Politica de ventanas de pago]] · [[Cancelacion automatica de borradores]] · [[Lotes de pago]]

## Relacionado
- [[Modelo de datos]] · [[PaymentCaptureAttemptLog]] · [[Comandos y scheduler]]
