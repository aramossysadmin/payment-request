---
tipo: decision
aliases:
  - ADR-002 Tests fuera de git
tags:
  - payment-request/decisiones
fuentes:
  - .gitignore
  - git aa603f9
  - git 83ba444
estado: vigente
actualizado: 2026-10-15
---

# ADR-002 Tests fuera de git

La carpeta `tests/`, `phpunit.xml` y `.env.testing` no se versionan; existen solo en el equipo de desarrollo.

## Contexto
El flujo de trabajo es local → `git push` → producción. Los tests usan `RefreshDatabase`, que recrea el esquema. Si los tests viajaran al servidor, un `php artisan test` accidental podría destruir datos. Además generaban conflictos y cambios fantasma entre máquinas.

## Decisión
- 2026-03-20: agregar `/tests/`, `phpunit.xml` y `.env.testing` a `.gitignore` y quitarlos del rastreo.
- 2026-07-21: dejar de rastrear también los archivos de test históricos que seguían en git (`git rm --cached`), alineando el repo al 100% con `.gitignore`.

## Consecuencias
- En producción no existe la carpeta `tests/`: no hay nada que ejecutar por error.
- `tests/` sigue en `autoload-dev` de `composer.json`; no afecta al runtime.
- El job `tests` de GitHub Actions no tiene pruebas que correr (ya estaba en rojo antes del cambio). Ver [[Tests]].
- Los tests nuevos quedan solo locales: si se pierde el equipo, se pierden. No hay revisión de tests vía PR.

## Fecha
2026-03-20 (inicial) · 2026-07-21 (completa)

## Fuentes
- Commits `aa603f9` y `83ba444`.
- `.gitignore`

## Relacionado
- [[Indice de decisiones]] · [[Tests]] · [[Proteccion de la base de datos]]
