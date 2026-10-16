---
tipo: modulo
aliases:
  - Estrategias
  - Catálogo de Estrategias
tags:
  - payment-request/modulos
  - payment-request/inversion
  - payment-request/catalogos
fuentes:
  - app/Models/Strategy.php
  - app/Filament/Resources/StrategyResource.php
  - app/Filament/Resources/StrategyResource/Pages/EditStrategy.php
  - app/Policies/StrategyPolicy.php
  - app/Http/Requests/Concerns/ResolvesInvestmentStrategy.php
  - app/Models/InvestmentPaymentRequest.php
  - database/migrations/2026_09_22_135543_create_strategies_table.php
  - database/migrations/2026_09_22_135544_add_strategy_fields_to_investment_payment_requests_table.php
  - resources/js/pages/investment-sheets/consolidated.tsx
actualizado: 2026-10-15
---

# Estrategias

Catálogo de tasas de recargo para los pagos de inversión de tipo **estrategia**. El usuario captura un **Monto Estrategia** y el sistema calcula el total con recargo e IVA a partir de la tasa del catálogo.

## Modelo `Strategy`

| Columna | Tipo | Regla |
|---|---|---|
| `name` | string(150), único | Autogenerado desde la tasa: `ESTRATEGIA 4.5` (`Strategy::nameFromRate()`, quita ceros finales) |
| `rate` | decimal(6,3), único | Porcentaje capturado, ej. `4.5` |
| `factor` | decimal(8,6) | Derivado: `rate / 100` redondeado a 6 decimales (`Strategy::factorFromRate()`), asignado por el mutator `setRateAttribute()` |
| `is_active` | boolean | Solo las activas se ofrecen al capturar |
| `deleted_at` | soft delete | Papelera |

Solo `rate` e `is_active` son `fillable`; `name` y `factor` nunca se capturan.

## Inmutabilidad en dos capas

**Capa 1 — catálogo de solo lectura**

- `Strategy::IMMUTABLE_ATTRIBUTES = ['name', 'rate', 'factor']`. El evento `updating` lanza `LogicException` si cambia alguno: "Desactívala y crea una nueva."
- `forceDeleting` lanza `LogicException`: nunca hay borrado definitivo. `StrategyPolicy::forceDelete()` y `forceDeleteAny()` devuelven `false`.
- En Filament, la página de edición solo permite `is_active` (`EditStrategy::mutateFormDataBeforeSave()` descarta todo lo demás) y la tasa está deshabilitada en edición.

**Capa 2 — snapshot en el pago**

Al guardar un pago de estrategia se copian `strategy_factor_snapshot` y `strategy_name_snapshot` (`ResolvesInvestmentStrategy::strategyAttributes()`). Toda la presentación posterior usa el snapshot, de modo que desactivar o mandar a la papelera una estrategia no altera pagos existentes. La relación `InvestmentPaymentRequest::strategy()` incluye registros en papelera.

## Alta en Filament y duplicados

`StrategyResource` (grupo de navegación **Catálogos**):

- Campos: Nombre (deshabilitado, se previsualiza), Tasa (0.001–100, paso 0.001, sufijo %), Factor (deshabilitado, se previsualiza "tasa ÷ 100"), Activa.
- Regla `duplicateRateRule()` en creación: busca la tasa en **todas** las estrategias (activas, inactivas y papelera):

| Existente | Error | Aviso persistente |
|---|---|---|
| En papelera | "Ya existe la ESTRATEGIA X en la papelera. Restáurala…" | Botón "Restaurar estrategia" (abre su edición) |
| Inactiva | "Ya existe la ESTRATEGIA X pero está inactiva. Actívala…" | Botón "Activar estrategia" |
| Activa | "Ya existe la ESTRATEGIA X y está activa." | — |

- Tabla con filtros Activas/Inactivas y papelera; acciones editar, borrar (papelera) y restaurar. Permisos Shield `*_strategy` (ver [[Permisos Shield]]).

## Cálculo de montos

