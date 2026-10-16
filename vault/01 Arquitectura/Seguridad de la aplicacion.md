---
tipo: arquitectura
aliases:
  - Seguridad de la aplicación
tags:
  - payment-request/arquitectura
  - payment-request/seguridad
fuentes:
  - app/Http/Middleware/SecurityHeaders.php
  - bootstrap/app.php
  - routes/web.php
  - routes/settings.php
  - app/Providers/FortifyServiceProvider.php
  - app/Providers/AppServiceProvider.php
  - app/Http/Controllers/EmailApprovalController.php
  - app/Http/Controllers/InvestmentBatchApprovalController.php
  - app/Http/Controllers/InvestmentPaymentBatchController.php
  - app/Http/Controllers/DocumentViewController.php
  - app/Http/Controllers/PaymentRequestDocumentController.php
  - app/Services/ApprovalService.php
  - app/Http/Requests/
  - app/Models/Branch.php
  - config/session.php
  - config/app.php
actualizado: 2026-10-15
---

# Seguridad de la aplicación

Resumen de los controles de seguridad implementados en el código: cabeceras HTTP, límites de peticiones, enlaces de aprobación por correo, acceso a documentos, validación de archivos y datos cifrados. Los permisos por rol se documentan aparte en [[Permisos Shield]], [[Policies]] y [[Middleware y rutas protegidas]].

## Cabeceras HTTP (`SecurityHeaders`)

Middleware **global** (`$middleware->append(SecurityHeaders::class)` en `bootstrap/app.php`), aplica también a `/admin`.

