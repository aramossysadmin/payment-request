---
tipo: modelo
aliases:
  - Sociedad
  - Razón social
tags:
  - payment-request/datos
  - payment-request/modelos
fuentes:
  - app/Models/Society.php
  - app/Policies/SocietyPolicy.php
  - database/migrations/2026_02_11_235649_create_societies_table.php
  - database/migrations/2026_07_09_174738_add_rfc_and_csf_to_societies_table.php
actualizado: 2026-10-15
---

# Society

Sociedad (razón social) a la que pertenecen las sucursales. Guarda su RFC y la ruta de su constancia de situación fiscal.

## Tabla `societies`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint | PK |
| `name` | string, único | MAYÚSCULAS |
| `rfc` | string(20), nullable | MAYÚSCULAS |
| `constancia_situacion_fiscal` | string, nullable | Ruta del archivo de la CSF |
| `created_at`, `updated_at`, `deleted_at` | timestamps | Soft deletes |

## Fillable

`name`, `rfc`, `constancia_situacion_fiscal`.

## Relaciones

| Método | Tipo | Destino |
|---|---|---|
| `branches()` | HasMany | [[Branch]] |

## Traits

`HasFactory`, `LogsActivity`, `SoftDeletes`.

## Policy

`app/Policies/SocietyPolicy.php` (Shield, `*_society`).

## Dónde se usa

- [[Catalogos]] · [[PDFs]]

## Relacionado
- [[Modelo de datos]] · [[Branch]]
