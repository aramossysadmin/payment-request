---
tipo: decision
aliases:
  - ADR-001 Catálogo de tipos de pago en base de datos
tags:
  - payment-request/decisiones
fuentes:
  - app/Models/PaymentType.php
  - git b6f0a27
estado: vigente
actualizado: 2026-11-02
---

# ADR-001 Catálogo de tipos de pago en base de datos

Los tipos de pago del módulo legacy dejaron de ser un enum de PHP y pasaron a una tabla administrable.

## Contexto
`payment_type` de las solicitudes de pago era un enum fijo en código. Agregar o desactivar un tipo requería un deploy.

## Decisión
Reemplazar el enum por la tabla `payment_types` con su modelo `PaymentType`, recurso Filament, factory, seeder y policy; las solicitudes referencian el tipo por llave foránea.

## Consecuencias
- Los tipos se administran desde el panel sin tocar código.
- Hubo que actualizar controladores, form requests, notificaciones y frontend; el cambio destapó un error en correos (`label()` sobre null) que se corrigió en el mismo commit.
- **Ojo:** los tipos de pago del flujo de **inversión** (factura, reembolso, estrategia, anticipo, cotización, pagaré, domiciliado, factura España, nómina fiscal) no usan este catálogo; son valores fijos validados en `app/Http/Requests/UploadInvestmentPaymentDocumentsRequest.php`.

## Fecha
2026-03-15

## Fuentes
- Commit `b6f0a27` — *Convert payment_type from enum to database-driven catalog*.
- `app/Models/PaymentType.php`

## Relacionado
- [[Indice de decisiones]] · [[PaymentType]] · [[Catalogos]] · [[Modulo legacy Payment Requests]]
