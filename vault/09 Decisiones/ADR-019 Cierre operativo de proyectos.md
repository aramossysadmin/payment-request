---
tipo: decision
aliases:
  - ADR-019 Cierre operativo de proyectos
tags:
  - payment-request/decisiones
fuentes:
  - app/Models/Project.php
  - app/Filament/Resources/ProjectResource.php
  - git c361b5e
estado: vigente
actualizado: 2026-10-15
---

# ADR-019 Cierre operativo de proyectos

Un proyecto puede marcarse como CERRADO: se bloquea toda captura y modificación, pero sigue visible en solo lectura para quien corresponde.

## Contexto
Al terminar un proyecto se necesitaba impedir nuevas solicitudes, pagos, envíos o comprobantes sin borrar ni ocultar su información, y poder reabrirlo sin afectar registros en flujo.

## Decisión
- Reusar `projects.is_active` como "Abierto para captura" y agregar `closed_at`; un observer `saving` en `Project` sincroniza `closed_at` al cerrar/reabrir.
- Guards `abort_if(! $project?->is_active)` en todos los endpoints de escritura (solicitudes, pagos, envío de lote, carga de documentos, revisión del PM, programación semanal, comprobantes). Los de lectura (index, show, PDF, Excel) no llevan guard.
- Solo `super_admin` y `project_manager` pueden cambiar el toggle y ver proyectos cerrados en dashboards y listados (con badge "Cerrado"); los formularios de captura nunca ofrecen proyectos cerrados.
- Frontend: banner `ProjectClosedBanner` y botones de captura deshabilitados.

## Consecuencias
- Cierre reversible, sin tocar registros (coherente con [[ADR-005 Nunca borrar registros solo borrado logico]]).
- Cada endpoint de escritura nuevo debe incluir el guard de proyecto abierto.

## Fecha
2026-09-10

## Fuentes
- Commit `c361b5e`.

## Relacionado
- [[Indice de decisiones]] · [[Proyectos]] · [[Project]] · [[ADR-020 Acceso por proyecto con backfill]]
