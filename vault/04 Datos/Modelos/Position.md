---
tipo: modelo
aliases:
  - Puesto
tags:
  - payment-request/datos
  - payment-request/modelos
fuentes:
  - app/Models/Position.php
  - app/Policies/PositionPolicy.php
  - database/migrations/2026_02_13_100001_create_positions_table.php
actualizado: 2026-10-15
---

# Position

Catálogo de puestos de trabajo asignables a usuarios. Es informativo: no interviene en autorizaciones.

## Tabla `positions`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint | PK |
| `name` | string, único | MAYÚSCULAS |
| `description` | text, nullable | Primera letra en mayúscula |
| `created_at`, `updated_at`, `deleted_at` | timestamps | Soft deletes |

## Fillable

`name`, `description`.

## Relaciones

| Método | Tipo | Destino |
|---|---|---|
| `users()` | HasMany | [[User]] (`users.position_id`) |

## Traits

`HasFactory`, `LogsActivity`, `SoftDeletes`.

## Policy

`app/Policies/PositionPolicy.php` (Shield, `*_position`).

## Dónde se usa

- [[Catalogos]]

## Relacionado
- [[Modelo de datos]] · [[User]]
