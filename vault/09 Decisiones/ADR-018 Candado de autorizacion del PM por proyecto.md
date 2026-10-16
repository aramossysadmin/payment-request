---
tipo: decision
aliases:
  - ADR-018 Candado de autorización del PM por proyecto
tags:
  - payment-request/decisiones
fuentes:
  - app/Services/InvestmentApprovalService.php
  - app/Models/Project.php
  - config/investment-requests.php
  - .claude/memory/architecture_decisions.md
  - git d09e39b
estado: vigente
actualizado: 2026-10-15
---

# ADR-018 Candado de autorización del PM por proyecto

Cada proyecto puede exigir que sus Solicitudes de Inversión nuevas las autorice el PM; ese candado tiene precedencia sobre el bypass global.

## Contexto
Con el bypass global (ver [[ADR-007 Bypass global de autorizacion de solicitudes]]) en `false`, todas las solicitudes se auto-aprobaban. Se necesitaba volver a exigir autorización solo en algunos proyectos, sin editar el `.env` de producción (que requiere acceso al servidor).

## Decisión
- Columna `projects.requires_pm_approval` (boolean, default `false`), toggle "Requiere autorización del PM" en la edición del proyecto en Filament, auditado por activity log.
- Precedencia en `InvestmentApprovalService::createApprovals()`:
  1. Proyecto con candado → correo a todos los usuarios con rol `project_manager` (aprobación pendiente, token 48 h). Si no hay ningún PM, cae al autorizador global y registra un warning.
  2. `require_authorization = false` → `autoApprove()`.
  3. Default → correo al autorizador global (`authorizer_email`).
- Nuevo estado terminal `Rejected` para todo rechazo (antes el rechazo dejaba la solicitud en `pending_department`), con pestaña "Rechazados" en el listado.

## Consecuencias
- `INVESTMENT_REQUEST_REQUIRE_AUTHORIZATION` pasa de interruptor absoluto a "default para proyectos sin candado" (nombre conservado por compatibilidad).
- El deploy fue transparente: nada cambia hasta activar el toggle.
- Salvaguarda: si el PM no está disponible, super_admin desactiva el candado o aprueba desde Filament.
- Limitación conocida: con varios PMs, la aprobación se crea a nombre del primero; si aprueba otro, la auditoría lo atribuye al primero.

## Fecha
2026-07-21

## Fuentes
- Commit `d09e39b`; `.claude/memory/architecture_decisions.md`.

## Relacionado
- [[Indice de decisiones]] · [[Flujo de solicitud de inversion]] · [[Proyectos]] · [[Project]] · [[Estados]]
