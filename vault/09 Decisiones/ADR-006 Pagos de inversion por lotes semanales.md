---
tipo: decision
aliases:
  - ADR-006 Pagos de inversión por lotes semanales
tags:
  - payment-request/decisiones
fuentes:
  - app/Models/InvestmentPaymentBatch.php
  - app/Http/Controllers/InvestmentPaymentRequestController.php
  - app/Http/Controllers/InvestmentPaymentBatchController.php
  - git 693a7d7
  - git 8ee1a3b
  - git 6290945
  - git 0d268a4
estado: vigente (modificada por ADR-016)
actualizado: 2026-10-15
---

# ADR-006 Pagos de inversión por lotes semanales

Los pagos de inversión se capturan como borradores y se agrupan en lotes por departamento + proyecto + semana ISO, que se envían y aprueban en bloque.

## Contexto
El flujo original aprobaba cada pago de inversión de forma individual por correo. Con el volumen semanal, el CEO y el PM recibían demasiados correos y no tenían una vista de conjunto por proyecto y semana.

## Decisión
Rediseñar el flujo en fases (mayo de 2026):
1. **Borrador y lote**: tabla `investment_payment_batches` (departamento, proyecto, semana ISO, año, estado); cada pago nuevo queda en `draft` dentro del lote activo de la semana.
2. **Primera aprobación del CEO** del lote completo, con checkboxes por pago.
3. **Revisión del PM**, que puede ajustar el monto aprobado.
4. Aprobación final (originalmente del CEO; hoy la da el PM, ver [[ADR-016 El PM da la aprobacion final]]).
5. **Carga de documentos** por el solicitante y cierre (`completed`).

## Consecuencias
- Un correo consolidado por lote/solicitante en lugar de uno por pago.
- Los pagos históricos (anteriores al flujo) quedaron con `batch_id` nulo y estados legacy, por lo que las sumas deben contemplar ambos juegos de estados (ver [[ADR-014 Comprometido vs Pagado]]).
- El lote se identifica por el departamento de la solicitud de inversión (ver [[ADR-011 Lote hereda el departamento de la solicitud]]).
- La programación semanal toma los pagos completados del flujo nuevo.

## Fecha
2026-05-17

## Fuentes
- Commits `693a7d7`, `8ee1a3b`, `6290945`, `6f56578`, `0d268a4`, `ba7bc8c`.

## Relacionado
- [[Indice de decisiones]] · [[Flujo de pago de inversion]] · [[Lotes de pago]] · [[InvestmentPaymentBatch]]
