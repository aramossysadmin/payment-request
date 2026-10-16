---
tipo: modelo
aliases:
  - Tipo de pago
tags:
  - payment-request/datos
  - payment-request/modelos
fuentes:
  - app/Models/PaymentType.php
  - app/Enums/DocumentMode.php
  - app/Enums/PaymentTypeCategory.php
  - database/migrations/2026_03_15_032138_create_payment_types_table.php
  - database/migrations/2026_04_10_210000_replace_requires_invoice_with_document_modes_in_payment_types.php
actualizado: 2026-10-15
---

# PaymentType

Catálogo de tipos de pago para solicitudes de pago legacy y solicitudes de inversión, con reglas de qué documentos son obligatorios, opcionales o deshabilitados.

> [!info]
> Los pagos de inversión ([[InvestmentPaymentRequest]]) **no** usan este catálogo: guardan `payment_type` como string del enum `InvestmentPaymentType`. Ver [[Enums]].

## Tabla `payment_types`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint | PK |
| `name` | string, único | MAYÚSCULAS |
| `slug` | string, único | `Str::slug(name)` si no viene |
| `invoice_documents_mode` | string, default `disabled` | [[Enums#DocumentMode]] |
| `additional_documents_mode` | string, default `optional` | [[Enums#DocumentMode]] |
| `is_active` | boolean, default `true` | |
| `category` | string, default `payment` | [[Enums#PaymentTypeCategory]] |
| `created_at`, `updated_at`, `deleted_at` | timestamps | Soft deletes |

La columna `requires_invoice_documents` se reemplazó por los dos modos de documentos.

## Casts, fillable y scopes

- Casts: `invoice_documents_mode`, `additional_documents_mode` → `DocumentMode`; `category` → `PaymentTypeCategory`; `is_active` → `boolean`.
- Fillable: `name`, `slug`, `invoice_documents_mode`, `additional_documents_mode`, `is_active`, `category`.
- Scopes: `active()`, `forPayments()` (`category = payment`), `forInvestments()` (`category = investment`).

## Relaciones

| Método | Tipo | Destino |
|---|---|---|
| `paymentRequests()` | HasMany | [[PaymentRequest]] |
| `investmentRequests()` | HasMany | [[InvestmentRequest]] |

## Eventos

`booted()` → `creating`: genera `slug` desde `name` si falta.

## Traits

`HasFactory`, `LogsActivity`, `SoftDeletes`.

## Policy

`app/Policies/PaymentTypePolicy.php` (Shield, `*_payment::type`).

## Dónde se usa

- [[Catalogos]] · [[Modulo legacy Payment Requests]] · [[Solicitudes de inversion]]

## Relacionado
- [[Modelo de datos]] · [[Enums]]
