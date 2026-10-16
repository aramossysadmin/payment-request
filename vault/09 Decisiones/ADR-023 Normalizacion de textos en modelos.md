---
tipo: decision
aliases:
  - ADR-023 Normalización de textos en modelos
tags:
  - payment-request/decisiones
fuentes:
  - app/Models/InvestmentRequest.php
  - app/Models/InvestmentPaymentRequest.php
  - app/Models/PaymentRequest.php
  - git 26add19
  - git f6123f8
estado: vigente
actualizado: 2026-10-15
---

# ADR-023 Normalización de textos en modelos

Los textos capturados se normalizan en el propio modelo (mutators) para que el formato sea uniforme sin importar el origen (portal, Filament, importaciones).

## Contexto
Catálogos y solicitudes se capturan desde varias pantallas y por carga masiva; los mismos nombres llegaban con mayúsculas, minúsculas y espacios distintos, lo que generaba duplicados visuales y búsquedas inconsistentes.

## Decisión
- Marzo de 2026: mutators de normalización en los modelos de catálogo y de solicitud: nombres en MAYÚSCULAS (sociedades, sucursales, monedas, departamentos, puestos), nombres de usuario en Title Case y correo en minúsculas, proveedor/RFC/folio en MAYÚSCULAS.
- Junio de 2026: las **descripciones** de solicitudes y pagos pasan a MAYÚSCULAS completas (`setDescriptionAttribute` con `mb_strtoupper(trim(...))`) en `InvestmentRequest`, `InvestmentPaymentRequest` y `PaymentRequest`, con conversión en vivo en los inputs y CSS `uppercase` al mostrar.

## Consecuencias
- Defensa en profundidad: modelo + input + display.
- No hubo migración de datos: los registros previos conservan su caso original en BD y solo se **muestran** en mayúsculas.
- Las búsquedas y comparaciones deben considerar que el histórico puede tener otro caso.

## Fecha
2026-03-14 (catálogos) · 2026-06-01 (descripciones)

## Fuentes
- Commits `68a9062` … `2bcb686` (serie de normalización), `f6123f8`.

## Relacionado
- [[Indice de decisiones]] · [[Catalogos]] · [[Modelo de datos]]
