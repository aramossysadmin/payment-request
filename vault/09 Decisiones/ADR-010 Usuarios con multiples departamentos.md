---
tipo: decision
aliases:
  - ADR-010 Usuarios con múltiples departamentos
tags:
  - payment-request/decisiones
fuentes:
  - app/Models/User.php
  - app/Models/Department.php
  - app/Filament/Resources/UserResource.php
  - git e502cd2
estado: vigente
actualizado: 2026-10-15
---

# ADR-010 Usuarios con múltiples departamentos

Un usuario puede pertenecer a varios departamentos: uno principal (`users.department_id`) más adicionales en el pivote `department_user`.

## Contexto
Había usuarios que administran más de un departamento. El modelo solo permitía uno, lo que obligaba a cuentas duplicadas o a ver datos ajenos.

## Decisión
Arquitectura híbrida:
- Se conserva `users.department_id` como **principal**.
- Se activa el pivote `department_user` (ya existía sin uso) para los **adicionales**, con backfill idempotente desde el principal.
- `User::departments()` y el helper `belongsToDepartment()`; los scopes `visibleTo` consideran pertenencia y autorización.
- En los formularios de captura, el usuario multi-departamento elige el departamento; los mono-departamento usan su principal.
- Filament impide quitar un departamento con pendientes activos.

## Consecuencias
- Toda consulta de visibilidad debe usar los departamentos del usuario (`whereIn`), no solo el principal.
- Destapó y corrigió que el pago heredaba el departamento del usuario (ver [[ADR-011 Lote hereda el departamento de la solicitud]]).

## Fecha
2026-06-22

## Fuentes
- Commit `e502cd2`.

## Relacionado
- [[Indice de decisiones]] · [[User]] · [[Department]] · [[Roles y actores]]
