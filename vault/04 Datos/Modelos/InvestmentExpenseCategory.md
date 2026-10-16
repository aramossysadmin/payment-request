---
tipo: modelo
aliases:
  - Categoría de gasto de inversión
tags:
  - payment-request/datos
  - payment-request/modelos
fuentes:
  - app/Models/InvestmentExpenseCategory.php
  - app/Policies/InvestmentExpenseCategoryPolicy.php
  - database/migrations/2026_04_15_133102_create_investment_expense_categories_table.php
  - database/migrations/2026_04_28_194416_add_super_category_to_investment_expense_categories_table.php
  - database/migrations/2026_05_07_183449_create_department_investment_expense_category_table.php
  - database/migrations/2026_05_07_203317_change_investment_expense_categories_unique_to_composite.php
actualizado: 2026-10-15
---

# InvestmentExpenseCategory

Categoría que agrupa conceptos de gasto de inversión (ej. obra civil, mobiliario). Puede pertenecer a una "súper categoría" y se asigna a uno o varios departamentos.

## Tabla `investment_expense_categories`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint | PK |
| `name` | string | MAYÚSCULAS + `trim` |
| `super_category` | string, nullable | MAYÚSCULAS; agrupador superior |
| `is_active` | boolean, default `true` | |
| `created_at`, `updated_at`, `deleted_at` | timestamps | Soft deletes |

- Índice único compuesto `name + super_category` (antes `name` era único por sí solo).
- La columna `department_id` existió de abril a mayo 2026; se migró a la pivote `department_investment_expense_category` y se eliminó. Ver [[Migraciones]].

## Casts, fillable y scopes

- Casts: `is_active` → `boolean`. Fillable: `name`, `super_category`, `is_active`.
- Scope `active()`.

## Relaciones

| Método | Tipo | Destino |
|---|---|---|
| `departments()` | BelongsToMany | [[Department]] (pivote `department_investment_expense_category`) |
| `investmentExpenseConcepts()` | HasMany | [[InvestmentExpenseConcept]] |

## Traits

`HasFactory`, `LogsActivity`, `SoftDeletes`.

## Policy

`app/Policies/InvestmentExpenseCategoryPolicy.php` (Shield, `*_investment::expense::category`).

## Dónde se usa

- [[Catalogos]] · [[Solicitudes de inversion]] · [[Presupuesto por concepto]] · [[Consolidado por proyecto]]

## Relacionado
- [[Modelo de datos]] · [[InvestmentExpenseConcept]] · [[Department]]
