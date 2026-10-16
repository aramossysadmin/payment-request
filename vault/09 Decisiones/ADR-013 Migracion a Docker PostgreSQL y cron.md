---
tipo: decision
aliases:
  - ADR-013 Migración a Docker, PostgreSQL y cron
tags:
  - payment-request/decisiones
  - payment-request/operacion
fuentes:
  - config/database.php
  - CLAUDE.md
estado: vigente
actualizado: 2026-10-15
---

# ADR-013 Migración a Docker, PostgreSQL y cron

Producción pasó de una VM con SQLite y deploy manual a contenedores Docker con PostgreSQL y deploy automático por cron.

## Contexto
Hasta junio de 2026 la aplicación corría en una máquina virtual con base SQLite, y cada deploy era manual (`git pull` + comandos artisan a mano). SQLite limita la concurrencia de escritura y los respaldos; el deploy manual dependía de que alguien entrara al servidor.

## Decisión
- Nuevo servidor Ubuntu con Docker. La imagen se construye con un Dockerfile multi-stage (Node 22 para compilar assets; runtime `serversideup/php:8.3-fpm-nginx`).
- Tres contenedores con la misma imagen: web, worker de cola y scheduler.
- Base PostgreSQL 17 en un contenedor compartido (`agent-data-db`), base y rol `paymentrequest`.
- Deploy automático por cron (`deploy.sh`): fetch, `pull --ff-only`, build, `up -d`, `migrate --force`, `optimize`, `queue:restart` y publicación de la imagen en GHCR para rollback.
- El `.env` de producción se trasladó (no se regeneró la `APP_KEY`).

## Consecuencias
- Un push a `main` llega a producción sin intervención: commitear y hacer push implica desplegar.
- Las migraciones se aplican solas en el deploy; los respaldos con `pg_dump` son obligatorios antes de migraciones o correcciones.
- El runtime es PHP 8.3, mientras local y CI usan 8.4.
- Diferencias SQLite (local) vs PostgreSQL (producción) en consultas y fechas, por ejemplo `activity_log.created_at` mostrado con `+00`.

> [!question] Por confirmar
> - Fecha exacta del corte (no hay commit asociado; se estima alrededor del 24 de junio de 2026).
> - Estado de la regla operativa previa "solo commit + push, el usuario despliega": con el cron, todo push a `main` se despliega.

## Fecha
Junio de 2026 (aprox. 2026-06-24)

## Fuentes
- Documentación operativa del servidor (fuera del repo).
- `config/database.php` (selección de driver por `DB_CONNECTION`).

## Relacionado
- [[Indice de decisiones]] · [[Produccion]] · [[Deploy]] · [[Respaldos y restauracion]]
