---
tipo: modelo
aliases:
  - Usuario
tags:
  - payment-request/datos
  - payment-request/modelos
fuentes:
  - app/Models/User.php
  - app/Policies/UserPolicy.php
  - app/Filament/Resources/UserResource.php
  - database/migrations/0001_01_01_000000_create_users_table.php
  - database/migrations/2025_08_14_170933_add_two_factor_columns_to_users_table.php
actualizado: 2026-10-15
---

# User

Usuario del portal y del panel Filament. Concentra roles (spatie), departamentos, proyectos autorizados y 2FA (Fortify).

## Tabla `users`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint | PK |
| `name` | string | Se guarda en Title Case |
| `email` | string, único | Minúsculas + `trim` |
| `email_verified_at` | timestamp, nullable | |
| `password` | string | Cast `hashed` |
| `two_factor_secret`, `two_factor_recovery_codes` | text, nullable | Fortify |
| `two_factor_confirmed_at` | timestamp, nullable | |
| `remember_token` | string | |
| `is_active` | boolean, default `true` | Controla acceso al panel |
| `department_id` | FK → `departments`, nullable | Departamento principal |
| `position_id` | FK → `positions`, nullable | |
| `created_at`, `updated_at`, `deleted_at` | timestamps | Soft deletes |

Tablas auxiliares del arranque: `password_reset_tokens`, `sessions`.

## Casts, fillable y hidden

- Casts: `email_verified_at`, `two_factor_confirmed_at` → `datetime`; `is_active` → `boolean`; `password` → `hashed`.
- Fillable: `name`, `email`, `password`, `is_active`, `department_id`, `position_id`.
- Hidden: `password`, `two_factor_secret`, `two_factor_recovery_codes`, `remember_token`.

## Relaciones

| Método | Tipo | Destino |
|---|---|---|
| `department()` | BelongsTo | [[Department]] (principal) |
| `departments()` | BelongsToMany | [[Department]] vía `department_user` (principal + adicionales) |
| `position()` | BelongsTo | [[Position]] |
| `paymentRequests()` | HasMany | [[PaymentRequest]] |
| `projects()` | BelongsToMany | [[Project]] vía `project_user` |
| `roles()` (trait `HasRoles`) | MorphToMany | [[Role]] |
| `authorizedDepartments()` | **Builder**, no relación | Departamentos donde es autorizador nivel 1 o 2 |

## Métodos de negocio

- `canAccessPanel(Panel $panel)` — `true` si `is_active`. Cualquier usuario activo entra a `/admin`; lo que ve depende de permisos Shield.
- `scopeActive()`.
- `belongsToDepartment(int $id)` — pertenencia por pivote.
- `hasElevatedAccess()` — tiene rol `super_admin`, `ceo` o `project_manager`.
- `canAccessProject(?Project)` — `null` (solicitudes históricas) siempre accesible; roles elevados siempre; resto según `project_user`.

## Traits

`HasFactory`, `HasRoles`, `LogsActivity`, `Notifiable`, `SoftDeletes`, `TwoFactorAuthenticatable`. Implementa `FilamentUser`.

Activity log: `logFillable()` excluyendo `password`, `remember_token` y campos de 2FA.

> [!info]
> `User` no implementa `MustVerifyEmail` (la línea está comentada), por lo que el middleware `verified` no bloquea a nadie. Ver [[Middleware y rutas protegidas]].

## Policy

`app/Policies/UserPolicy.php` (Shield, `*_user`). En `UserResource`, el rol `super_admin` solo aparece para quien ya es `super_admin` y nadie puede quitarse a sí mismo ese rol.

## Dónde se usa

- [[Autenticacion y 2FA]] · [[Acceso por proyecto]] · [[Roles y actores]] · [[Notificaciones in-app]] · [[Panel Filament]]

## Relacionado
- [[Modelo de datos]] · [[Role]] · [[Department]] · [[Project]]
