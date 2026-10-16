---
tipo: modelo
aliases:
  - Estrategia
tags:
  - payment-request/datos
  - payment-request/modelos
fuentes:
  - app/Models/Strategy.php
  - app/Policies/StrategyPolicy.php
  - app/Filament/Resources/StrategyResource.php
  - database/migrations/2026_09_22_135543_create_strategies_table.php
actualizado: 2026-10-15
---

# Strategy

Catálogo de estrategias: un porcentaje que se aplica sobre un "Monto Estrategia" para obtener el total del pago de inversión. Sus datos son inmutables una vez creados.

## Tabla `strategies`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint | PK |
| `name` | string(150), único | Generado: `ESTRATEGIA 4.5` |
| `rate` | decimal(6,3), único | Porcentaje (ej. 4.5) |
| `factor` | decimal(8,6) | Derivado: `rate / 100` (ej. 0.045000) |
| `is_active` | boolean, default `true` | |
| `created_at`, `updated_at`, `deleted_at` | timestamps | Soft deletes |

## Casts y fillable

- Casts: `rate` → `decimal:3`, `factor` → `decimal:6`, `is_active` → `boolean`.
- Fillable: solo `rate` e `is_active`. `name` y `factor` se derivan.

## Reglas de inmutabilidad

- `IMMUTABLE_ATTRIBUTES = ['name', 'rate', 'factor']`.
- `booted()`:
  - `creating`: asigna `name = nameFromRate(rate)`.
  - `updating`: lanza `LogicException` si cambia algún atributo inmutable ("desactívala y crea una nueva").
  - `forceDeleting`: lanza `LogicException`; solo se permite soft delete.
- Mutator `setRateAttribute`: guarda `rate` y recalcula `factor`.

## Métodos

- `factorFromRate($rate)` — `round(rate / 100, 6)`.
- `nameFromRate($rate)` — `ESTRATEGIA` + tasa sin ceros sobrantes.
- `trimZeros($value)` — quita ceros decimales finales.
- `amountsFor($strategyAmount, IvaRate $ivaRate)` — calcula en centavos enteros: `Total = monto × (1 + factor)`, `Subtotal = Total ÷ (1 + IVA)`, `IVA = Total − Subtotal`, de modo que Subtotal + IVA cuadra exacto con Total.

Ejemplo ficticio: Estrategia 4.5% sobre $10,000.00 → Total $10,450.00; con IVA 16 % → Subtotal $9,008.62 e IVA $1,441.38.

- Scope `active()`.

## Relaciones

| Método | Tipo | Destino |
|---|---|---|
| `investmentPaymentRequests()` | HasMany | [[InvestmentPaymentRequest]] |

El pago guarda copia fija (`strategy_factor_snapshot`, `strategy_name_snapshot`) para que el cálculo histórico no dependa del catálogo.

## Traits

`HasFactory`, `LogsActivity` (`logOnly(['name','rate','factor','is_active'])`), `SoftDeletes`. No está en la lista `models.register` de filament-logger.

## Policy

`app/Policies/StrategyPolicy.php`: permisos Shield `*_strategy`, pero `forceDelete` y `forceDeleteAny` devuelven siempre `false`.

## Dónde se usa

- [[Estrategias]] · [[Solicitudes de pago de inversion]] · [[PDFs]] · [[Catalogo de correos]]

## Relacionado
- [[Modelo de datos]] · [[InvestmentPaymentRequest]] · [[Enums]]
