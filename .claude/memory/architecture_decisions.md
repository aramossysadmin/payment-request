---
name: Architecture Decisions
description: Decisiones de arquitectura importantes del proyecto y su justificacion
type: project
---

# Architecture Decisions

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
