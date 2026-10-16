---
tipo: modelo
aliases:
  - Sucursal
tags:
  - payment-request/datos
  - payment-request/modelos
fuentes:
  - app/Models/Branch.php
  - app/Policies/BranchPolicy.php
  - app/Filament/Resources/BranchResource.php
  - database/migrations/2026_02_11_235824_create_branches_table.php
  - database/migrations/2026_06_22_101052_add_sap_credentials_to_branches_table.php
  - database/migrations/2026_07_20_154721_add_cost_center_to_branches_table.php
actualizado: 2026-10-15
---

# Branch

Sucursal de una sociedad. Es el catálogo al que se asignan proyectos, solicitudes y pagos, y guarda la configuración de conexión a SAP Business One de esa sucursal.

## Tabla `branches`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint | PK |
| `name` | string | Se guarda en MAYÚSCULAS con `trim` (mutator `setNameAttribute`). No es único |
| `society_id` | FK → `societies` | `restrictOnDelete` |
| `cost_center` | string(50), nullable | Centro de costo (migración 2026-07-20) |
| `sap_database` | string, nullable | Base de datos de SAP B1 |
| `sap_branch_id` | string, nullable | Id de sucursal en SAP |
| `sap_user` | string, nullable | Usuario del Service Layer |
| `sap_password` | text, nullable | Cifrado con cast `encrypted` (depende de `APP_KEY`) |
| `is_active` | boolean, default `true` | |
| `created_at`, `updated_at`, `deleted_at` | timestamps | Soft deletes |

## Casts y fillable

- Casts: `sap_password` → `encrypted`, `is_active` → `boolean`.
- Fillable: `name`, `society_id`, `cost_center`, `sap_database`, `sap_branch_id`, `sap_user`, `sap_password`, `is_active`.

> [!danger] Dato cifrado
> `sap_password` se cifra con la `APP_KEY`. Si la llave cambia, el valor es irrecuperable. Ver [[APP_KEY y datos cifrados]].

## Relaciones

| Método | Tipo | Destino |
|---|---|---|
| `society()` | BelongsTo | [[Society]] |
| `paymentRequests()` | HasMany | [[PaymentRequest]] |

También es referenciada (sin relación inversa definida en el modelo) por [[Project]], [[InvestmentRequest]] e [[InvestmentPaymentRequest]] mediante `branch_id`.

## Métodos de negocio

- `isSapConfigured(): bool` — `true` solo si la sucursal está activa **y** tiene los 4 campos SAP llenos (`sap_database`, `sap_branch_id`, `sap_user`, `sap_password`). Se usa como verificación previa antes de intentar conexión al Service Layer y en la columna "Configurado / Sin SAP" de `BranchResource`.

## Traits y eventos

- `HasFactory` (`BranchFactory`), `LogsActivity`, `SoftDeletes`.
- Activity log: `logFillable()` + `logExcept(['sap_password'])` + `logOnlyDirty()`; ignora cambios solo de `updated_at` y no guarda logs vacíos.
- Sin `booted()` ni observers propios. Registrado en `config/filament-logger.php` (`models.register`).

> [!warning] Por confirmar
> El `logExcept(['sap_password'])` solo aplica al log del trait (`log_name = default`). El `ModelLogger`/`ResourceLogger` de filament-logger guarda `getAttributes()`/`getChanges()` filtrando únicamente `$hidden`, y `Branch` no declara `$hidden`. Revisar si el texto cifrado de `sap_password` queda en `activity_log.properties` con `log_name` `Model` o `Resource`. Ver [[Auditoria y activity log]].

## Policy

`app/Policies/BranchPolicy.php` — política estándar de Shield (permisos `*_branch`). Ver [[Policies]].

## Dónde se usa

- [[Catalogos]] (Filament `BranchResource`)
- [[SAP Business One]]
- [[Proyectos]]

## Relacionado
- [[Modelo de datos]] · [[Society]] · [[Project]] · [[Auditoria y activity log]]
