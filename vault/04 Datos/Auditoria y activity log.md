---
tipo: datos
aliases:
  - Auditoría y activity log
tags:
  - payment-request/datos
  - payment-request/seguridad
fuentes:
  - config/filament-logger.php
  - app/Policies/ActivityPolicy.php
  - app/Providers/Filament/AdminPanelProvider.php
  - database/migrations/2026_06_08_231006_create_activity_log_table.php
  - vendor/z3d0x/filament-logger/src/Loggers/AbstractModelLogger.php
actualizado: 2026-10-15
---

# Auditoría y activity log

Cómo se registra quién cambió qué. Se usa `spatie/laravel-activitylog` (tabla `activity_log`) alimentado por dos fuentes: el trait `LogsActivity` de cada modelo y los loggers de `z3d0x/filament-logger`. Se consulta en Filament, grupo de navegación **Auditoría**.

## Tabla `activity_log`

| Columna | Uso |
|---|---|
| `id` | Consecutivo; la forma más confiable de ordenar y buscar |
| `log_name` | Fuente: `default`, `Model`, `Resource`, `Access`, `Notification` |
| `description` | Texto del evento (ej. `updated`, `InvestmentPaymentRequest Updated by ...`) |
| `subject_type`, `subject_id` | Registro afectado |
| `event` | `created`, `updated`, `deleted`, `Created`, `Updated`, ... |
| `causer_type`, `causer_id` | Usuario que hizo el cambio (null en consola/colas) |
| `properties` | JSON con `attributes` y `old` (trait) o atributos planos (filament-logger) |
| `batch_uuid` | Agrupador de lotes de actividad |
| `created_at`, `updated_at` | Fecha del evento |

## Fuente 1: trait `LogsActivity` (`log_name = default`)

23 de los 25 modelos lo usan. Configuración común en `getActivitylogOptions()`:

```php
LogOptions::defaults()
    ->logFillable()                              // solo atributos de $fillable
    ->logOnlyDirty()                             // solo lo que cambió
    ->dontLogIfAttributesChangedOnly(['updated_at'])
    ->dontSubmitEmptyLogs();
```

| Variante | Modelo |
|---|---|
| `logExcept(['sap_password'])` | [[Branch]] |
| `logExcept(['password','remember_token','two_factor_secret','two_factor_recovery_codes','two_factor_confirmed_at'])` | [[User]] |
| `logOnly(['name','rate','factor','is_active'])` en vez de `logFillable()` | [[Strategy]] |

Modelos **sin** `LogsActivity`: [[PaymentRequestPolicy (modelo)]] y [[PaymentCaptureAttemptLog]].

> [!info] Consecuencia de `logFillable()`
> Las columnas que no están en `$fillable` no aparecen en el log del trait. Ejemplos: `status` de [[InvestmentRequest]] y [[PaymentRequest]] (estado spatie), `user_id`/`department_id`/`project_id` de solicitudes y pagos, `approval_token` de varias aprobaciones.

## Fuente 2: filament-logger (`config/filament-logger.php`)

| Logger | `log_name` | Qué registra |
|---|---|---|
| `ResourceLogger` | `Resource` | Observer sobre el modelo de cada recurso Filament del panel (excepto `ActivityResource`) |
| `ModelLogger` | `Model` | Observer sobre los 22 modelos listados en `models.register` (todos los de `app/Models` excepto `Strategy`, `PaymentRequestPolicy`, `PaymentCaptureAttemptLog`) |
| `AccessLogger` | `Access` | Inicios de sesión |
| `NotificationLogger` | `Notification` | Notificaciones enviadas o fallidas |

Estos observers guardan en `properties` el arreglo completo de `getAttributes()` al crear y `getChanges()` al actualizar, quitando solo los atributos `$hidden` del modelo. **No** respetan `logExcept()` del trait.

> [!warning] Registros duplicados
> Un mismo cambio puede producir hasta tres renglones: `default` (trait), `Model` y `Resource` (si el modelo tiene recurso Filament). Al investigar, filtrar por `log_name`.

> [!question] Por confirmar
> `Branch` no tiene `$hidden`, así que el valor **cifrado** de `sap_password` probablemente queda en `properties` de los renglones `Model`/`Resource`. No es el texto plano, pero conviene confirmarlo y, si aplica, agregar `sap_password` a `$hidden` o excluir el modelo.

## Visualización y permisos

- `AdminPanelProvider` registra `config('filament-logger.activity_resource')` (`ActivityResource` del paquete), en el grupo **Auditoría**.
- Existe `app/Policies/ActivityPolicy.php` con permisos Shield `*_activity`.

> [!danger] Por confirmar: la policy no está registrada
> El modelo auditado es `Spatie\Activitylog\Models\Activity`. Laravel adivina policies en `Spatie\...\Policies\ActivityPolicy`, no en `App\Policies`, y no hay `Gate::policy()` para él. Verificado con `Gate::getPolicyFor(Activity::class)` → `NULL`. Cuando no hay policy, Filament permite la acción, y `User::canAccessPanel()` solo exige `is_active`. Resultado probable: **cualquier usuario activo** puede ver la bitácora en `/admin`. Revisar y registrar la policy (`Gate::policy(Activity::class, ActivityPolicy::class)`).

## Zona horaria de `created_at`

`config('app.timezone')` es `America/Mexico_City` y la app guarda fechas en hora local.

> [!warning] Producción (PostgreSQL)
> En producción `activity_log.created_at` se guarda con la hora de CDMX pero con sufijo `+00`. Comparar contra `now()` o rangos en UTC da desfases de varias horas. Para localizar eventos, **buscar por `id`** (o por `subject_type` + `subject_id`) en lugar de filtrar con `now()`. Ver [[Zona horaria y locale]].

## Consultas útiles (solo lectura)

```php
// Historia de un pago concreto, sin duplicados de filament-logger
Activity::where('subject_type', InvestmentPaymentRequest::class)
    ->where('subject_id', $id)
    ->where('log_name', 'default')
    ->orderBy('id')
    ->get();
```

## Otras bitácoras

- [[PaymentCaptureAttemptLog]]: intentos bloqueados por la política de ventanas.
- Columnas de trazabilidad en los propios registros: `ceo_reviewed_at`, `pm_reviewed_at`, `final_reviewed_at`, `receipt_uploaded_by`, `responded_at` de aprobaciones, `closed_at` de proyectos.

## Relacionado
- [[Seguridad de la aplicacion]] · [[Panel Filament]] · [[Policies]] · [[Modelo de datos]] · [[Correcciones de datos en produccion]]
