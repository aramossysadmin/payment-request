---
tipo: decision
aliases:
  - ADR-020 Acceso por proyecto con backfill
tags:
  - payment-request/decisiones
  - payment-request/permisos
fuentes:
  - app/Models/User.php
  - app/Models/Project.php
  - app/Models/InvestmentRequest.php
  - app/Filament/Resources/UserResource.php
  - git f959f5e
estado: vigente
actualizado: 2026-10-15
---

# ADR-020 Acceso por proyecto con backfill

Los usuarios no privilegiados solo ven los proyectos que tienen asignados; la asignación inicial se generó automáticamente a partir de su actividad.

## Contexto
Cualquier usuario veía todos los proyectos activos en listados, dashboards y selectores, y podía consultar el detalle de un proyecto por URL directa. El único filtro era por departamento sobre solicitudes y pagos.

## Decisión
- Pivote `project_user` y relaciones `User::projects()` / `Project::users()`.
- `User::hasElevatedAccess()` (super_admin, ceo, project_manager: acceso total) y `User::canAccessProject(?Project)`; scope `Project::accessibleBy(User)`.
- `InvestmentRequest::scopeVisibleTo` añade un filtro AND por proyecto; las solicitudes históricas sin proyecto no se bloquean.
- Guards `abort_if(! $user->canAccessProject($project), 403)` en los endpoints con `{project}` (consolidado, PDFs y Excel de historial y de solicitudes).
- Migración de backfill: a cada usuario no privilegiado se le asignan los proyectos donde su(s) departamento(s) ya tenían alguna Solicitud de Inversión; su `down()` es no-op deliberado.
- Filament: sección "Proyectos autorizados" en el usuario, visible solo para usuarios sin rol privilegiado.

## Consecuencias
- Al dar de alta un usuario hay que asignarle proyectos, o no verá ninguno.
- Varios tests existentes fallan con 403 por no asignar el proyecto (forman parte de las fallas conocidas, ver [[Tests]]).
- `PaymentRequest` (legacy) no se filtra por proyecto porque no tiene `project_id`.

## Fecha
2026-09-10

## Fuentes
- Commit `f959f5e`.

## Relacionado
- [[Indice de decisiones]] · [[Acceso por proyecto]] · [[User]] · [[Project]] · [[Middleware y rutas protegidas]]
