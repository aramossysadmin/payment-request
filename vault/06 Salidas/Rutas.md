---
tipo: salida
aliases:
  - Rutas
  - Mapa de rutas
tags:
  - payment-request/rutas
  - payment-request/salidas
fuentes:
  - routes/web.php
  - routes/settings.php
  - routes/console.php
  - bootstrap/app.php
  - app/Providers/Filament/AdminPanelProvider.php
  - app/Providers/FortifyServiceProvider.php
  - config/fortify.php
  - config/filesystems.php
  - app/Models/User.php
actualizado: 2026-10-15
---

# Rutas

Inventario de todas las rutas de la aplicación: `routes/web.php`, `routes/settings.php` (incluido al final de `web.php`), `routes/console.php`, más las que registran Fortify, Filament y otros paquetes. Verificado con `php artisan route:list`.

## Convenciones

- Todas las rutas de `web.php` / `settings.php` llevan el grupo de middleware `web` (sesión, CSRF, `HandleAppearance`, `HandleInertiaRequests`, `AddLinkHeadersForPreloadedAssets`) y el middleware global `SecurityHeaders` (`bootstrap/app.php`).
- Aliases de middleware de Spatie Permission: `role`, `permission`, `role_or_permission`.
- `auth` + `verified`: el modelo `User` **no** implementa `MustVerifyEmail` (la interfaz está comentada), por lo que `verified` no bloquea a nadie en la práctica.
- Los parámetros `{payment:uuid}` y `{batch:uuid}` se resuelven por UUID; `PaymentRequest` usa `uuid` como llave de ruta por defecto; `{investment_request}` y `{project}` usan el ID.
- Columna "Autorización extra": controles dentro del controller (Gate/Policy, `abort_*`), además del middleware.

Ver [[Middleware y rutas protegidas]] y [[Seguridad de la aplicacion]].

## Públicas (sin sesión)

| Método | URI | Nombre | Controller | Middleware | Autorización extra |
|---|---|---|---|---|---|
| GET | `/` | `home` | closure → redirect a `login` | — | — |
| GET | `/documents/view` | `documents.view` | `DocumentViewController` | — | Firma válida (`hasValidSignature`), sin `..` ni rutas absolutas; disco `local` |
| GET | `/approval/{token}` | `approval.show` | `EmailApprovalController@show` | `throttle:10,1` | Token vigente y aprobación pendiente |
| POST | `/approval/{token}/approve` | `approval.approve` | `EmailApprovalController@approve` | `throttle:10,1` | Ídem |
| POST | `/approval/{token}/reject` | `approval.reject` | `EmailApprovalController@reject` | `throttle:10,1` | Ídem |
| GET | `/investment-batch-approval/success` | `investment-batch-approval.success` | `InvestmentBatchApprovalController@success` | `throttle:10,1` | — |
| GET | `/investment-batch-approval/{token}` | `investment-batch-approval.show` | `InvestmentBatchApprovalController@show` | `throttle:10,1` | Token del lote (96 h) |
| POST | `/investment-batch-approval/{token}/review` | `investment-batch-approval.review` | `InvestmentBatchApprovalController@review` | `throttle:10,1` | Ídem |

`/approval/{token}` sirve aprobaciones de Solicitudes de Pago legacy, Solicitudes de Inversión, Pagos de Inversión y Programación Semanal (busca el token en las cuatro tablas de aprobación). Ver [[Aprobacion del CEO]] y [[Modulo legacy Payment Requests]].

## Portal autenticado (`auth` + `verified`, `routes/web.php`)

### General

| Método | URI | Nombre | Controller | Middleware extra | Autorización extra |
|---|---|---|---|---|---|
| GET | `/dashboard` | `dashboard` | `DashboardController` | — | `visibleTo` |
| GET | `/providers/search` | `providers.search` | `ProviderSearchController` | `throttle:30,1` | — |
| GET | `/guide` | `guide` | closure → `Inertia::render('guide/index')` | — | — |
| POST | `/notifications/{id}/read` | `notifications.read` | `NotificationController@markAsRead` | — | Solo notificaciones propias |
| POST | `/notifications/read-all` | `notifications.read-all` | `NotificationController@markAllAsRead` | — | — |

