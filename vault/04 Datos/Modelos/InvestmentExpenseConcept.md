---
tipo: modelo
aliases:
  - Concepto de gasto de inversión
tags:
  - payment-request/datos
  - payment-request/modelos
fuentes:
  - app/Models/InvestmentExpenseConcept.php
  - app/Policies/InvestmentExpenseConceptPolicy.php
  - database/migrations/2026_04_15_133106_create_investment_expense_concepts_table.php
  - database/migrations/2026_06_22_215829_add_sap_item_code_to_investment_expense_concepts_table.php
actualizado: 2026-10-15
---

# InvestmentExpenseConcept

Concepto de gasto de inversión dentro de una categoría. Es la unidad sobre la que se calcula el presupuesto (proyecto + concepto + departamento) y puede mapearse a un artículo (ItemCode) de SAP B1.

## Tabla `investment_expense_concepts`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint | PK |
| `name` | string | MAYÚSCULAS + `trim` (no único) |
| `investment_expense_category_id` | FK → `investment_expense_categories` | |
| `sap_item_code` | string(50), nullable, único | ItemCode SAP; MAYÚSCULAS, `NULL` si vacío |
| `is_active` | boolean, default `true` | |
| `created_at`, `updated_at`, `deleted_at` | timestamps | Soft deletes |

## Casts, fillable y scopes

- Casts: `is_active` → `boolean`.
- Fillable: `name`, `investment_expense_category_id`, `sap_item_code`, `is_active`.
- Scope `active()`.

## Relaciones

| Método | Tipo | Destino |
|---|---|---|
| `category()` | BelongsTo | [[InvestmentExpenseCategory]] |

Referenciado por `investment_expense_concept_id` en [[InvestmentRequest]].

## Métodos de negocio

- `isSapMapped(): bool` — `true` si tiene `sap_item_code`. Verificación previa antes de enviar pagos relacionados a SAP.

## Traits

`HasFactory`, `LogsActivity`, `SoftDeletes`.

## Policy

`app/Policies/InvestmentExpenseConceptPolicy.php` (Shield, `*_investment::expense::concept`).

## Dónde se usa

- [[Catalogos]] · [[Presupuesto por concepto]] · [[Solicitudes de inversion]] · [[SAP Business One]]

## Relacionado
- [[Modelo de datos]] · [[InvestmentRequest]] · [[InvestmentExpenseCategory]]
