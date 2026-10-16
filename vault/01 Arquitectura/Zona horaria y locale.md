---
tipo: arquitectura
aliases:
  - Zona horaria y locale
tags:
  - payment-request/arquitectura
fuentes:
  - config/app.php
  - app/Providers/AppServiceProvider.php
  - app/Services/PaymentRequestPolicyService.php
  - app/Console/Commands/CancelExpiredPaymentDraftsCommand.php
  - resources/js/lib/format-policy-time.ts
  - resources/views/app.blade.php
actualizado: 2026-10-15
---

# Zona horaria y locale

El sistema opera en **hora del centro de México** y en **español de México**. La zona horaria está fija en código; el idioma depende de variables de entorno más formateos explícitos `es_MX` / `es-MX` donde importa.

## Zona horaria

| Pieza | Valor | Fuente |
|---|---|---|
| Zona de la aplicación | `America/Mexico_City` (**fijo**, no viene de `.env`) | `config/app.php` → `'timezone'` |
| Clase de fechas por defecto | `Carbon\CarbonImmutable` | `AppServiceProvider::configureDefaults()` → `Date::use(CarbonImmutable::class)` |
| "Ahora" en lógica de ventanas | `CarbonImmutable::now(config('app.timezone'))` | `PaymentRequestPolicyService`, comandos `payment-drafts:*` |
| Base de datos | Los `timestamps` se guardan como hora local de la app (sin conversión a UTC) | Comportamiento por defecto de Eloquent con `app.timezone` |

> [!info] Sin horario de verano
> `America/Mexico_City` no tiene horario de verano desde 2022, así que el desfase es UTC-6 todo el año. Las ventanas de la política ("lunes 08:00 – miércoles 18:00") son siempre hora CDMX.

### Consecuencias prácticas

- **Inmutabilidad**: como `now()`, `today()` y los casts de fecha devuelven `CarbonImmutable`, métodos como `addDays()` o `startOfWeek()` **devuelven una nueva instancia**; hay que reasignar (`$fecha = $fecha->addDay()`).
- **Type hints**: en firmas usar `Carbon\CarbonInterface` (lo hacen `PaymentRequestPolicyService` y las notificaciones de borradores), no `Carbon\Carbon`, para aceptar tanto mutables como inmutables.
- **Semanas ISO**: la programación y los lotes usan `isoWeek` / `isoWeekYear` (semana que inicia en lunes). `PaymentRequestPolicyService::getNextProvisionDate()` usa `startOfWeek(CarbonImmutable::MONDAY)`. Ver [[Programacion semanal]] y [[Lotes de pago]].
- **Serialización a JSON**: Laravel serializa fechas en ISO 8601 en UTC; el navegador las convierte a su zona local con `new Date(...)`. Para fechas sin hora (`payment_provision_date`) el frontend concatena `'T00:00:00'` antes de formatear para evitar que se recorran un día.

### Hora local vs hora oficial en el frontend

`resources/js/lib/format-policy-time.ts` (constante `OFFICIAL_TZ = 'America/Mexico_City'`):

| Función | Si el navegador está en hora México | Si está en otra zona |
|---|---|---|
| `formatPolicyTime(iso)` | `lunes 15 jun 08:00` | `lunes 15 jun 16:00 (hora local) · 08:00 México` |
| `formatPolicyTimeShort(iso)` | `08:00` | `16:00 local · 08:00 México` |

Compara el offset del navegador contra el de CDMX en ese instante, no solo el nombre de la zona. Se usa en el banner y avisos de la [[Politica de ventanas de pago]].

> [!question] Por confirmar
> Hay usuarios en sucursales fuera de la zona centro (y potencialmente en España). La política de ventanas siempre se evalúa en hora CDMX; solo la presentación muestra la hora local. Confirmar si se requiere zona horaria por sucursal.

## Locale e idioma

| Pieza | Valor | Fuente |
|---|---|---|
| `app.locale` | `APP_LOCALE` (default del archivo: `en`); el entorno local está configurado en `es` | `config/app.php` |
| `app.fallback_locale` | `APP_FALLBACK_LOCALE` (default `en`); local en `es` | `config/app.php` |
| `app.faker_locale` | `APP_FAKER_LOCALE` (default `en_US`) | `config/app.php` |
| Carpeta `lang/` | **No existe** (no se han publicado traducciones de Laravel) | — |
| `lang` del HTML | `app()->getLocale()` con `_` → `-` | `resources/views/app.blade.php` |

- **Textos de negocio**: están escritos directamente en español en código (labels de Filament, mensajes de FormRequests, correos, páginas React). No hay archivos de traducción propios.
- **Fechas en backend**: donde el texto llega al usuario se fuerza el locale explícitamente con `->locale('es_MX')->translatedFormat(...)`: mensajes de ventana cerrada en `StoreInvestmentPaymentRequest`, `UpdateInvestmentPaymentRequest` e `InvestmentPaymentBatchController` ("... hora CDMX."), etiquetas y textos de `PaymentRequestPolicyService`, motivo de cancelación en `CancelExpiredPaymentDraftsCommand`, asuntos y cuerpos de los correos `draft-payments-*`.
- **Fechas y números en frontend**: `toLocaleDateString('es-MX')`, `Intl.DateTimeFormat('es-MX')` e `Intl.NumberFormat('es-MX', { currency: 'MXN' })`. El contexto de moneda formatea con `en-US` (separador de miles con coma y punto decimal) más el prefijo de moneda. Ver [[Multimoneda]].
- **Filament**: muestra sus textos según `app.locale` (trae traducciones `es` de fábrica). Formatos del logger: `d/m/Y H:i:s`.

> [!question] Por confirmar: mensajes de validación del framework
> Con `locale = es` y sin `lang/es/validation.php`, las reglas que no tienen mensaje personalizado en su FormRequest mostrarían la clave (`validation.required`) en lugar de un texto legible. Verificar en producción el valor de `APP_LOCALE` / `APP_FALLBACK_LOCALE` y si conviene publicar las traducciones (`php artisan lang:publish` + traducción) o fijar `fallback_locale = en`.

## Relacionado
- [[Politica de ventanas de pago]] · [[Cancelacion automatica de borradores]] · [[Comandos y scheduler]] · [[Frontend Inertia React]] · [[Backend Laravel]]
