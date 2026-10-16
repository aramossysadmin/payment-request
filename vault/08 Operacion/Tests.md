---
tipo: operacion
aliases:
  - Tests
  - Pruebas automatizadas
tags:
  - payment-request/operacion
  - payment-request/tests
fuentes:
  - phpunit.xml
  - tests/Pest.php
  - .gitignore
  - .github/workflows/tests.yml
  - .github/workflows/lint.yml
  - composer.json
  - CLAUDE.md
actualizado: 2026-10-15
---

# Tests

Pruebas automatizadas con Pest 4. La carpeta `tests/` existe **solo en el equipo local**: no se versiona, por decisión del proyecto. Esta nota explica cómo correrlas con seguridad y qué esperar.

## Decisión: los tests no van a git

`.gitignore` excluye `/tests/`, `phpunit.xml` y `.env.testing`. Los tests nuevos quedan solo en local; los históricos se dejaron de rastrear en julio de 2026. Motivos: evitar conflictos con producción y eliminar el riesgo de que `php artisan test` (con `RefreshDatabase`) llegue a ejecutarse en el servidor. Ver [[ADR-002 Tests fuera de git]].

## Configuración

| Archivo | Clave |
|---|---|
| `phpunit.xml` (local, no versionado) | Suite `Feature` en `tests/Feature`; fuerza `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, `MAIL_MAILER=array`, `QUEUE_CONNECTION=sync`, `CACHE_STORE=array`, `SESSION_DRIVER=array` |
| `.env.testing` (local, no versionado) | **Debe** tener `DB_CONNECTION=sqlite` y `DB_DATABASE=:memory:` |
| `tests/Pest.php` | Aplica `TestCase` + `RefreshDatabase` a `Feature`; desactiva el activity logger (`ACTIVITY_LOGGER_ENABLED=false`) |

> [!danger] Antes de correr cualquier test
> Verificar que `.env.testing` sigue apuntando a `DB_DATABASE=:memory:`. `RefreshDatabase` está permitido **únicamente** porque la base de pruebas es SQLite en memoria. Nunca modificar esas variables. Ver [[Proteccion de la base de datos]].

## Cómo correrlos

```bash
php artisan test tests/Feature/InvestmentStrategyTest.php   # un archivo
php artisan test --filter=nombreDelTest                      # un test
php artisan test                                             # toda la suite
```

- Correr primero solo los tests relacionados con el cambio; la suite completa, cuando el usuario lo pida.
- Los datos se crean con factories dentro de cada test.
- `composer test` además ejecuta `pint --test` antes de la suite.

## Organización

Tests de feature en `tests/Feature/`, agrupados por módulo (estrategias, multimoneda, carga de documentos por tipo, historial de pagos, programación semanal, aprobación por correo, Filament, solicitudes de inversión). No hay suite `Unit` declarada en `phpunit.xml`.

## Fallas preexistentes conocidas

> [!warning] No confundir con regresiones
> - Alrededor de **15 fallas** conocidas: fechas fijas que ya quedaron en el pasado, respuestas 403 por el control de acceso por proyecto (tests que no asignan el proyecto al usuario) y supuestos sobre `exchange_rate`.
> - **4 tests** de `InvestmentPaymentTypeTest` dependen de la hora en que se ejecutan (ventana de captura de la [[Politica de ventanas de pago]]).
>
> Al validar un cambio, comparar contra esta línea base: una falla nueva fuera de esta lista sí es regresión.

## CI en GitHub Actions

| Workflow | Disparo | Qué hace |
|---|---|---|
| `.github/workflows/tests.yml` | push / PR a `develop`, `main`, `master`, `workos` | Matriz PHP 8.4 y 8.5, Node 22; `cp .env.example .env`, `key:generate`, `npm run build`, `./vendor/bin/pest` |
| `.github/workflows/lint.yml` | mismo disparo | PHP 8.4; `composer lint` (Pint), `npm run format`, `npm run lint` |

> [!question] Por confirmar
> Como `tests/` y `phpunit.xml` no están en el repo, el job `tests` de CI no tiene pruebas que ejecutar y lleva tiempo en rojo (ya lo estaba antes de julio de 2026). Decidir si se desactiva ese workflow, se ajusta para no fallar, o se acepta en rojo. El workflow de lint sí es útil.

## Relacionado
- [[Desarrollo local]] · [[Proteccion de la base de datos]] · [[ADR-002 Tests fuera de git]]
- [[Politica de ventanas de pago]] · [[Acceso por proyecto]] · [[Multimoneda]]
