---
tipo: operacion
aliases:
  - APP_KEY y datos cifrados
  - Rotación de APP_KEY
tags:
  - payment-request/operacion
  - payment-request/seguridad
fuentes:
  - CLAUDE.md
  - app/Models/Branch.php
  - config/sap.php
  - config/app.php
  - config/fortify.php
  - app/Models/User.php
actualizado: 2026-10-15
---

# APP_KEY y datos cifrados

La `APP_KEY` de Laravel es la llave simétrica con la que se cifran datos sensibles en la base. Si cambia o se pierde, esos datos **no se pueden recuperar**. Resume la sección *APP_KEY y datos encriptados* de `CLAUDE.md`.

> [!danger] Nunca regenerar a ciegas
> **No ejecutar `php artisan key:generate`** en un entorno con datos (producción incluida) sin seguir el procedimiento de 5 pasos de abajo. No existe forma matemática de descifrar sin la llave original.

## Qué está cifrado hoy

| Tabla.columna | Cómo | Para qué |
|---|---|---|
| `branches.sap_password` | Cast `encrypted` en `app/Models/Branch.php` | Contraseña de cada sucursal para iniciar sesión en el Service Layer de SAP B1 |
| `users.two_factor_secret`, `users.two_factor_recovery_codes` | Fortify los guarda con `encrypt()` (2FA habilitado en `config/fortify.php`) | Secreto TOTP y códigos de recuperación de cada usuario con 2FA |

Además, al cambiar la llave se invalidan sesiones, cookies cifradas y URLs firmadas vigentes (por ejemplo, las de documentos y aprobación por correo).

> [!question] Por confirmar
> `CLAUDE.md` solo lista `branches.sap_password`. Confirmar si hay usuarios con 2FA activo en producción: si los hay, al rotar la llave tendrán que reconfigurar su 2FA. Ver [[Autenticacion y 2FA]].

- Se usa cifrado **simétrico** (no hash) porque SAP necesita la contraseña en texto plano al autenticarse. Ver [[ADR-012 Credenciales SAP cifradas por sucursal]].
- El activity log de `Branch` excluye `sap_password` (`logExcept`), para que la contraseña no quede en `activity_log`.
- La URL del Service Layer es global (`config/sap.php`); las credenciales son por sucursal. Ver [[SAP Business One]] y [[Branch]].

## Procedimiento obligatorio para cambiar `APP_KEY`

1. **Respaldar** la base completa ([[Respaldos y restauracion]]).
2. **Identificar** las tablas con datos cifrados (hoy: `branches.sap_password`; revisar también los campos 2FA de `users`).
3. **Descifrar** con la `APP_KEY` actual y exportar a un canal seguro, o planear la re-captura manual de las contraseñas.
4. **Cambiar** la `APP_KEY`.
5. **Re-cifrar** o re-capturar los datos con la nueva llave.

## Reglas de resguardo

- Mantener un respaldo del `.env` de producción en un gestor de credenciales seguro (nunca en git ni en esta bóveda).
- En un deploy desde cero o cambio de servidor: **trasladar el `.env` existente**; nunca reconstruir la `APP_KEY` si ya hay datos cifrados. En la migración a Docker (jun 2026) el `.env` se trasladó al stack (`env_file`). Ver [[Produccion]].
- Antes de editar el `.env` de producción, dejar copia `.env.bak.<motivo>-<fecha>`.
- Si alguien está por regenerar la `APP_KEY`, detenerse, advertir y proponer este procedimiento.

> [!info] CI
> El workflow `.github/workflows/tests.yml` ejecuta `php artisan key:generate` sobre un `.env` copiado de `.env.example` en la máquina efímera de GitHub Actions. No afecta a ningún entorno con datos.

## Relacionado
- [[Seguridad de la aplicacion]] · [[SAP Business One]] · [[Branch]]
- [[Produccion]] · [[Respaldos y restauracion]] · [[Proteccion de la base de datos]]