| Cabecera | Valor | Nota |
|---|---|---|
| `X-Frame-Options` | `DENY` | Solo si el controller no puso otro valor. `DocumentViewController` responde `SAMEORIGIN` para poder mostrar PDFs/imágenes en `<iframe>`/`<img>` del propio portal. |
| `X-Content-Type-Options` | `nosniff` | |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Evita filtrar tokens de la URL a terceros. |
| `Permissions-Policy` | `camera=(), microphone=(), geolocation=()` | |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` | Solo si la petición es HTTPS (`$request->secure()`). Detrás de proxy depende de que Laravel reconozca el esquema (ver [[Produccion]]). |

> [!note] Sin Content-Security-Policy
> No se envía cabecera CSP.

## Límites de peticiones (throttles)

| Ruta / grupo | Límite | Fuente |
|---|---|---|
| `GET /approval/{token}`, `POST /approval/{token}/approve`, `POST /approval/{token}/reject` | 10 por minuto | `routes/web.php` (grupo `throttle:10,1`) |
| `GET /investment-batch-approval/{token}`, `POST .../{token}/review`, `GET .../success` | 10 por minuto | mismo grupo |
| `GET /providers/search` | 30 por minuto | `routes/web.php` |
| `POST payment-requests/{id}/approve` y `/reject` | 10 por minuto | `routes/web.php` |
| `POST investment-sheets/{id}/approve` y `/reject` | 10 por minuto | `routes/web.php` |
| `PUT /settings/password` | 6 por minuto | `routes/settings.php` |
| Login de Fortify | 5 por minuto por `email + IP` | `FortifyServiceProvider` (limiter `login`) |
| Reto 2FA | 5 por minuto por sesión de login | `FortifyServiceProvider` (limiter `two-factor`) |

El resto de rutas autenticadas no tiene throttle propio.

## Aprobación por correo con token

Rutas **públicas** (sin `auth`), protegidas por un token de un solo uso con expiración.

| Flujo | Token | Expiración | Generado en | Consumido en |
|---|---|---|---|---|
| Solicitud de pago legacy (cada etapa/nivel) | `payment_request_approvals.approval_token` (`Str::uuid()`) | 48 h | `ApprovalService` | `EmailApprovalController` |
| Solicitud de inversión | `investment_request_approvals.approval_token` | 48 h | `InvestmentApprovalService` | `EmailApprovalController` |
| Pago de inversión individual | `investment_payment_approvals.approval_token` | 48 h | `InvestmentPaymentApprovalService` | `EmailApprovalController` |
| Programación semanal | `weekly_payment_schedule_approvals.approval_token` | 48 h | `WeeklyPaymentScheduleApprovalService` | `EmailApprovalController` |
| Lote de pagos al CEO | `investment_payment_batches.ceo_approval_token` | **96 h** | `InvestmentPaymentBatchController::submit` | `InvestmentBatchApprovalController` |

Reglas que aplica el código:
1. `EmailApprovalController::findApprovalByToken()` busca el token en las 4 tablas de aprobaciones, en orden.
2. Si no existe → "no es válido o ya fue utilizado"; si `isTokenExpired()` → "ha expirado, ingresa al portal"; si ya no está `pending` → "ya fue autorizada/rechazada".
3. Al aprobar o rechazar, el servicio pone `approval_token = null` y `approval_token_expires_at = null` → el enlace deja de funcionar (un solo uso).
4. Lote del CEO: `show` y `review` exigen `hasValidCeoToken()` (token presente y `expires_at` en el futuro) y `status = 'submitted'`; si no, `review` responde **410**. Al revisar, el token se borra dentro de una transacción.
5. Las rutas `POST` públicas siguen en el grupo `web`, así que requieren token CSRF (las vistas Blade/Inertia lo incluyen).

> [!warning] El token es la credencial
> Cualquiera que tenga el correo (reenvío, buzón compartido) puede aprobar o rechazar mientras el token esté vigente. No hay re-autenticación. El throttle de 10/min y la entropía de UUID v4 hacen inviable adivinarlo, pero no protegen contra un correo reenviado. Ver [[Aprobacion del CEO]] y [[Flujo legacy de solicitud de pago]].

## Acceso a documentos

Los archivos subidos viven en el disco `local` (`storage/app/private`), **nunca** en `public/`. Hay dos vías de acceso:

### 1. `DocumentViewController` (URL firmada) — ruta `documents.view`

`GET /documents/view?path=...&signature=...` es **pública** pero exige firma válida:

| Control | Detalle |
|---|---|
| Firma | `$request->hasValidSignature()`; si falla → 403 "El enlace ha expirado o no es válido." |
| Vigencia | Todas las URLs se generan con `URL::temporarySignedRoute('documents.view', now()->addHours(48), ['path' => ...])` (API Resources, consolidado, revisión del PM, programación semanal, correos y vista Blade de aprobación). |
| Path traversal | Rechaza `path` vacío, que contenga `..` o que empiece con `/` (403). |
| Existencia | 404 si el archivo no existe en el disco `local`. |
| Nombre de descarga | `download_name` opcional (firmado junto con la URL); se le quitan `/` y `\`. |
| Inline vs descarga | Solo `pdf`, `jpg`, `jpeg`, `png`, `gif`, `webp` se sirven `inline` con `Content-Type` fijo por extensión y `X-Frame-Options: SAMEORIGIN`; cualquier otra extensión (xml, docx, xlsx...) se fuerza a descarga. |

> [!note] Quién puede abrir el enlace
> La URL firmada no verifica usuario: quien tenga el enlace puede abrir el archivo durante 48 h. La autorización ocurre al **generar** la URL (solo se incluye en props o correos que el usuario ya puede ver).

### 2. Controllers con policy

`PaymentRequestDocumentController` (`/payment-requests/{id}/documents/{index}`): `Gate::authorize('view', $paymentRequest)`, toma el documento por índice del arreglo `advance_documents` (el usuario nunca manda una ruta), PDF inline y el resto como descarga.

## Validación de archivos subidos

| FormRequest | Campo | Regla |
|---|---|---|
| `StoreInvestmentPaymentRequest` / `UpdateInvestmentPaymentRequest` | `invoice_documents.*` (máx. 2) | `mimes:pdf,xml`, 10 MB |
| | `advance_documents.*` (máx. 10) | `mimes:pdf,xml,jpg,jpeg,png,doc,docx,xls,xlsx`, 10 MB; además en `withValidator` se exige 1 solo documento PDF/imagen para reembolso, estrategia, anticipo, cotización, pagaré o domiciliado |
| `UploadInvestmentPaymentDocumentsRequest` | `pdf` / `xml` / `document` | `mimes:pdf` / `mimetypes:application/xml,text/xml,text/plain` / `mimes:pdf,jpg,jpeg,png`, 10 MB |
| `StoreInvestmentRequestRequest` | `invoice_documents.*` / `advance_documents.*` (máx. 5) | `mimes:pdf,xml` / `mimes:pdf,jpg,jpeg,png`, 10 MB |
| `UploadPaymentReceiptRequest` | `receipt_documents.*` (1 a 5) | `mimes:pdf,xml,jpg,jpeg,png`, 10 MB |

- La regla `mimes` de Laravel valida por **contenido**: compara `guessExtension()` (MIME detectado con `finfo`) contra la lista, y bloquea archivos PHP. No confía en la extensión que manda el navegador.
- Los archivos se guardan con nombre `UUID.extensión` (`storeAs($dir, Str::uuid().'.'.$ext, 'local')`); el nombre original no se usa en disco. La extensión sí proviene del cliente, por eso `DocumentViewController` decide inline/descarga con una lista blanca.

Ver [[Documentos de pago]] y [[Comprobantes de pago]].

## Otras protecciones

| Control | Dónde |
|---|---|
| IDs no adivinables en rutas de pagos y lotes de inversión (`{payment:uuid}`, `{batch:uuid}`) | `routes/web.php` |
| Reglas de dominio en controllers: solo el dueño borra su borrador; solo quien tiene aprobación pendiente aprueba/rechaza; bloqueo de escritura en proyectos cerrados | `InvestmentPaymentBatchController`, `PaymentRequestApprovalController`, etc. Ver [[Proyectos]] y [[Acceso por proyecto]] |
| `DB::prohibitDestructiveCommands()` en producción (bloquea `migrate:fresh`, `migrate:refresh`, `migrate:reset`, `migrate:rollback`, `db:wipe`) | `AppServiceProvider`. Ver [[Proteccion de la base de datos]] |
| Política de contraseñas fuerte en producción (12+, mixta, símbolos, no comprometida) | `AppServiceProvider`. Ver [[Autenticacion y 2FA]] |
| Activity log sin secretos: `User` excluye `password`, `remember_token` y campos 2FA; `Branch` excluye `sap_password` | `getActivitylogOptions()`. Ver [[Auditoria y activity log]] |
| Sesión en base de datos, 120 min, `http_only`, `same_site = lax`, cookie segura según `SESSION_SECURE_COOKIE` | `config/session.php` |
| Cookies cifradas excepto `appearance` y `sidebar_state` | `bootstrap/app.php` |

## Datos cifrados con `APP_KEY`

| Dato | Mecanismo |
|---|---|
| `branches.sap_password` | Cast `encrypted` en [[Branch]] (texto cifrado con AES-256-CBC usando `APP_KEY`). En el panel el campo es `password()->revealable()` y solo se guarda si se llena (`dehydrated(fn ($state) => filled($state))`). |
| `users.two_factor_secret`, `users.two_factor_recovery_codes` | Cifrados por Fortify con `APP_KEY`. |
| Cookies, sesión, URLs firmadas | Dependen de `APP_KEY`. |

`config/app.php` soporta `APP_PREVIOUS_KEYS` (lista separada por comas) para descifrar con llaves anteriores durante una rotación.

> [!danger] No regenerar `APP_KEY` en un entorno con datos
> Si `APP_KEY` cambia sin migrar, las contraseñas SAP de sucursales y los secretos 2FA quedan **irrecuperables**. Procedimiento en [[APP_KEY y datos cifrados]].

## Puntos abiertos

> [!question] Por confirmar
> - **Plantilla con datos reales en `public/`**: `php artisan investment:generate-payments-template` escribe `public/downloads/templates/investment_payments_historical.xlsx` con folios, proveedores, saldos y correos de solicitantes. Todo lo que está en `public/` se sirve sin autenticación. Confirmar que el archivo se elimina después de usarlo (ver [[Comandos y scheduler]]).
> - **Búsqueda de proveedores sin alcance**: `/providers/search` devuelve proveedor y RFC de todas las solicitudes legacy a cualquier usuario autenticado (ver [[Busqueda de proveedores]]).
> - **2FA y login de Filament**: el panel no pasa por el reto 2FA de Fortify y comparte sesión con el portal (ver [[Autenticacion y 2FA]]).
> - **Usuarios inactivos** pueden iniciar sesión en el portal Inertia; `is_active` solo se valida en el panel (ver [[Autenticacion y 2FA]]).

## Relacionado
- [[Autenticacion y 2FA]] · [[Middleware y rutas protegidas]] · [[Policies]] · [[APP_KEY y datos cifrados]] · [[Proteccion de la base de datos]] · [[Documentos de pago]] · [[SAP Business One]]