Ver [[Dashboards]], [[Busqueda de proveedores]], [[Guia de usuario]], [[Notificaciones in-app]].

### Solicitudes de Pago (legacy)

`Route::resource('payment-requests', PaymentRequestController::class)` más rutas adicionales. Ver [[Modulo legacy Payment Requests]].

| Método | URI | Nombre | Controller | Middleware extra | Autorización extra |
|---|---|---|---|---|---|
| GET | `/payment-requests` | `payment-requests.index` | `PaymentRequestController@index` | — | Solo `visibleTo` (sin Gate) |
| GET | `/payment-requests/create` | `payment-requests.create` | `@create` | — | Ninguna |
| POST | `/payment-requests` | `payment-requests.store` | `@store` | — | Ninguna (FormRequest `authorize` = true) |
| GET | `/payment-requests/{payment_request}` | `payment-requests.show` | `@show` | — | `Gate view` |
| GET | `/payment-requests/{payment_request}/edit` | `payment-requests.edit` | `@edit` | — | `Gate update` + estado `pending_department` |
| PUT/PATCH | `/payment-requests/{payment_request}` | `payment-requests.update` | `@update` | — | Ídem |
| DELETE | `/payment-requests/{payment_request}` | `payment-requests.destroy` | `@destroy` | — | `Gate delete` (soft delete) |
| POST | `/payment-requests/{payment_request}/approve` | `payment-requests.approve` | `PaymentRequestApprovalController@approve` | `throttle:10,1` | Aprobación pendiente del usuario |
| POST | `/payment-requests/{payment_request}/reject` | `payment-requests.reject` | `PaymentRequestApprovalController@reject` | `throttle:10,1` | Ídem |
| PATCH | `/payment-requests/{payment_request}/sap-folios` | `payment-requests.sap-folios` | `PaymentRequestApprovalController@updateSapFolios` | — | Aprobó la etapa administración/tesorería |
| GET | `/payment-requests/{payment_request}/pdf` | `payment-requests.pdf` | `PaymentRequestPdfController` | — | `Gate view` + `completed` |
| GET | `/payment-requests/{payment_request}/documents/{index}` | `payment-requests.documents` | `PaymentRequestDocumentController` | `whereNumber('index')` | `Gate view` |

### Inversión: dashboard, consolidado y reportes

| Método | URI | Nombre | Controller | Middleware extra | Autorización extra |
|---|---|---|---|---|---|
| GET | `/investment-dashboard` | `investment-dashboard` | `InvestmentDashboardController` | — | `accessibleBy`, `visibleTo` |
| GET | `/investment-sheets/consolidated` | `investment-sheets.consolidated.index` | `InvestmentSheetConsolidatedIndexController` | — | `accessibleBy` |
| GET | `/investment-sheets/consolidated/{project}` | `investment-sheets.consolidated` | `InvestmentSheetConsolidatedController` | — | `canAccessProject` |
| GET | `…/consolidated/{project}/payment-history-pdf` | `investment-sheets.payment-history-pdf` | `InvestmentPaymentHistoryPdfController` | — | `canAccessProject`, filtro de departamento |
| GET | `…/consolidated/{project}/payment-history-excel` | `investment-sheets.payment-history-excel` | `InvestmentPaymentHistoryExcelController` | — | Ídem |
| GET | `…/consolidated/{project}/investment-requests-pdf` | `investment-sheets.investment-requests-pdf` | `InvestmentSheetInvestmentRequestsPdfController` | `role:super_admin\|ceo\|project_manager` | Rol + `canAccessProject` |
| GET | `…/consolidated/{project}/investment-requests-excel` | `investment-sheets.investment-requests-excel` | `InvestmentSheetInvestmentRequestsExcelController` | `role:super_admin\|ceo\|project_manager` | Rol + `canAccessProject` |

