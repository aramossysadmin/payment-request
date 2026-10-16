---
tipo: indice
aliases:
  - Índice de decisiones
  - ADRs
tags:
  - payment-request/decisiones
fuentes:
  - .claude/memory/architecture_decisions.md
  - .claude/memory/tech_stack.md
  - CLAUDE.md
actualizado: 2026-10-15
---

# Índice de decisiones

Registro de decisiones de arquitectura y operación (ADR) del proyecto. Cada nota sigue el formato **Contexto · Decisión · Consecuencias · Fecha · Fuentes**, con los commits que la sustentan.

## Decisiones

| ADR | Decisión | Fecha | Estado |
|---|---|---|---|
| [[ADR-001 Catalogo de tipos de pago en base de datos\|001]] | Tipos de pago legacy como catálogo en BD, no enum | 2026-03-15 | vigente |
| [[ADR-002 Tests fuera de git\|002]] | `tests/`, `phpunit.xml` y `.env.testing` no se versionan | 2026-03-20 / 2026-07-21 | vigente |
| [[ADR-003 Documentos en disco privado con URL firmada\|003]] | Documentos en disco privado, nombres UUID, URL firmada | 2026-04-15 | vigente |
| [[ADR-004 Validacion de archivos por contenido\|004]] | Validación MIME por contenido + extensión | 2026-04-15 / 2026-06-23 | vigente |
| [[ADR-005 Nunca borrar registros solo borrado logico\|005]] | Nunca borrar registros; solo borrado lógico | 2026-04-15 | vigente |
| [[ADR-006 Pagos de inversion por lotes semanales\|006]] | Pagos de inversión en borrador y lotes semanales | 2026-05-17 | vigente (modificada por 016) |
| [[ADR-007 Bypass global de autorizacion de solicitudes\|007]] | Auto-aprobación configurable de Solicitudes de Inversión | 2026-06-01 | vigente (acotada por 018) |
| [[ADR-008 Montos normalizados a MXN\|008]] | Sumas normalizadas a MXN con `exchange_rate` | 2026-06-08 | vigente |
| [[ADR-009 Politica de ventanas como pagina singleton\|009]] | Política de ventanas singleton en Filament Page | 2026-06-11 | vigente |
| [[ADR-010 Usuarios con multiples departamentos\|010]] | Usuarios con departamento principal + adicionales | 2026-06-22 | vigente |
| [[ADR-011 Lote hereda el departamento de la solicitud\|011]] | Pago y lote heredan el depto de la solicitud, no del usuario | 2026-06-22 | vigente |
| [[ADR-012 Credenciales SAP cifradas por sucursal\|012]] | Credenciales SAP por sucursal con cifrado `encrypted` | 2026-06-22 | vigente |
| [[ADR-013 Migracion a Docker PostgreSQL y cron\|013]] | Producción en Docker + PostgreSQL + deploy por cron | ~2026-06-24 | vigente |
| [[ADR-014 Comprometido vs Pagado\|014]] | Presupuesto: Comprometido vs Pagado, fuente única | 2026-06-24 | vigente |
| [[ADR-015 Boton Guardar Programacion deshabilitado\|015]] | Botón "Guardar Programación" deshabilitado | 2026-07-02 | temporal |
| [[ADR-016 El PM da la aprobacion final\|016]] | Se elimina la aprobación final del CEO; el PM cierra | 2026-07-14 | vigente |
| [[ADR-017 Comparacion de montos en centavos\|017]] | Comparaciones monetarias en centavos enteros | 2026-07-20 | vigente |
| [[ADR-018 Candado de autorizacion del PM por proyecto\|018]] | Candado de autorización del PM por proyecto | 2026-07-21 | vigente |
| [[ADR-019 Cierre operativo de proyectos\|019]] | Proyectos ABIERTO/CERRADO con bloqueo de captura | 2026-09-10 | vigente |
| [[ADR-020 Acceso por proyecto con backfill\|020]] | Acceso por proyecto con backfill automático | 2026-09-10 | vigente |
| [[ADR-021 Snapshot inmutable de estrategia\|021]] | Estrategias inmutables y copia fija en el pago | 2026-09-23 | vigente |
| [[ADR-022 Aprobacion por correo con token\|022]] | Aprobación desde el correo con token caducable | 2026-03-14 | vigente |
| [[ADR-023 Normalizacion de textos en modelos\|023]] | Normalización de textos en mutators de modelos | 2026-03-14 / 2026-06-01 | vigente |

> [!info] Numeración
> Los números 001–021 siguen el orden cronológico; 022 y 023 se agregaron después y son anteriores en fecha. La columna **Fecha** es la referencia temporal.

## Por tema

- **Flujo de inversión**: 006 · 007 · 011 · 014 · 015 · 016 · 017 · 018 · 021
- **Datos e integridad**: 001 · 005 · 008 · 017 · 021 · 023
- **Seguridad y acceso**: 003 · 004 · 010 · 012 · 019 · 020 · 022
- **Operación**: 002 · 009 · 013

## Cómo agregar una decisión

1. Crear `ADR-NNN Titulo corto sin acentos` con el siguiente número libre.
2. Frontmatter `tipo: decision`, `estado`, `fuentes` con archivos y commits.
3. Secciones: Contexto · Decisión · Consecuencias · Fecha · Fuentes · Relacionado.
4. Si reemplaza o acota otra, actualizar el `estado` de la anterior y esta tabla.

## Relacionado
- [[Indice del historial]] · [[Stack y versiones]] · [[Produccion]] · [[Proteccion de la base de datos]]
