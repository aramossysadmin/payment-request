---
tipo: modelo
aliases:
  - Rol
tags:
  - payment-request/datos
  - payment-request/modelos
  - payment-request/permisos
fuentes:
  - app/Models/Role.php
  - app/Policies/RolePolicy.php
  - app/Filament/Resources/RoleResource.php
  - config/permission.php
  - database/migrations/2026_02_11_174837_create_permission_tables.php
actualizado: 2026-10-15
---

# Role

Rol de spatie/laravel-permission extendido por la aplicación. Es el modelo de roles configurado en `config/permission.php` y el que administra Filament Shield.

## Tablas (spatie/laravel-permission)

| Tabla | Contenido |
|---|---|
| `roles` | `id`, `name`, `guard_name`, timestamps; único `name + guard_name` |
| `permissions` | `id`, `name`, `guard_name`, timestamps |
| `model_has_roles` | Asignación rol → usuario (morph) |
| `model_has_permissions` | Permisos directos a modelos (morph) |
| `role_has_permissions` | Permisos de cada rol |

## Comportamiento propio

- Hereda de `Spatie\Permission\Models\Role`.
- **Global scope `hide_super_admin`**: si el usuario autenticado no tiene el rol `super_admin` (consulta directa a `model_has_roles`), el rol `super_admin` se oculta de todas las consultas. Sin usuario autenticado (consola, colas) no filtra.
- Mutator `setNameAttribute`: `trim`, minúsculas y espacios → `_` (ej. "Project Manager" → `project_manager`).

## Traits

`LogsActivity` (fillable, solo cambios). Registrado en filament-logger.

## Policy

`app/Policies/RolePolicy.php`, registrada explícitamente por Shield (`register_role_policy`). `RoleResource` solo usa los prefijos `view`, `view_any`, `create`, `update`, `delete`, `delete_any`.

## Dónde se usa

- [[Roles y actores]] · [[Permisos Shield]] · [[Panel Filament]]

## Relacionado
- [[Modelo de datos]] · [[User]] · [[Policies]]