Ver [[Consolidado por proyecto]], [[PDFs]], [[Exportaciones Excel]], [[Acceso por proyecto]].

### Inversión: Solicitudes de Inversión

`Route::resource('investment-sheets', InvestmentRequestController::class)` con parámetro `investment_request`. Ver [[Solicitudes de inversion]].

| Método | URI | Nombre | Controller | Middleware extra | Autorización extra |
|---|---|---|---|---|---|
| GET | `/investment-sheets` | `investment-sheets.index` | `InvestmentRequestController@index` | — | `visibleTo` |
| GET | `/investment-sheets/create` | `investment-sheets.create` | `@create` | — | Proyectos `active()->accessibleBy()` |
| POST | `/investment-sheets` | `investment-sheets.store` | `@store` | — | Proyecto abierto |
| GET | `/investment-sheets/{investment_request}` | `investment-sheets.show` | `@show` | — | `Gate view` |
| GET | `/investment-sheets/{investment_request}/edit` | `investment-sheets.edit` | `@edit` | — | `Gate update` + `pending_department` |
| PUT/PATCH | `/investment-sheets/{investment_request}` | `investment-sheets.update` | `@update` | — | `Gate update` + estado + proyecto abierto (actual y destino) |
| DELETE | `/investment-sheets/{investment_request}` | `investment-sheets.destroy` | `@destroy` | — | `Gate delete` |
| PATCH | `/investment-sheets/{investment_request}/description` | `investment-sheets.update-description` | `@updateDescription` | — | `Gate update` + condiciones del controller |
| POST | `/investment-sheets/{investment_request}/approve` | `investment-sheets.approve` | `InvestmentRequestApprovalController@approve` | `throttle:10,1` | Aprobación pendiente |
| POST | `/investment-sheets/{investment_request}/reject` | `investment-sheets.reject` | `InvestmentRequestApprovalController@reject` | `throttle:10,1` | Ídem |
| GET | `/investment-sheets/{investment_request}/pdf` | `investment-sheets.pdf` | `InvestmentRequestPdfController` | — | `Gate view` + `completed` |

> [!info] Orden de rutas
> Las rutas `investment-sheets/consolidated…` se declaran **antes** del `Route::resource('investment-sheets', …)`, por lo que `consolidated` no se interpreta como `{investment_request}`.

### Inversión: pagos, lotes y revisión

| Método | URI | Nombre | Controller | Middleware extra | Autorización extra |
|---|---|---|---|---|---|
| GET | `/investment-payment-requests/{investmentRequestId}` | `investment-payment-requests.index` | `InvestmentPaymentRequestController@index` (JSON) | — | **Ninguna** (ver [[Acceso por proyecto]]) |
| POST | `/investment-payment-requests` | `investment-payment-requests.store` | `@store` | — | Ventana de captura, fecha de provisión, proyecto abierto |
| PATCH/POST | `/investment-payment-requests/{payment:uuid}` | `investment-payment-requests.update` | `@update` | — | Ventana de captura, proyecto abierto |
| PATCH | `/investment-payment-requests/{payment:uuid}/provision-date` | `investment-payment-requests.correct-provision-date` | `@correctProvisionDate` | `role:super_admin` | `abort_unless super_admin` |
| DELETE | `/investment-payment-batches/payments/{payment:uuid}` | `investment-payment-batches.destroy-payment` | `InvestmentPaymentBatchController@destroyPayment` | — | Proyecto abierto + reglas del controller |
| POST | `/investment-payment-batches/{batch:uuid}/submit` | `investment-payment-batches.submit` | `InvestmentPaymentBatchController@submit` | — | Ventana de envío, proyecto abierto |
| POST | `/investment-payment-requests/{payment:uuid}/upload-documents` | `investment-payment-requests.upload-documents` | `InvestmentPaymentDocumentController@upload` | — | Proyecto abierto |
| GET | `/investment-payment-review` | `investment-payment-review.index` | `InvestmentPaymentReviewController@index` | `role:project_manager\|super_admin` | — |
| POST | `/investment-payment-review` | `investment-payment-review.submit` | `InvestmentPaymentReviewController@submit` | `role:project_manager\|super_admin` | Proyectos abiertos |

