---
tipo: modelo
aliases:
  - Proyecto
tags:
  - payment-request/datos
  - payment-request/modelos
fuentes:
  - app/Models/Project.php
  - app/Policies/ProjectPolicy.php
  - app/Filament/Resources/ProjectResource.php
  - database/migrations/2026_04_15_133051_create_projects_table.php
  - database/migrations/2026_09_10_191942_create_project_user_table.php
actualizado: 2026-10-15
---

# Project

Proyecto de inversión (p. ej. una apertura). Agrupa solicitudes de inversión y pagos, define la moneda base, el presupuesto autorizado, si está abierto para captura y quién puede verlo.

## Tabla `projects`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint | PK |
| `name` | string, único | MAYÚSCULAS |
| `branch_id` | FK → `branches` | |
| `currency_id` | FK → `currencies` | Obligatoria (backfill a MXN) |
| `start_date`, `opening_date` | date, nullable | Para dashboards |
| `authorized_budget` | decimal(15,2), nullable | Referencia en el dashboard |
| `is_active` | boolean, default `true` | `true` = ABIERTO, `false` = CERRADO |
| `closed_at` | timestamp, nullable | Se sincroniza con `is_active` |
| `requires_pm_approval` | boolean, default `false` | Nuevas solicitudes de inversión van al PM |
| `created_at`, `updated_at`, `deleted_at` | timestamps | Soft deletes |

## Pivote `project_user`

`id`, `project_id`, `user_id` (ambos `cascadeOnDelete`), timestamps, único `project_id + user_id`. La migración `2026_09_10_192000` la rellenó para usuarios sin rol privilegiado con los proyectos donde sus departamentos ya habían capturado solicitudes.

## Casts y fillable

- Casts: `is_active`, `requires_pm_approval` → `boolean`; `start_date`, `opening_date` → `date`; `closed_at` → `datetime`; `authorized_budget` → `decimal:2`.
- Fillable: `name`, `branch_id`, `currency_id`, `start_date`, `opening_date`, `authorized_budget`, `is_active`, `closed_at`, `requires_pm_approval`.

## Relaciones

| Método | Tipo | Destino |
|---|---|---|
| `branch()` | BelongsTo | [[Branch]] |
| `currency()` | BelongsTo | [[Currency]] |
| `investmentRequests()` | HasMany | [[InvestmentRequest]] |
| `users()` | BelongsToMany | [[User]] vía `project_user` |

Referenciado por [[InvestmentPaymentBatch]] (`project_id`).

## Scopes

- `active()` — proyectos abiertos (`is_active = true`).
- `accessibleBy(User $user)` — roles con acceso elevado (`super_admin`, `ceo`, `project_manager`) ven todos; el resto solo los de su pivote `project_user`.

## Eventos

`booted()` → `saving`: si cambió `is_active`, pone `closed_at = now()` al cerrar y `null` al reabrir. Aplica a cualquier vía de guardado.

> [!info] Proyecto cerrado
> Los controladores de captura, envío de lote, revisión PM, documentos, programación semanal y comprobantes abortan con 403 o regresan error si el proyecto del pago está cerrado. Solo `super_admin` y `project_manager` pueden cambiar el toggle en Filament.

## Traits

`HasFactory`, `LogsActivity`, `SoftDeletes`.

## Policy

`app/Policies/ProjectPolicy.php` (Shield, `*_project`).

## Dónde se usa

- [[Proyectos]] · [[Acceso por proyecto]] · [[Consolidado por proyecto]] · [[Dashboards]] · [[Solicitudes de inversion]]

## Relacionado
- [[Modelo de datos]] · [[User]] · [[Roles y actores]]
