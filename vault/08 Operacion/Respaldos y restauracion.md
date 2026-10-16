---
tipo: operacion
aliases:
  - Respaldos y restauración
  - Backups
tags:
  - payment-request/operacion
fuentes:
  - CLAUDE.md
actualizado: 2026-10-15
---

# Respaldos y restauración

Procedimiento estándar para respaldar la base de datos PostgreSQL de producción antes de migraciones o correcciones de datos, y reglas para restaurar.

> [!danger] Reglas no negociables
> - El respaldo es **obligatorio** antes de cualquier migración que se vaya a desplegar o de cualquier corrección de datos.
> - Los dumps **no se bajan** al equipo local: contienen datos financieros y fiscales.
> - Restaurar **solo con autorización explícita** del usuario.

## Ruta y nombre estándar

```
/srv/paymentrequest/paymentrequest-pre-<motivo>-<YYYYMMDD-HHMMSS>.dump
```

`<motivo>` es un identificador corto de lo que se va a hacer (ej. `pre-migracion-estrategias`). Formato custom de `pg_dump` (`-Fc`), comprimido y restaurable de forma selectiva.

## Crear el respaldo

Desde el servidor (vía SSH como `desarrollador`):

```bash
docker exec agent-data-db pg_dump -U paymentrequest -Fc paymentrequest \
  > /srv/paymentrequest/paymentrequest-pre-<motivo>-$(date +%Y%m%d-%H%M%S).dump
```

> [!info] Rol de base de datos
> Se usa `-U paymentrequest`. El rol `postgres` no existe en `agent-data-db`. Ver [[Produccion]].

## Verificar el respaldo

1. **Tamaño**: `ls -lh` del archivo; un dump de pocos bytes indica fallo.
2. **Contenido legible**:
   ```bash
   docker exec -i agent-data-db pg_restore --list < /srv/paymentrequest/<archivo>.dump | head
   ```
   Debe listar tablas y datos (`TABLE DATA`).

## Restaurar

> [!danger] Solo con autorización explícita
> Restaurar sobrescribe datos vivos. Antes: sacar un dump del estado actual, presentar el plan al usuario y esperar su confirmación. Preferir restauraciones **selectivas** (`pg_restore -t <tabla>` a una base temporal y copiar solo lo necesario) sobre restaurar la base completa.

## Respaldos del `.env`

Antes de tocar el `.env` de producción se deja copia con el patrón `.env.bak.<motivo>-<fecha>` en `/srv/paymentrequest/`. El `.env` contiene la `APP_KEY`, que no puede perderse (ver [[APP_KEY y datos cifrados]]).

## Relacionado
- [[Produccion]] · [[Deploy]] · [[Correcciones de datos en produccion]]
- [[Proteccion de la base de datos]] · [[APP_KEY y datos cifrados]]
