---
tipo: decision
aliases:
  - ADR-005 Nunca borrar registros, solo borrado lógico
tags:
  - payment-request/decisiones
fuentes:
  - CLAUDE.md
  - app/Models/Strategy.php
  - app/Models/
  - git 0770e62
  - git 4e0a2f1
estado: vigente
actualizado: 2026-10-15
---

# ADR-005 Nunca borrar registros, solo borrado lógico

Ningún registro se elimina físicamente de la base de datos; la baja es siempre lógica (papelera, estado o `is_active`).

## Contexto
El portal maneja información financiera y fiscal con trazabilidad obligatoria. Un solo desarrollador trabaja el proyecto, y una eliminación accidental (por un comando de mantenimiento, un seeder o una herramienta automática) no tendría forma sencilla de revertirse.

## Decisión
- Regla absoluta en `CLAUDE.md`: prohibido `delete()` físico, `forceDelete()`, `truncate()`, `migrate:fresh`, `migrate:refresh`, `db:wipe` y cualquier operación destructiva.
- Los modelos de negocio usan `SoftDeletes`; los catálogos se desactivan con `is_active`.
- Donde el riesgo es mayor se bloquea en código: `Strategy` lanza `LogicException` en `forceDeleting`.
- Las migraciones son aditivas; los `down()` de backfill son no-op deliberado.

## Consecuencias
- Toda la historia queda consultable y auditable (ver [[Auditoria y activity log]]).
- Las consultas deben respetar el scope de soft deletes y los estados de cancelación/rechazo.
- Las correcciones de datos se hacen con Eloquent y estado esperado validado ([[Correcciones de datos en produccion]]).
- `forceDelete` en las demás policies depende de permisos de Shield; ver el punto por confirmar en [[Proteccion de la base de datos]].

## Fecha
2026-04-15 (regla en `CLAUDE.md`) · 2026-04-28 (redacción absoluta "nunca borrar registros")

## Fuentes
- `CLAUDE.md` (sección *Database Protection*), commits `0770e62` y `4e0a2f1`.
- `app/Models/Strategy.php`

## Relacionado
- [[Indice de decisiones]] · [[Proteccion de la base de datos]] · [[Migraciones]]
