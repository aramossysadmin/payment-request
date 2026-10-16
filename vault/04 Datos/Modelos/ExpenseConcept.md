---
tipo: modelo
aliases:
  - Concepto de gasto
tags:
  - payment-request/datos
  - payment-request/modelos
fuentes:
  - app/Models/ExpenseConcept.php
  - app/Policies/ExpenseConceptPolicy.php
  - database/migrations/2026_02_12_003308_create_expense_concepts_table.php
  - database/migrations/2026_03_15_003657_add_is_active_to_expense_concepts_table.php
actualizado: 2026-10-15
---

# ExpenseConcept

Catálogo de conceptos de gasto del módulo original de solicitudes de pago. En inversiones quedó como campo opcional (`expense_concept_id` nullable); el concepto que manda en inversiones es [[InvestmentExpenseConcept]].

## Tabla `expense_concepts`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint | PK |
| `name` | string, único | MAYÚSCULAS + `trim` |
| `is_active` | boolean, default `true` | |
| `created_at`, `updated_at`, `deleted_at` | timestamps | Soft deletes |

## Casts, fillable y scopes

- Casts: `is_active` → `boolean`. Fillable: `name`, `is_active`.
- Scopes: `active()` (`is_active = true`), `inactive()` (`is_active = false`).

## Relaciones

| Método | Tipo | Destino |
|---|---|---|
| `paymentRequests()` | HasMany | [[PaymentRequest]] |

Referenciado por `expense_concept_id` (nullable) en [[InvestmentRequest]] e [[InvestmentPaymentRequest]].

## Traits

`HasFactory`, `LogsActivity`, `SoftDeletes`.

## Policy

`app/Policies/ExpenseConceptPolicy.php` (Shield, `*_expense::concept`).

## Dónde se usa

- [[Catalogos]] · [[Modulo legacy Payment Requests]]

## Relacionado
- [[Modelo de datos]] · [[InvestmentExpenseConcept]]
