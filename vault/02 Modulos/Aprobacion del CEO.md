---
tipo: modulo
aliases:
  - Aprobación del CEO
tags:
  - payment-request/modulos
  - payment-request/inversion
  - payment-request/aprobaciones
fuentes:
  - app/Http/Controllers/InvestmentBatchApprovalController.php
  - app/Models/InvestmentPaymentBatch.php
  - app/Notifications/InvestmentBatchSubmittedNotification.php
  - app/Notifications/InvestmentBatchRequesterSummaryNotification.php
  - app/Notifications/InvestmentBatchReadyForReviewNotification.php
  - resources/js/pages/investment-batch-approval/
  - routes/web.php
actualizado: 2026-10-15
---

# Aprobación del CEO

Primer paso de autorización de los pagos de inversión: el CEO revisa un lote enviado, aprueba o rechaza pago por pago, desde una **página pública con token** (no requiere iniciar sesión).

## Rutas públicas

Todas fuera del middleware `auth`, dentro de `throttle:10,1` (10 peticiones por minuto):

| Ruta | Método | Página |
|---|---|---|
| `/investment-batch-approval/{token}` | `show` | `investment-batch-approval/show.tsx` o `invalid.tsx` |
| `/investment-batch-approval/{token}/review` | `review` (POST) | redirige a `success` |
| `/investment-batch-approval/success` | `success` | `investment-batch-approval/success.tsx` |

## Token y expiración

- Se genera al enviar el lote: `ceo_approval_token` = UUID, `ceo_approval_token_expires_at` = ahora + **96 horas** (`InvestmentPaymentBatchController::submit()`).
- `InvestmentPaymentBatch::hasValidCeoToken()`: token no nulo y expiración en el futuro.
- Al revisar, el token y su expiración se ponen en `null` (un solo uso).

`show()` muestra `invalid.tsx` con el motivo resuelto por `resolveInvalidReason()`:

| Situación | Mensaje |
|---|---|
| No existe lote con ese token | "El enlace de aprobación no es válido o no existe." |
| El lote ya no está `submitted` | "Este lote ya fue procesado anteriormente." |
| Token expirado | "El enlace de aprobación ha expirado." |

`review()` responde **410** si el token no es válido, expiró o el lote no está `submitted`.

> [!warning] Qué pasa al expirar
> No hay proceso que reenvíe el enlace ni que cambie el estado del lote al expirar: los pagos se quedan en `submitted`. Ver punto por confirmar abajo.

## Pantalla del CEO

Muestra datos del lote (departamento, proyecto, solicitante, semana/año, fecha de envío y de expiración) y la tabla de pagos `submitted` con folio, concepto, proveedor, RFC, descripción, estrategia (`strategySummary()`), subtotal, IVA, total y fecha de programación. Todos los pagos vienen marcados para aprobar; el CEO desmarca los que rechaza y puede escribir **un motivo de rechazo único** que se aplica a todos los rechazados.

## Procesamiento (`review`)

En transacción:

1. Pagos en `approved_uuids` → `ceo_approved` + `ceo_reviewed_at`.
2. Resto de pagos `submitted` del lote → `ceo_rejected` + `ceo_rejection_reason` + `ceo_reviewed_at`.
3. Lote → `ceo_approved` si hubo al menos una aprobación, si no `ceo_rejected`; limpia el token.

Después:

- **Un correo por solicitante** (`InvestmentBatchRequesterSummaryNotification`, contexto `ceo`) con sus pagos aprobados y rechazados. Los rechazados liberan presupuesto.
- Si hubo aprobados: correo a **todos** los usuarios con rol `project_manager` (`InvestmentBatchReadyForReviewNotification`). El correo lista los pagos nuevos de este lote y los pendientes anteriores del **mismo proyecto** (`ceo_approved` / `projectmanager_review`), con liga a `/investment-payment-review`.
- Redirige a `success` con los conteos de aprobados y rechazados.

> [!question] Por confirmar
> - Un lote con token expirado queda indefinidamente en `submitted`, con sus pagos comprometiendo presupuesto. ¿Existe un procedimiento operativo para reenviar o cancelar estos lotes?
> - El correo al PM se envía a todos los usuarios con rol `project_manager`, sin filtrar por proyecto asignado.

## Relacionado
- [[Lotes de pago]] · [[Revision del PM]] · [[Flujo de pago de inversion]]
- [[Catalogo de correos]] · [[Roles y actores]] · [[Middleware y rutas protegidas]]
- [[InvestmentPaymentBatch]] · [[InvestmentPaymentRequest]]
