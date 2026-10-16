---
tipo: decision
aliases:
  - ADR-015 Botón Guardar Programación deshabilitado
tags:
  - payment-request/decisiones
fuentes:
  - resources/js/pages/weekly-payment-schedule/index.tsx
  - app/Http/Controllers/WeeklyPaymentScheduleController.php
  - routes/web.php
  - git a08d8d8
estado: vigente (temporal)
actualizado: 2026-10-15
---

# ADR-015 Botón Guardar Programación deshabilitado

En la Programación de Pagos Semanal, el botón **Guardar Programación** está deshabilitado temporalmente por decisión operativa.

## Contexto
La programación semanal permitía seleccionar los pagos de una semana y enviarlos a autorización para pasar a banco (`scheduled_for_bank`). En julio de 2026 se agregó la carga de **comprobante de pago** por pago individual, que cierra el proceso sin requerir una programación guardada.

## Decisión
- En `resources/js/pages/weekly-payment-schedule/index.tsx` el `<Button>` de "Guardar Programación" se renderiza con `disabled` y el texto "Deshabilitado temporalmente".
- Las semanas que ya tienen programación muestran sus pagos en solo lectura.
- La carga de comprobante (`POST weekly-payment-schedule/{uuid}/receipt`) funciona con o sin programación, para pagos históricos o actuales.

## Consecuencias
- El bloqueo es **solo de interfaz**: la ruta `weekly-payment-schedule.store` y `WeeklyPaymentScheduleController::store` siguen existiendo.
- Los pagos pueden terminar en `receipt_attached` sin pasar por `scheduled_for_bank`.
- La guía de usuario (`resources/js/pages/guide/index.tsx`) sigue mencionando el botón.

> [!question] Por confirmar
> - Motivo operativo concreto y criterio para reactivarlo.
> - Si debe bloquearse también en backend mientras siga deshabilitado.
> - Actualizar o no la guía de usuario.

## Fecha
2026-07-02

## Fuentes
- Commit `a08d8d8`.

## Relacionado
- [[Indice de decisiones]] · [[Programacion semanal]] · [[Comprobantes de pago]] · [[Guia de usuario]]
