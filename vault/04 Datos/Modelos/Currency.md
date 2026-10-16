---
tipo: modelo
aliases:
  - Moneda
tags:
  - payment-request/datos
  - payment-request/modelos
fuentes:
  - app/Models/Currency.php
  - app/Policies/CurrencyPolicy.php
  - database/migrations/2026_02_11_231556_create_currencies_table.php
  - database/migrations/2026_06_08_193000_add_exchange_rate_to_currencies_table.php
actualizado: 2026-10-15
---

# Currency

Catálogo de monedas con su tipo de cambio a MXN. Toda suma multimoneda del sistema se normaliza a MXN multiplicando por `exchange_rate`.

## Tabla `currencies`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint | PK |
| `name` | string, único | MAYÚSCULAS + `trim` |
| `prefix` | string(10) | Código (ej. `MXN`, `USD`, `EUR`), MAYÚSCULAS + `trim` |
| `exchange_rate` | decimal(12,4), default `1` | Significa "1 unidad = X MXN" |
| `created_at`, `updated_at`, `deleted_at` | timestamps | Soft deletes |

## Casts y fillable

- Casts: `exchange_rate` → `decimal:4`.
- Fillable: `name`, `prefix`, `exchange_rate`.

## Relaciones

| Método | Tipo | Destino |
|---|---|---|
| `paymentRequests()` | HasMany | [[PaymentRequest]] |

Referenciada además por `currency_id` en [[Project]], [[InvestmentRequest]] e [[InvestmentPaymentRequest]].

## Uso del tipo de cambio

`InvestmentRequest::budgetBreakdown()` suma `total * (select exchange_rate from currencies ...)` para normalizar presupuesto, comprometido y pagado a MXN. Ver [[Multimoneda]] y [[Presupuesto por concepto]].

> [!info]
> La migración de `currency_id` en proyectos (`2026_06_08_194724`) rellenó los proyectos existentes con la moneda de prefijo `MXN` antes de volver la columna obligatoria.

## Traits y eventos

- `HasFactory`, `LogsActivity` (fillable, solo cambios), `SoftDeletes`. Sin observers.

## Policy

`app/Policies/CurrencyPolicy.php` (Shield estándar, `*_currency`).

## Dónde se usa

- [[Catalogos]] · [[Multimoneda]] · [[Consolidado por proyecto]] · [[Dashboards]]

## Relacionado
- [[Modelo de datos]] · [[Project]] · [[InvestmentPaymentRequest]]