Ver [[Solicitudes de pago de inversion]], [[Lotes de pago]], [[Revision del PM]], [[Documentos de pago]], [[Politica de ventanas de pago]].

### Tesorería: programación semanal

| Método | URI | Nombre | Controller | Middleware extra | Autorización extra |
|---|---|---|---|---|---|
| GET | `/weekly-payment-schedule` | `weekly-payment-schedule.index` | `WeeklyPaymentScheduleController@index` | — | `visibleTo` |
| GET | `/weekly-payment-schedule/export` | `weekly-payment-schedule.export` | `WeeklyPaymentScheduleExportController` | — | Solicitud visible en el proyecto |
| POST | `/weekly-payment-schedule` | `weekly-payment-schedule.store` | `WeeklyPaymentScheduleController@store` | — | Proyectos abiertos |
| POST | `/weekly-payment-schedule/{payment:uuid}/receipt` | `weekly-payment-schedule.upload-receipt` | `WeeklyPaymentReceiptController@store` | — | Proyecto abierto |

Ver [[Programacion semanal]] y [[Comprobantes de pago]].

## Configuración de cuenta (`routes/settings.php`)

| Método | URI | Nombre | Controller | Middleware |
|---|---|---|---|---|
| ANY | `/settings` | — | `Route::redirect` → `/settings/profile` | `auth` |
| GET | `/settings/profile` | `profile.edit` | `Settings\ProfileController@edit` | `auth` |
| PATCH | `/settings/profile` | `profile.update` | `Settings\ProfileController@update` | `auth` |
| DELETE | `/settings/profile` | `profile.destroy` | `Settings\ProfileController@destroy` | `auth`, `verified` |
| GET | `/settings/password` | `user-password.edit` | `Settings\PasswordController@edit` | `auth`, `verified` |
| PUT | `/settings/password` | `user-password.update` | `Settings\PasswordController@update` | `auth`, `verified`, `throttle:6,1` |
| GET | `/settings/appearance` | `appearance.edit` | closure → `Inertia::render('settings/appearance')` | `auth`, `verified` |
| GET | `/settings/two-factor` | `two-factor.show` | `Settings\TwoFactorAuthenticationController@show` | `auth`, `verified` |

Ver [[Autenticacion y 2FA]].

## Autenticación (Fortify)

Registradas por Laravel Fortify. Funcionalidades activas (`config/fortify.php`): `resetPasswords`, `emailVerification`, `twoFactorAuthentication`. **No** hay registro de usuarios (`/register` no existe). Home tras login: `/payment-requests`.

| Método | URI | Nombre |
|---|---|---|
| GET / POST | `/login` | `login` / `login.store` (limitador `login`: 5 por minuto por correo + IP) |
| POST | `/logout` | `logout` |
| GET / POST | `/forgot-password` | `password.request` / `password.email` |
| GET | `/reset-password/{token}` | `password.reset` |
| POST | `/reset-password` | `password.update` |
| GET / POST | `/two-factor-challenge` | `two-factor.login` / `two-factor.login.store` (limitador `two-factor`: 5 por minuto por sesión) |
| GET | `/email/verify`, `/email/verify/{id}/{hash}` | `verification.notice`, `verification.verify` |
| POST | `/email/verification-notification` | `verification.send` |
| GET / POST | `/user/confirm-password` | `password.confirm` / `password.confirm.store` |
| GET | `/user/confirmed-password-status` | `password.confirmation` |
| POST / DELETE | `/user/two-factor-authentication` | `two-factor.enable` / `two-factor.disable` |
| POST | `/user/confirmed-two-factor-authentication` | `two-factor.confirm` |
| GET | `/user/two-factor-qr-code`, `/user/two-factor-secret-key` | `two-factor.qr-code`, `two-factor.secret-key` |
| GET / POST | `/user/two-factor-recovery-codes` | `two-factor.recovery-codes` / `two-factor.regenerate-recovery-codes` |

