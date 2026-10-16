---
tipo: decision
aliases:
  - ADR-017 Comparación de montos en centavos
tags:
  - payment-request/decisiones
fuentes:
  - app/Http/Requests/StoreInvestmentPaymentRequest.php
  - app/Http/Requests/UpdateInvestmentPaymentRequest.php
  - app/Http/Controllers/InvestmentPaymentReviewController.php
  - git b7ea864
  - git a7d521e
estado: vigente
actualizado: 2026-10-15
---

# ADR-017 Comparación de montos en centavos

Las validaciones monetarias comparan importes como **enteros en centavos**, nunca como `float`.

## Contexto
Al capturar un pago cuyo total igualaba exactamente el saldo disponible, la validación respondía "El total excede el saldo disponible" aunque ambos importes se veían idénticos. Causa: comparación `>` entre `float` (IEEE-754); valores como 3661.10 no tienen representación binaria exacta y la diferencia de ~1e-12 bastaba para rechazar.

## Decisión
Canonizar ambos operandos antes de comparar: `(int) round($x * 100) > (int) round($y * 100)`. Aplicado en los tres puntos de control de saldo del módulo de inversión: captura, edición de borrador y aprobación del PM.

El cálculo de estrategias (2026-09-23) siguió el mismo principio: aritmética en centavos en backend y la misma en el cliente.

## Consecuencias
- Cubrir el saldo exacto ya no se rechaza.
- Cualquier comparación o cálculo monetario nuevo debe usar centavos enteros.

## Fecha
2026-07-20

## Fuentes
- Commits `b7ea864` y `a7d521e`.

## Relacionado
- [[Indice de decisiones]] · [[Presupuesto por concepto]] · [[ADR-014 Comprometido vs Pagado]] · [[Estrategias]]
