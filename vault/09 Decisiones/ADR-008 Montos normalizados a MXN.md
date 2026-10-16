---
tipo: decision
aliases:
  - ADR-008 Montos normalizados a MXN
tags:
  - payment-request/decisiones
fuentes:
  - app/Models/Currency.php
  - app/Http/Controllers/InvestmentSheetConsolidatedController.php
  - git ca3fb70
  - git bebcc75
  - git 146efd2
estado: vigente
actualizado: 2026-10-15
---

# ADR-008 Montos normalizados a MXN

Todas las sumas de importes se calculan en pesos mexicanos usando el tipo de cambio de cada moneda, y la moneda de visualización se aplica al final.

## Contexto
Hay proyectos en monedas distintas al peso y pagos capturados en monedas diferentes a la de su proyecto. Las sumas mezclaban monedas (sumaban euros con pesos).

## Decisión
- Columna `currencies.exchange_rate` con el significado **"1 unidad de la moneda = X MXN"**.
- El backend normaliza cada importe a MXN antes de sumar (`total × exchange_rate`, ej. helper privado `sumTotalMxn()` en `app/Http/Controllers/InvestmentSheetConsolidatedController.php` y en los controladores de PDF/Excel).
- El frontend convierte de MXN a la moneda elegida en un selector (por defecto la del proyecto): `importe_nativo × rate ÷ rate_display`.
- Los PDFs y Excel consolidados respetan la moneda seleccionada.

## Consecuencias
- Un solo criterio de agregación; los totales por proyecto, departamento y concepto son comparables.
- El tipo de cambio es un valor administrable, no histórico: cambiarlo reexpresa todos los totales.
- La validación de saldo se hace en MXN (ver [[ADR-014 Comprometido vs Pagado]]).

> [!question] Por confirmar
> Si se requiere congelar el tipo de cambio por pago (snapshot) para reportes históricos, como se hizo con las estrategias.

## Fecha
2026-06-08 (base) · 2026-07-24 (PDFs/Excel con moneda seleccionada)

## Fuentes
- Commits `ca3fb70`, `bebcc75`, `467fe15`, `146efd2`.

## Relacionado
- [[Indice de decisiones]] · [[Multimoneda]] · [[Currency]] · [[Consolidado por proyecto]]
