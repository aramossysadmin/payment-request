---
tipo: modulo
aliases:
  - Revisión del PM
  - Revisión de Pagos
  - Aprobación final del PM
tags:
  - payment-request/modulos
  - payment-request/inversion
  - payment-request/aprobaciones
fuentes:
  - app/Http/Controllers/InvestmentPaymentReviewController.php
  - app/Notifications/InvestmentBatchRequesterSummaryNotification.php
  - app/Notifications/InvestmentBatchFinalApprovalSummaryToPmNotification.php
  - resources/js/pages/investment-payment-review/index.tsx
  - resources/views/pdf/final-approval-summary-pm.blade.php
  - routes/web.php
actualizado: 2026-10-15
---

# Revisión del PM

Segundo y **último** paso de autorización de los pagos de inversión. El Project Manager revisa por proyecto los pagos que aprobó el CEO, puede **reducir el monto aprobado** y su decisión es la **aprobación FINAL**: los pagos aprobados quedan en `final_approved` listos para cargar documentos.

> [!info] Ya no existe la aprobación final del CEO
> Desde el commit "Pagos de Inversión: eliminar paso Aprobación final del CEO" (julio 2026) el PM es el paso terminal. Los estados `final_pending` y las columnas `final_ceo_*` / `final_session_*` de los lotes quedaron como históricos.

## Rutas

Grupo con middleware `role:project_manager|super_admin` (dentro de `auth`, `verified`):

| Ruta | Método | Uso |
|---|---|---|
| `GET investment-payment-review` | `index` | Pantalla "Revisión de Pagos" (`investment-payment-review/index.tsx`) |
| `POST investment-payment-review` | `submit` | Guarda las decisiones |

## Pantalla

1. Selector **Hoja de Inversión**: proyectos visibles según `InvestmentRequest::visibleTo()`. Sin proyecto seleccionado (o uno no visible) se muestra estado vacío.
2. Con proyecto: pagos en `ceo_approved` o `projectmanager_review` cuyas solicitudes pertenecen al proyecto, **agrupados por departamento** con conteo y total.
3. Por pago: folio, concepto, proyecto, proveedor, RFC, solicitante, sucursal, estrategia (resumen y desglose `strategyBreakdown()`), subtotal/IVA/total, fecha y semana de pago, y el presupuesto del concepto (presupuesto, comprometido, pagado, disponible) calculado una vez por grupo de concepto con `budgetBreakdown()`.
4. Documentos adjuntos con URL firmada de 48 h (`documents.view`).
5. Cada pago inicia como **Aprobar** con monto = total solicitado. El PM puede desmarcarlo (rechazo) o editar el campo "Aprobar Monto".
6. Un **motivo de rechazo único** para todos los rechazados.
7. El botón de envío se deshabilita si hay errores de monto o el proyecto está cerrado.

## Reglas al guardar (`submit`)

Validación:

| Campo | Regla |
|---|---|
| `decisions` | Arreglo, mínimo 1 |
| `decisions.*.uuid` | UUID obligatorio |
| `decisions.*.approved` | Booleano obligatorio |
| `decisions.*.approved_amount` | Numérico ≥ 0, opcional (default = `total`) |
| `rejection_reason` | Opcional, máx. 1000 |

- Solo procesa pagos en `ceo_approved` / `projectmanager_review`; si ninguno coincide, error.
- Si algún pago pertenece a un proyecto cerrado, **no procesa nada** y lista los proyectos cerrados.
- **Monto aprobado:** se compara en centavos; debe ser > 0 y **≤ total solicitado**. El PM puede reducir, nunca aumentar.

Transacción:

- Aprobado → `status = final_approved`, `approved_amount`, `pm_reviewed_at`, `final_reviewed_at`.
- Rechazado → `status = projectmanager_rejected`, `pm_rejection_reason`, `pm_reviewed_at` (libera presupuesto).
- Por cada lote afectado: si ya no quedan pagos pendientes de PM, el lote pasa a `final_approved` (si hay al menos uno aprobado, con `final_ceo_reviewed_at`) o a `projectmanager_rejected`.

## Correos

| Destinatario | Notificación | Contenido |
|---|---|---|
| Cada solicitante (uno por persona) | `InvestmentBatchRequesterSummaryNotification` con contexto `final` | Aprobados y rechazados; si hubo aprobados indica que debe subir documentos |
| Todos los usuarios con rol `project_manager` (incluye a quien aprobó), uno por proyecto con aprobados | `InvestmentBatchFinalApprovalSummaryToPmNotification` | Resumen por departamento con totales por moneda usando `approved_amount`, número de rechazados y **PDF adjunto** (`pdf.final-approval-summary-pm`, carta horizontal, archivo `aprobacion-final-{proyecto}-{AAAAMMDD-HHMM}.pdf`) |

## Efecto del monto ajustado

El `approved_amount` es el que se usa en la programación semanal, su exportación y el PDF/correo del PM (`approved_amount ?? total`). El consolidado marca el pago como "ajustado" (`was_adjusted`) cuando es menor al total.

> [!question] Por confirmar
> - El presupuesto (`COMMITTED_STATUSES` / `PAID_STATUSES`) suma `investment_payment_requests.total`, no `approved_amount`. Una reducción del PM no libera la diferencia en el saldo del concepto. ¿Es intencional?
> - `submit()` no verifica que el PM tenga acceso al proyecto de cada pago enviado; solo exige el rol. La pantalla sí filtra por proyectos visibles.
> - El correo con PDF se envía a todos los PMs, sin filtrar por proyecto asignado.

## Relacionado
- [[Aprobacion del CEO]] · [[Documentos de pago]] · [[Flujo de pago de inversion]] · [[Lotes de pago]]
- [[Presupuesto por concepto]] · [[Estrategias]] · [[Catalogo de correos]] · [[PDFs]]
- [[Roles y actores]] · [[InvestmentPaymentRequest]]
