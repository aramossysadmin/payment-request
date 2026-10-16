---
tipo: modelo
aliases:
  - Departamento
tags:
  - payment-request/datos
  - payment-request/modelos
fuentes:
  - app/Models/Department.php
  - app/Policies/DepartmentPolicy.php
  - database/migrations/2026_02_13_100002_create_departments_table.php
  - database/migrations/2026_02_13_100003_create_department_user_table.php
  - database/migrations/2026_04_09_182810_add_authorizer_levels_to_departments_and_approvals.php
  - database/migrations/2026_05_07_183449_create_department_investment_expense_category_table.php
actualizado: 2026-10-15
---

# Department

Departamento de la organización. Define a qué área pertenece un usuario, quién autoriza (nivel 1 y 2) las solicitudes del área y qué categorías de gasto de inversión puede usar.

## Tabla `departments`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint | PK |
| `name` | string, único | MAYÚSCULAS + `trim` |
| `description` | text, nullable | Primera letra en mayúscula |
| `authorizer_level_1_id` | FK → `users`, nullable | `restrictOnDelete` |
| `authorizer_level_2_id` | FK → `users`, nullable | `restrictOnDelete` |
| `created_at`, `updated_at`, `deleted_at` | timestamps | Soft deletes |

## Tablas pivote

- `department_user` (`id`, `department_id`, `user_id`, timestamps, único `department_id + user_id`): pertenencia de usuarios (principal y adicionales). La migración `2026_06_22_003800` rellenó la pivote con el `users.department_id` de cada usuario.
- `department_investment_expense_category` (PK compuesta `department_id + investment_expense_category_id`, `cascadeOnDelete`): qué categorías de inversión ve cada departamento.

## Fillable

`name`, `description`, `authorizer_level_1_id`, `authorizer_level_2_id`.

## Relaciones

| Método | Tipo | Destino |
|---|---|---|
| `users()` | HasMany | [[User]] (por `users.department_id`, departamento principal) |
| `allUsers()` | BelongsToMany | [[User]] vía `department_user` |
| `authorizerLevel1()` | BelongsTo | [[User]] (`authorizer_level_1_id`) |
| `authorizerLevel2()` | BelongsTo | [[User]] (`authorizer_level_2_id`) |
| `investmentExpenseCategories()` | BelongsToMany | [[InvestmentExpenseCategory]] |

## Rol en el negocio

- **Flujo legacy de pagos**: `ApprovalService` usa `authorizerLevel1` / `authorizerLevel2` del departamento del solicitante (etapa *department*) y de los departamentos llamados `ADMINISTRACIÓN` y `TESORERÍA` (etapas siguientes). Ver [[Flujo legacy de solicitud de pago]].
- **Visibilidad**: `scopeVisibleTo` de [[InvestmentRequest]] y [[PaymentRequest]] une los departamentos donde el usuario es autorizador con los de su pivote `department_user`.
- **Lotes de pago**: [[InvestmentPaymentBatch]] se agrupa por `department_id`.

## Traits y eventos

- `HasFactory`, `LogsActivity`, `SoftDeletes`. Sin observers.

## Policy

`app/Policies/DepartmentPolicy.php` (Shield estándar, `*_department`).

## Dónde se usa

- [[Catalogos]] · [[Acceso por proyecto]] · [[Solicitudes de inversion]] · [[Lotes de pago]] · [[Modulo legacy Payment Requests]]

## Relacionado
- [[Modelo de datos]] · [[User]] · [[Roles y actores]]
