---
tipo: arquitectura
aliases:
  - Estructura de carpetas
tags:
  - payment-request/arquitectura
fuentes:
  - app/
  - bootstrap/app.php
  - resources/js/
  - resources/views/
  - routes/
  - config/
  - database/
  - .gitignore
actualizado: 2026-10-15
---

# Estructura de carpetas

Mapa de qué vive dónde en el repositorio. Sigue la estructura "slim" de Laravel 11/12: no hay `app/Http/Kernel.php` ni `app/Console/Kernel.php`; el middleware y el ruteo se registran en `bootstrap/app.php`.

## Vista general

```text
payment-request/
├── app/                 Código PHP de la aplicación
├── bootstrap/           app.php (routing, middleware) y providers.php
├── config/              Configuración (incluye sap.php e investment-requests.php propios)
├── database/            migraciones, factories, seeders (+ BulkImport y plantillas)
├── public/              index.php, logos, assets publicados de Filament, plantillas descargables
├── resources/
│   ├── css/app.css      Tailwind 4 (CSS-first) + tokens del design system
│   ├── js/              App Inertia + React + TypeScript
│   └── views/           Blade: raíz Inertia, correos, PDFs, aprobación por correo, Filament
├── routes/              web.php, settings.php, console.php
├── storage/app/private  Documentos subidos por usuarios (disco "local")
├── tests/               Pest (NO se versiona, ver [[Tests]])
└── vault/               Esta bóveda de Obsidian
```

## `app/`

| Carpeta | Contenido | Nota |
|---|---|---|
| `Actions/Fortify/` | `CreateNewUser`, `ResetUserPassword` | Acciones que Fortify invoca. Ver [[Autenticacion y 2FA]]. |
| `Concerns/` | `PasswordValidationRules`, `ProfileValidationRules` | Reglas de validación reutilizables (traits). |
| `Console/Commands/` | 3 comandos `payment-drafts:*` e `investment:*` | Se autoregistran. Ver [[Comandos y scheduler]]. |
| `Enums/` | `DocumentMode`, `InvestmentPaymentType`, `IvaRate`, `PaymentRequestStatus`, `PaymentType`, `PaymentTypeCategory` | Ver [[Enums]]. |
| `Exports/` | `InvestmentRequestsConsolidatedExport`, `PaymentHistoryExport`, `WeeklyPaymentScheduleExport` | Clases de `maatwebsite/excel`. Ver [[Exportaciones Excel]]. |
| `Filament/Pages/` | `PaymentPolicySettings` | Página singleton de política de ventanas. |
| `Filament/Resources/` | 17 recursos (+ `Pages/` y `RelationManagers/` por recurso) | Ver [[Panel Filament]]. |
| `Http/Controllers/` | 29 controllers del portal Inertia (+ `Settings/` y `Concerns/`) | Mezcla de invocables y de recurso. Ver [[Backend Laravel]]. |
| `Http/Controllers/Concerns/` | `ResolvesDisplayCurrency` | Conversión a moneda de visualización. Ver [[Multimoneda]]. |
| `Http/Middleware/` | `HandleAppearance`, `HandleInertiaRequests`, `SecurityHeaders` | Registrados en `bootstrap/app.php`. |
| `Http/Requests/` | FormRequests `Store*`, `Update*`, `Reject*`, `Upload*` (+ `Settings/`, `Concerns/ResolvesInvestmentStrategy`) | Validación con mensajes en español. |
| `Http/Resources/` | `PaymentRequestResource`, `InvestmentRequestResource` y sus `*ApprovalResource` | Serialización de modelos hacia props Inertia. |
| `Mail/` | `ResetPasswordMail` | Único Mailable; el resto de correos son Notifications. |
| `Models/` | 25 modelos Eloquent | Ver [[Modelo de datos]]. |
| `Notifications/` | 19 notificaciones `ShouldQueue` (+ `Concerns/IncludesRequestDetails`) | Ver [[Catalogo de correos]] y [[Colas y correo]]. |
| `Policies/` | 18 policies (una por recurso Filament + `ActivityPolicy`) | Generadas por Shield. Ver [[Policies]]. |
| `Providers/` | `AppServiceProvider`, `FortifyServiceProvider`, `PasswordResetServiceProvider`, `Filament/AdminPanelProvider` | Listados en `bootstrap/providers.php`. |
| `Services/` | 8 servicios de dominio (aprobaciones, política de ventanas, desgloses) | Ver [[Backend Laravel]]. |
| `States/` | `PaymentRequest/*` e `InvestmentRequest/*` | Estados de `spatie/laravel-model-states`. Ver [[Estados]]. |

## `resources/`

