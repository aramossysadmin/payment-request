---
tipo: modulo
aliases:
  - Lotes de pago
  - Batches de pago
tags:
  - payment-request/modulos
  - payment-request/inversion
fuentes:
  - app/Models/InvestmentPaymentBatch.php
  - app/Http/Controllers/InvestmentPaymentRequestController.php
  - app/Http/Controllers/InvestmentPaymentBatchController.php
  - app/Notifications/InvestmentBatchSubmittedNotification.php
  - app/Filament/Resources/InvestmentPaymentBatchResource.php
actualizado: 2026-10-15
---

# Lotes de pago

Un lote (`InvestmentPaymentBatch`) agrupa los pagos de inversión de un **departamento + proyecto + semana ISO**. El lote en borrador es el contenedor donde se capturan los pagos; al enviarlo, los pagos seleccionados viajan juntos a la [[Aprobacion del CEO]].

## Campos relevantes

| Campo | Uso |
|---|---|
| `uuid` | Llave de ruta (generada en `creating`) |
| `department_id`, `project_id` | Heredados de la solicitud de inversión, no del usuario |
| `user_id` | Quien creó el lote (primer pago) |
| `week_number`, `year` | Semana ISO y año ISO **del momento de captura** |
| `status` | `draft`, `submitted`, `ceo_approved`, `ceo_rejected`, `final_approved`, `projectmanager_rejected`, `auto_cancelled` |
| `submitted_at` | Fecha de envío |
| `ceo_approval_token`, `ceo_approval_token_expires_at`, `ceo_reviewed_at` | Enlace público del CEO (ver [[Aprobacion del CEO]]) |
| `final_ceo_*`, `final_session_*` | Columnas del paso "aprobación final del CEO", eliminado del flujo; `final_ceo_reviewed_at` se sigue llenando al cerrar el lote como `final_approved` |
| `deadline_at` | Solo se muestra en Filament; ningún código lo asigna |

## Creación: `findOrCreateActiveBatch()`

Método privado de `InvestmentPaymentRequestController`, invocado en cada captura:

```php
InvestmentPaymentBatch::query()
    ->where('department_id', $investmentRequest->department_id)
    ->where('project_id', $investmentRequest->project_id)
    ->where('week_number', $now->isoWeek)
    ->where('year', $now->isoWeekYear)
    ->where('status', 'draft')
    ->firstOrCreate([...], ['user_id' => $user->id]);
```

- La semana del lote es la **semana ISO actual** (cuando se captura), no la de `payment_provision_date`.
- Hay como máximo un lote draft "activo" por departamento + proyecto + semana; varios usuarios del mismo departamento comparten el lote.

## Envío: `InvestmentPaymentBatchController::submit()`

Ruta `POST investment-payment-batches/{uuid}/submit`.

1. Autorización: el creador del lote o cualquier miembro del departamento (`belongsToDepartment`). 409 si el lote ya no es `draft`.
2. 403 si el proyecto está cerrado.
3. **Ventana de envío** (`canSubmitNow`); si está cerrada registra el intento (`logBlockedAttempt(…, 'submit')`) y devuelve error con la próxima apertura. Ver [[Politica de ventanas de pago]].
4. Valida `payment_uuids` (mínimo 1).
5. En transacción:
   - Seleccionados → `submitted` (se quedan en el lote).
   - No seleccionados → se mueven a un **lote draft nuevo** con mismo departamento, proyecto, semana y año.
   - Lote → `submitted`, `submitted_at = now()`, `ceo_approval_token` = UUID, expiración **+96 horas**.
6. Notifica a **todos** los usuarios con rol `ceo` (`InvestmentBatchSubmittedNotification`, solo correo, en cola). Si nadie tiene el rol no se envía nada y no hay error.

> [!info] Asunto del correo al CEO
> "Lote pendiente — {proyecto} · {departamento} ({n} pagos)", con botón al enlace público `/investment-batch-approval/{token}`.

## Eliminar un pago del borrador: `destroyPayment()`

Ruta `DELETE investment-payment-batches/payments/{uuid}`:

- Solo el dueño del pago y solo en `draft` (403 en otro caso).
- 403 si el proyecto está cerrado.
- `$payment->delete()` es **soft delete** (el modelo usa `SoftDeletes`): el registro permanece en BD con `deleted_at`.

## Cierre del lote

| Momento | Estado del lote | Dónde |
|---|---|---|
| CEO aprueba al menos un pago | `ceo_approved` | `InvestmentBatchApprovalController::review()` |
| CEO rechaza todos | `ceo_rejected` | Igual |
| PM termina de revisar todos los pagos del lote y hay al menos un aprobado | `final_approved` (+ `final_ceo_reviewed_at`) | `InvestmentPaymentReviewController::submit()` |
| PM rechaza todos | `projectmanager_rejected` | Igual |
| Cancelación automática deja el lote sin pagos activos | `auto_cancelled` | `CancelExpiredPaymentDraftsCommand` |

## Consulta en Filament

`InvestmentPaymentBatchResource` permite consultar lotes y sus pagos con etiquetas y colores por estado (incluye estados históricos como `final_pending`). Ver [[Panel Filament]].

> [!question] Por confirmar
> El mapa de etiquetas de Filament (`InvestmentPaymentBatchResource::statusLabel()`) no incluye `auto_cancelled`; se muestra el valor crudo.

## Relacionado
- [[Solicitudes de pago de inversion]] · [[Aprobacion del CEO]] · [[Revision del PM]] · [[Cancelacion automatica de borradores]]
- [[Consolidado por proyecto]] · [[Flujo de pago de inversion]] · [[Politica de ventanas de pago]]
- [[InvestmentPaymentBatch]]
