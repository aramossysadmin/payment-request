---
name: Architecture Decisions
description: Decisiones de arquitectura importantes del proyecto y su justificacion
type: project
---

# Architecture Decisions

## 2026-07-21 — Candado por proyecto para Solicitudes de Inversión: precedencia sobre bypass global

**Decisión:** El campo `projects.requires_pm_approval` (boolean, toggle en `/admin/projects/{id}/edit`) tiene **prioridad sobre** el bypass global `INVESTMENT_REQUEST_REQUIRE_AUTHORIZATION`. Cuando un proyecto tiene el candado activo, sus nuevas Solicitudes de Inversión SIEMPRE van al PM (rol `project_manager`), aunque el bypass global esté en `false`.

**Orden de precedencia en `InvestmentApprovalService::createApprovals()`:**
1. `project->requires_pm_approval == true` → correo a todos los usuarios con rol `project_manager` (approval `pending`, token 48h). Con fallback a `authorizer_email` si no hay ningún PM.
2. `config('investment-requests.require_authorization') == false` → `autoApprove()`.
3. Default → correo al `authorizer_email` global.

**Cambio semántico:** `INVESTMENT_REQUEST_REQUIRE_AUTHORIZATION` dejó de ser "kill switch absoluto". Ahora es "default para proyectos SIN candado". El nombre en `.env` se conservó por retro-compatibilidad (para no requerir editar el `.env` de producción).

**Razón:** La feature se necesita activar por proyecto sin depender de intervención en `.env` de producción (que requiere SSH y coordinación). Con esta precedencia:
- Deploy es transparente — nada cambia hasta que Yazmin active el toggle en algún proyecto desde admin.
- Yazmin controla su propio flujo (autonomía del PM).
- El bypass global sigue siendo el default operativo actual (`false` en prod = auto-completar todo lo que no tiene candado).

**Rechazo uniforme:** El método `reject()` transiciona siempre a nuevo estado `Rejected` (terminal, inmutable), tanto para el flujo PM como para el authorizer global. Antes el rechazo del authorizer dejaba la solicitud en `pending_department` (limbo). Ahora es un estado explícito auditable.

**Nuevo tab en frontend:** `?status_group=rejected` en `investment-sheets/index` (mapea a `whereState('status', Rejected::$name)`).

**Salvaguarda operativa** (en lugar del kill switch global perdido): si Yazmin no está disponible, super_admin desactiva el toggle del proyecto desde Filament (auditado por Spatie Activity Log) o aprueba/rechaza directamente desde `InvestmentRequestResource`.

**Multi-PM racing (limitación conocida):** el approval se crea con `user_id = primer PM` (por `->first()`). Si otro PM abre y aprueba el link, el audit trail dice que aprobó el primer PM. Aceptable mientras solo Yazmin tenga el rol; cuando haya 2+ PMs y se necesite trazabilidad exacta, refactorizar.

## 2026-06-24 — Comprometido vs Pagado en Hojas de Inversión

**Decisión:** El presupuesto de un concepto/proyecto se consume en dos categorías distintas y diferenciadas en TODA la UI/validación:
- **Comprometido** (`InvestmentPaymentRequest::COMMITTED_STATUSES` = `draft, submitted, ceo_approved, projectmanager_review, projectmanager_approved, final_approved, documents_pending`): reserva presupuesto desde el borrador.
- **Pagado** (`InvestmentPaymentRequest::PAID_STATUSES` = `completed, scheduled_for_bank, approved, pending_approval`): pago efectivo (docs cargados / programado en banco + legacy).
- Rechazados/cancelados no cuentan (liberan).
- **Disponible = Presupuesto − Comprometido − Pagado.**

**Razón:** Antes solo lo "pagado" consumía presupuesto, así que un borrador no reservaba nada y se podía sobre-comprometer (dos borradores de $1000 contra $1000). Ahora un borrador ya bloquea presupuesto.

**Frontera comprometido→pagado (verificada en código):** `final_approved` → (subir docs, `InvestmentPaymentDocumentController`) → `completed` → (aprobar programación semanal, `WeeklyPaymentScheduleApprovalService`) → `scheduled_for_bank`. Por eso `final_approved` es comprometido (NO pagado).

**Implementación — única fuente de verdad:** `InvestmentRequest::budgetBreakdown(?int $excludePaymentId)` devuelve `['budget','committed','paid','available']` normalizado a MXN (× `currencies.exchange_rate`), con alcance project+concept+department (o request si no hay concepto). `excludePaymentId` excluye el propio borrador al editarlo. TODA validación (Store/Update) y display debe reusar este método — NO recalcular a mano (esa divergencia causó el bug histórico del `InvestmentDashboardController`, que usaba el filtro legacy `['pending_approval','approved']`).

**Superficies:** consolidated (card de proyecto, depto, drawer), investment-payment-review (modal), investment-dashboard (KPIs + tabla). weekly-payment-schedule no muestra presupuesto. PDFs/emails solo muestran montos solicitados/aprobados (sin desglose de presupuesto).