| Carpeta | Contenido |
|---|---|
| `js/pages/` | Páginas Inertia por módulo: `auth/`, `settings/`, `payment-requests/`, `investment-sheets/`, `investment-payment-review/`, `investment-batch-approval/`, `investment-dashboard/`, `weekly-payment-schedule/`, `guide/`, `dashboard.tsx`, `welcome.tsx`. |
| `js/components/` | Componentes de negocio (`data-table`, `approval-timeline`, `provider-autocomplete`, `payment-policy-banner`, etc.) y `ui/` (shadcn). |
| `js/layouts/` | `app-layout` (sidebar), `auth-layout`, `settings/layout`. |
| `js/contexts/` | `display-currency.tsx` (contexto global de moneda). |
| `js/hooks/`, `js/lib/`, `js/types/` | Hooks, utilidades de formato y tipos TS. |
| `js/actions/`, `js/routes/`, `js/wayfinder/` | **Generados** por Wayfinder; están en `.gitignore`. |
| `views/app.blade.php` | Plantilla raíz de Inertia. |
| `views/emails/` | Correos markdown/HTML propios (resumen al PM, recordatorios, reset de contraseña). |
| `views/pdf/` | Plantillas DomPDF. Ver [[PDFs]]. |
| `views/approval/` | Páginas Blade públicas de aprobación por token (`show`, `show-schedule`, `result`). |
| `views/filament/pages/` | Vista de `PaymentPolicySettings`. |
| `views/vendor/mail`, `views/vendor/notifications` | Overrides del layout de correo de Laravel. |

Detalle en [[Frontend Inertia React]].

## `routes/`

| Archivo | Contenido |
|---|---|
| `web.php` | Todas las rutas del portal (públicas por token + grupo `auth, verified`). |
| `settings.php` | Perfil, contraseña, apariencia y 2FA (incluido al final de `web.php`). |
| `console.php` | Programación del scheduler. Ver [[Comandos y scheduler]]. |

Fortify y Filament registran sus propias rutas (`/login`, `/two-factor-challenge`, `/admin/*`). Catálogo completo en [[Rutas]].

## `config/` (archivos con cambios propios)

| Archivo | Por qué importa |
|---|---|
| `app.php` | `timezone` fijo en `America/Mexico_City`. Ver [[Zona horaria y locale]]. |
| `fortify.php` | Features activas y `home = /payment-requests`. |
| `sap.php` | URL del Service Layer de SAP B1. Ver [[SAP Business One]]. |
| `investment-requests.php` | Autorizador global y bandera `require_authorization`. Ver [[Solicitudes de inversion]]. |
| `filament-shield.php`, `filament-logger.php` | Permisos y bitácora del panel. |
| `queue.php`, `mail.php`, `services.php` | Ver [[Colas y correo]]. |

## `database/`

| Carpeta | Contenido |
|---|---|
| `migrations/` | Historial completo del esquema (desde feb 2026). Ver [[Migraciones]]. |
| `factories/` | Una factory por modelo de negocio (usadas en tests). |
| `seeders/` | Catálogos base y roles (`RoleAndPermissionSeeder`, `CeoRoleSeeder`, `ProjectManagerRoleSeeder`, etc.). |
| `seeders/BulkImport/` | Cargas masivas históricas (categorías, conceptos, solicitudes y pagos de inversión). |
| `seeders/data/` | Insumos de las cargas masivas; solo se versionan las plantillas vacías de `templates/`. |
| `backups/` | Respaldos SQLite locales (no versionados en la práctica; ver [[Respaldos y restauracion]]). |

## `storage/app/private/` (disco `local`)

Los archivos subidos se guardan con nombre `UUID.extensión` en subcarpetas por tipo: `advance-documents`, `investment-advance-documents`, `investment-payment-documents`, `investment-payment-receipts`, `society-fiscal-documents`, más `livewire-tmp` (uploads temporales de Filament). Nunca se sirven directamente: pasan por `DocumentViewController` con URL firmada o por controllers con policy. Ver [[Seguridad de la aplicacion]] y [[Documentos de pago]].

## Qué NO se versiona (`.gitignore`)

- `.env`, `.env.testing`, `.env.production`, `phpunit.xml`, `/tests/`.
- Código generado por Wayfinder (`resources/js/actions`, `resources/js/routes`, `resources/js/wayfinder`) y `public/build`.
- `.claude/PENDIENTES.md`, `.claude/BITACORA-COMMITS.md`, `.claude/archive/`, `.claude/settings.local.json`.
- Estado local de Obsidian (`vault/.obsidian/workspace*.json`, `vault/.obsidian/cache`, `vault/.trash/`).

> [!question] Por confirmar
> En la raíz existe un archivo vacío llamado `null` que sí está versionado. Parece un residuo de una redirección de shell (`> null`). Confirmar si se puede eliminar.

## Relacionado
- [[Stack y versiones]] · [[Backend Laravel]] · [[Frontend Inertia React]] · [[Panel Filament]] · [[Rutas]] · [[Modelo de datos]]