## Panel Filament (`/admin`)

Panel `admin` (`AdminPanelProvider`): login y recuperación de contraseña propios, middleware de sesión/CSRF de Filament y `Authenticate`; el acceso requiere `User::canAccessPanel()` (usuario activo) y cada recurso se autoriza con su Policy/permisos Shield. Ver [[Panel Filament]] y [[Permisos Shield]].

| Área | URIs |
|---|---|
| Sesión | `/admin` (redirige al home), `/admin/login`, `POST /admin/logout`, `/admin/password-reset/request`, `/admin/password-reset/reset` |
| Operaciones | `/admin/payment-requests` (index, create, `{record}/edit`), `/admin/investment-requests` (index, create, `{record}/edit`) |
| Auditoría | `/admin/investment-payment-requests` (index, `{record}`), `/admin/investment-payment-batches` (index, `{record}`), `/admin/activity-logs` (index, `{record}`; recurso de `filament-logger`) |
| Organización | `/admin/societies`, `/admin/branches`, `/admin/departments`, `/admin/positions` (index, create, edit) |
| Catálogos | `/admin/currencies`, `/admin/expense-concepts`, `/admin/payment-types`, `/admin/projects`, `/admin/investment-expense-categories`, `/admin/investment-expense-concepts`, `/admin/strategies` (index, create, edit) |
| Accesos | `/admin/users` (index, create, edit), `/admin/shield/roles` (index, create, `{record}`, `{record}/edit`) |
| Configuración | `/admin/payment-policy-settings` (página; solo super_admin y PM). Ver [[Politica de ventanas de pago]] |

Detalle de los recursos de catálogo en [[Catalogos]] y [[Proyectos]].

## Rutas de paquetes

| URI | Origen |
|---|---|
| `GET /up` | Health check de Laravel (`withRouting(health: '/up')`) |
| `GET/PUT /storage/{path}` (`storage.local`, `storage.local.upload`) | Disco `local` con `'serve' => true` (`config/filesystems.php`): sirve archivos privados mediante URLs temporales firmadas |
| `/livewire/update`, `/livewire/upload-file`, `/livewire/preview-file/{filename}`, `/livewire/livewire.js` | Livewire (motor de Filament) |
| `/filament/exports/{export}/download`, `/filament/imports/{import}/failed-rows/download` | Filament Actions (export/import) |
| `POST /_boost/browser-logs` | Laravel Boost (herramienta de desarrollo) |

> [!info] Laravel Boost
> `laravel/boost` está en `require-dev` de `composer.json`; `/_boost/browser-logs` solo existe si se instalan las dependencias de desarrollo. Confirmar en [[Deploy]] que producción instala con `--no-dev`.

## Consola y scheduler (`routes/console.php`)

| Comando | Programación | Opciones | Propósito |
|---|---|---|---|
| `inspire` | — | — | Comando de ejemplo de Laravel |
| `payment-drafts:cancel-expired` | `everyMinute()` | `withoutOverlapping()`, `runInBackground()` | Cancelación automática de drafts al cierre de la ventana de envío |
| `payment-drafts:notify-closing-soon` | `everyFiveMinutes()` | `withoutOverlapping()`, `runInBackground()` | Recordatorio 2 h antes del cierre |

Otros comandos de `app/Console/Commands` (se registran solos, sin programación): `investment:generate-payments-template` (`GenerateInvestmentPaymentsTemplateCommand`). Ver [[Comandos y scheduler]] y [[Cancelacion automatica de borradores]].

## Relacionado
- [[Middleware y rutas protegidas]] · [[Permisos Shield]] · [[Policies]] · [[Seguridad de la aplicacion]]
- [[Backend Laravel]] · [[Frontend Inertia React]] · [[Panel Filament]] · [[Autenticacion y 2FA]] · [[Comandos y scheduler]]
