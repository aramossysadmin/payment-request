---
tipo: decision
aliases:
  - ADR-007 Bypass global de autorización de solicitudes
tags:
  - payment-request/decisiones
fuentes:
  - config/investment-requests.php
  - app/Services/InvestmentApprovalService.php
  - git c0aea5c
estado: vigente (acotada por ADR-018)
actualizado: 2026-10-15
---

# ADR-007 Bypass global de autorización de solicitudes

Una bandera de configuración permite que las Solicitudes de Inversión nuevas se aprueben automáticamente, sin pasar por el autorizador.

## Contexto
La autorización individual de cada Solicitud de Inversión por correo se volvió un cuello de botella mientras se diseñaba un esquema de autorización consolidada similar al de pagos.

## Decisión
- Nueva clave `require_authorization` en `config/investment-requests.php`, alimentada por la variable `INVESTMENT_REQUEST_REQUIRE_AUTHORIZATION` (default `true`).
- Si es `false`, `InvestmentApprovalService::createApprovals()` llama a `autoApprove()`: crea una aprobación marcada como aprobada con comentario de bypass automático y el solicitante como actor, pasa la solicitud a `Completed` y no envía correo.
- Aplica igual al alta desde Inertia y desde Filament porque ambos usan el mismo servicio.

## Consecuencias
- Se planteó como medida **temporal**.
- Desde 2026-07-21 dejó de ser un interruptor absoluto: el candado por proyecto tiene precedencia (ver [[ADR-018 Candado de autorizacion del PM por proyecto]]).
- La pista de auditoría distingue las aprobaciones automáticas por su comentario.

> [!question] Por confirmar
> Si el bypass sigue siendo temporal o ya es el comportamiento definitivo para proyectos sin candado.

## Fecha
2026-06-01

## Fuentes
- Commit `c0aea5c`.
- `config/investment-requests.php`

## Relacionado
- [[Indice de decisiones]] · [[Flujo de solicitud de inversion]] · [[Solicitudes de inversion]]