`Strategy::amountsFor($strategyAmount, IvaRate $ivaRate)`, todo en **enteros** (centavos, factor en millonésimas, IVA en centésimas) con redondeo half-up:

```text
Total    = Monto Estrategia × (1 + factor)
Subtotal = Total ÷ (1 + IVA)
IVA      = Total − Subtotal
```

Así Subtotal + IVA cuadra exacto con el Total. Ejemplo ficticio: Estrategia 4.5 % sobre $10,000.00 con IVA 16 % → Total $10,450.00, Subtotal $9,008.62, IVA $1,441.38.

En el frontend, `computeStrategyAmounts()` de `consolidated.tsx` replica la fórmula para previsualizar; al elegir el tipo estrategia el modal fija IVA 16 %.

## Validación al capturar (`ResolvesInvestmentStrategy`)

Trait usado por `StoreInvestmentPaymentRequest` y `UpdateInvestmentPaymentRequest`:

1. `prepareForValidation()`: si el tipo **no** es estrategia, fuerza `strategy_id` y `strategy_amount` a `null`. Si lo es y hay estrategia activa, IVA válido y monto > 0, **el backend recalcula** `subtotal`, `iva` y `total` con `amountsFor()` (el catálogo es la fuente de verdad, se ignora lo que mande el cliente).
2. Reglas: `strategy_id` y `strategy_amount` obligatorios si `payment_type = estrategia`; la estrategia debe existir sin papelera; monto ≥ 0.01.
3. `validateStrategyIsActive()`: error si la estrategia está inactiva.
4. `strategyAttributes()`: devuelve `strategy_id`, `strategy_amount` (2 decimales) y los dos snapshots.

## Presentación: `strategySummary()`

`InvestmentPaymentRequest`:

- `hasStrategy()`: `strategy_amount` y `strategy_factor_snapshot` no nulos (no depende de `payment_type`, que puede cambiar al subir documentos).
- `strategyRate()`: snapshot × 100 sin ceros finales.
- `strategySummary()`: texto único "Estrategia 4.5% · Monto Estrategia $10,000.00 MXN".
- `strategyBreakdown()`: tasa, monto, recargo (`total − strategy_amount`), total y moneda.

Dónde aparece:

| Salida | Archivo |
|---|---|
| Correo al CEO (lote enviado) | `InvestmentBatchSubmittedNotification` → `emails/request-notification` |
| Correo al PM (lote listo para revisión) | `InvestmentBatchReadyForReviewNotification` → `emails/investment-batch-ready-for-review` |
| Correo al solicitante (resultado) | `InvestmentBatchRequesterSummaryNotification` → `emails/investment-batch-requester-summary` |
| Correo de programación semanal | `WeeklyPaymentScheduleCreated` |
| PDF resumen de aprobación final para PMs | `pdf/final-approval-summary-pm.blade.php` |
| PDF historial de pagos | `pdf/payment-history.blade.php` |
| Página pública del CEO | `InvestmentBatchApprovalController::show()` → `investment-batch-approval/show.tsx` |
| Revisión del PM (resumen + desglose) | `InvestmentPaymentReviewController::index()` |
| Consolidado: borradores, pendientes de documentos, historial, panel por concepto | `InvestmentSheetConsolidatedController`, `InvestmentPaymentRequestController::index()` |
| Programación semanal | `WeeklyPaymentScheduleController::index()` |
| Excel historial | `InvestmentPaymentHistoryExcelController` + `PaymentHistoryExport` (columnas Estrategia (%) y Monto Estrategia) |
| Excel programación semanal | `WeeklyPaymentScheduleExport` |
| Filament pagos | `InvestmentPaymentRequestResource` (descripción en tabla, campos Estrategia y Monto Estrategia en detalle) |

## Relacionado
- [[Solicitudes de pago de inversion]] · [[Consolidado por proyecto]] · [[Catalogos]] · [[Panel Filament]]
- [[Strategy]] · [[InvestmentPaymentRequest]] · [[Catalogo de correos]] · [[PDFs]] · [[Exportaciones Excel]]
