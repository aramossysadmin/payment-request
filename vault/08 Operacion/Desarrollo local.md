---
tipo: operacion
aliases:
  - Desarrollo local
  - Entorno local
tags:
  - payment-request/operacion
fuentes:
  - composer.json
  - package.json
  - config/database.php
  - config/queue.php
  - config/mail.php
  - pint.json
  - CLAUDE.md
actualizado: 2026-10-15
---

# Desarrollo local

Cómo se trabaja el proyecto en el equipo de desarrollo: Laravel Herd, SQLite, cola en base de datos y correo de prueba en Mailtrap.

## Entorno

| Elemento | Local | Producción (referencia) |
|---|---|---|
| Servidor web | Laravel Herd → `https://payment-request.test` | nginx + php-fpm en Docker |
| PHP | 8.4 | 8.3 |
| Base de datos | SQLite `database/database.sqlite` | PostgreSQL 17 |
| Cola | `QUEUE_CONNECTION=database` | `database` |
| Correo | Mailtrap sandbox | Resend |

> [!info] Herd
> El sitio siempre está disponible vía Herd; no hace falta `php artisan serve`. La configuración interna de Herd no se toca.

## Comandos habituales

| Tarea | Comando |
|---|---|
| Todo en paralelo (server + cola + Vite) | `composer run dev` (script `dev` de `composer.json`) |
| Compilar assets | `npm run build` (o `npm run dev` para HMR) |
| Formatear PHP | `vendor/bin/pint --dirty` (antes de cerrar cualquier cambio) |
| Formatear / lint del frontend | `npm run format`, `npm run lint`, `npm run types` |
| Tests | ver [[Tests]] |

> [!tip] Cambios de frontend que no se ven
> Si un cambio en React/Tailwind no aparece, falta `npm run build` o tener `npm run dev` corriendo. Un error "Unable to locate file in Vite manifest" se resuelve igual.

## Worker de cola en local

Los correos y notificaciones se encolan. Para procesarlos sin saturar Mailtrap:

```bash
php artisan queue:work --tries=1 --timeout=120 --sleep=2 --rest=10
```

- Conviene correrlo en una **pestaña de terminal visible**, para ver cada job procesado o fallido.
- Mailtrap sandbox rechaza ráfagas con `550 Too many emails per second`; `--rest=10` espacia los envíos.
- Reintentar un job fallido por su UUID: `php artisan queue:retry <uuid>`.
- Detener el worker de forma ordenada: `php artisan queue:restart`.

Ver [[Colas y correo]] y [[Catalogo de correos]].

## Base de datos local

- Driver por defecto `sqlite` (`config/database.php`).
- Las mismas reglas de [[Proteccion de la base de datos]] aplican en local: nada de `migrate:fresh`, `db:wipe` ni borrados.
- Las diferencias SQLite vs PostgreSQL (tipos, comparaciones de texto, zona horaria) pueden ocultar errores que solo aparecen en producción; probar consultas nuevas pensando en PostgreSQL.

## Variables de entorno

El `.env` local no se lee ni se comparte (contiene credenciales). Los nombres de variables relevantes están en `config/*.php` (`config/mail.php`, `config/queue.php`, `config/sap.php`, `config/investment-requests.php`). La variable `GITHUB_TOKEN` del `.env` local se usa para el push (ver [[Deploy]]).

## Relacionado
- [[Tests]] · [[Deploy]] · [[Produccion]] · [[Stack y versiones]] · [[Estructura de carpetas]]
