---
tipo: operacion
aliases:
  - Protección de la base de datos
  - Nunca borrar registros
tags:
  - payment-request/operacion
  - payment-request/seguridad
fuentes:
  - CLAUDE.md
  - tests/Pest.php
  - phpunit.xml
  - app/Models/Strategy.php
  - app/Policies/
actualizado: 2026-10-15
---

# Protección de la base de datos

Resumen de la regla crítica del proyecto (sección *Database Protection* de `CLAUDE.md`): **los registros nunca se borran de ninguna tabla, bajo ninguna circunstancia**. Aplica a producción, a desarrollo y a cualquier herramienta (artisan, tinker, seeders, migraciones).

> [!danger] Regla absoluta
> Ningún registro se elimina: ni para pruebas, depuración, migraciones, seeders, comandos artisan ni "limpieza". Cualquier borrado de registros es un error crítico.

## Operaciones prohibidas

| Prohibido | Alternativa segura |
|---|---|
| `php artisan migrate:fresh`, `migrate:refresh`, `db:wipe` | Migraciones nuevas y aditivas |
| `truncate()`, `delete()`, `DB::table()->delete()` | Borrado lógico (`SoftDeletes`) o cambio de estado |
| `forceDelete()` | Mandar a la papelera (`delete()` sobre modelo con `SoftDeletes`) |
| SQL de escritura directa en producción | Eloquent vía tinker con el procedimiento de [[Correcciones de datos en produccion]] |
| Modificar columnas sin repetir sus atributos | En la migración, incluir todos los atributos previos de la columna |

Si alguna tarea parece requerir borrar, se detiene el trabajo, se advierte al usuario y se propone una alternativa (desactivar, cancelar, mover a papelera).

## Cómo se refleja en el código

- La mayoría de los modelos usa `SoftDeletes` (borrado lógico): `Branch`, `Currency`, `Department`, `ExpenseConcept`, `InvestmentExpenseCategory`, `InvestmentExpenseConcept`, `InvestmentPaymentRequest`, `InvestmentRequest`, `PaymentRequest`, `PaymentType`, `Position`, `Project`, `Society`, `Strategy`, `User`, `WeeklyPaymentSchedule` (`app/Models/`).
- `Strategy` bloquea el borrado definitivo en el propio modelo: el evento `forceDeleting` lanza `LogicException` (`app/Models/Strategy.php`).
- Muchos catálogos se "dan de baja" con un toggle `is_active` en lugar de borrarse (sucursales, proyectos, estrategias).
- Las migraciones recientes son aditivas y su `down()` de backfill es no-op deliberado (ej. backfill de `project_user`).

> [!question] Por confirmar
> En las policies (`app/Policies/*Policy.php`) `forceDelete` / `forceDeleteAny` dependen de permisos de Shield (`force_delete_*`), no están bloqueados en código. Confirmar que ningún rol tenga esos permisos asignados, o bloquearlos como en `Strategy`.

## Aislamiento de los tests

`RefreshDatabase` (que recrea el esquema) **solo** se permite porque los tests corren contra SQLite en memoria:

- `.env.testing` debe conservar siempre `DB_CONNECTION=sqlite` y `DB_DATABASE=:memory:`. Nunca modificar esas dos variables.
- `phpunit.xml` también fija `DB_CONNECTION=sqlite` y `DB_DATABASE=:memory:`.
- Antes de correr tests se verifica que `.env.testing` apunte a `:memory:`.
- Los datos de prueba se crean con factories dentro de cada test.
- La carpeta `tests/` no se versiona, por lo que no existe en el servidor y no puede ejecutarse por accidente contra producción. Ver [[Tests]] y [[ADR-002 Tests fuera de git]].

## Antes de cualquier comando que toque la BD

1. Confirmar a qué conexión apunta (`DB_CONNECTION`) y que **no** es producción.
2. Si es producción: plan aprobado + respaldo ([[Respaldos y restauracion]]).
3. Preferir consultas read-only (`default_transaction_read_only=on`).

## Relacionado
- [[ADR-005 Nunca borrar registros solo borrado logico]] · [[Correcciones de datos en produccion]]
- [[Migraciones]] · [[Tests]] · [[Auditoria y activity log]] · [[APP_KEY y datos cifrados]]
