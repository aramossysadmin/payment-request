---
tipo: decision
aliases:
  - ADR-009 Política de ventanas como página singleton
tags:
  - payment-request/decisiones
fuentes:
  - app/Filament/Pages/PaymentPolicySettings.php
  - app/Models/PaymentRequestPolicy.php
  - app/Services/PaymentRequestPolicyService.php
  - routes/console.php
  - git ef876ad
  - git 9de05cf
  - git 1f44f53
  - git b9456f7
estado: vigente
actualizado: 2026-10-15
---

# ADR-009 Política de ventanas como página singleton

La política de ventanas de captura y envío de pagos es un registro único (singleton) que se edita desde una página de Filament, no desde un Resource.

## Contexto
Se necesitaba restringir cuándo se capturan y envían pagos de inversión (ventanas semanales), con excepciones por rol, prórroga de emergencia (snooze), cancelación automática de borradores y auditoría de intentos bloqueados. Un primer intento con `Resource` + `EditRecord` falló porque Filament exige `{record}` en la URL.

## Decisión
- Tabla `payment_request_policies` con un solo registro, obtenido con `PaymentRequestPolicy::current()`.
- `PaymentRequestPolicyService` como única fuente de verdad (con caché de 60 s) para `canCapture` / `canSubmit` y fechas de apertura y cierre.
- Edición en `app/Filament/Pages/PaymentPolicySettings.php` (`Filament\Pages\Page` + `InteractsWithForms`), ruta `/admin/payment-policy-settings`; el guardado invalida la caché.
- Validación en backend (form requests y envío de lote) además de la UI.
- Comandos programados en `routes/console.php`: cancelación automática de borradores cada minuto y recordatorio de cierre cada 5 min.

## Consecuencias
- Patrón reutilizable: toda configuración singleton futura debe ser una `Page`, no un `Resource`.
- La lógica de ventanas depende de la zona horaria de CDMX; los tests de captura son sensibles a la hora de ejecución.

## Fecha
2026-06-11 (backend y página) · 2026-06-12 (frontend y cancelación automática)

## Fuentes
- Commits `ef876ad`, `9de05cf`, `1f44f53`, `200f018`, `b9456f7`.

## Relacionado
- [[Indice de decisiones]] · [[Politica de ventanas de pago]] · [[PaymentRequestPolicy (modelo)]] · [[Cancelacion automatica de borradores]] · [[Panel Filament]]
