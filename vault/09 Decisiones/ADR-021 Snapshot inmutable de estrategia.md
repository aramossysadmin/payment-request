---
tipo: decision
aliases:
  - ADR-021 Snapshot inmutable de estrategia
tags:
  - payment-request/decisiones
fuentes:
  - app/Models/Strategy.php
  - app/Models/InvestmentPaymentRequest.php
  - git a7d521e
estado: vigente
actualizado: 2026-10-15
---

# ADR-021 Snapshot inmutable de estrategia

Las estrategias del catálogo son inmutables una vez creadas, y cada pago guarda una copia fija del factor y nombre de la estrategia usada.

## Contexto
El tipo de pago "Estrategia" calcula el total a partir de un monto base y un porcentaje del catálogo (ejemplo ficticio: Estrategia 4.5% sobre $10,000.00 → total $10,450.00). Si la tasa del catálogo cambiara, los montos históricos dejarían de cuadrar.

## Decisión
- **Catálogo inmutable** (`app/Models/Strategy.php`): solo se captura la tasa; el factor (tasa ÷ 100) y el nombre se generan. En `updating`, si cambia un atributo inmutable se lanza `LogicException`; solo se puede activar/desactivar. `forceDeleting` también lanza excepción: solo papelera. Tasas únicas validadas contra activas, inactivas y en papelera.
- **Snapshot en el pago**: `investment_payment_requests` guarda `strategy_id`, `strategy_amount`, `strategy_factor_snapshot` y `strategy_name_snapshot`.
- El backend recalcula Subtotal/IVA/Total desde el catálogo antes de validar saldo y rechaza estrategias inactivas.
- `strategySummary()` se basa en la copia fija y se muestra en correos, PDFs, Excel y pantallas siempre que el pago tenga datos de estrategia.

## Consecuencias
- Los montos históricos no dependen del estado actual del catálogo.
- Para "cambiar" una estrategia se desactiva y se crea otra.
- Migraciones aditivas (tabla nueva + columnas nullable), sin tocar registros existentes.

## Fecha
2026-09-23

## Fuentes
- Commit `a7d521e`.

## Relacionado
- [[Indice de decisiones]] · [[Estrategias]] · [[Strategy]] · [[InvestmentPaymentRequest]] · [[ADR-017 Comparacion de montos en centavos]]
