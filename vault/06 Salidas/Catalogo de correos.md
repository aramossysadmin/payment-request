---
tipo: salida
aliases:
  - Catálogo de correos
  - Notificaciones por correo
tags:
  - payment-request/correos
  - payment-request/salidas
fuentes:
  - app/Notifications
  - app/Notifications/Concerns/IncludesRequestDetails.php
  - app/Mail/ResetPasswordMail.php
  - app/Providers/PasswordResetServiceProvider.php
  - resources/views/emails
  - app/Services/ApprovalService.php
  - app/Services/InvestmentApprovalService.php
  - app/Services/InvestmentPaymentApprovalService.php
  - app/Services/WeeklyPaymentScheduleApprovalService.php
  - app/Http/Controllers/InvestmentPaymentBatchController.php
  - app/Http/Controllers/InvestmentBatchApprovalController.php
  - app/Http/Controllers/InvestmentPaymentReviewController.php
  - app/Console/Commands/CancelExpiredPaymentDraftsCommand.php
  - app/Console/Commands/NotifyClosingSoonReminderCommand.php
  - app/Models/InvestmentPaymentRequest.php
  - config/investment-requests.php
  - config/queue.php
actualizado: 2026-10-15
---

# Catálogo de correos

Inventario de todas las clases en `app/Notifications` (19) y `app/Mail` (1): qué las dispara, a quién llegan, por qué canal, si van por cola, qué vista usan y qué contienen.

## Reglas comunes

- **Cola**: todas las clases de `app/Notifications` implementan `ShouldQueue` con `Queueable` (sin `tries`, `backoff` ni cola específica). La conexión por defecto es `database` (`config/queue.php`, variable `QUEUE_CONNECTION`). Sin un worker corriendo, los correos no salen. Ver [[Colas y correo]].
- **Mailer**: `config/mail.php` usa `MAIL_MAILER` (default `log`).
- **Canal `database`**: alimenta la campana del portal. Ver [[Notificaciones in-app]].
- **Plantilla genérica** `emails.request-notification` (Markdown `<x-mail::message>`): banner opcional, título de sección, saludo, descripción, lista de detalles (etiqueta: valor), departamento / etapa actual, tabla "Pagos a Procesar" y "Pagos Excluidos" (si hay `paymentItems`), lista de documentos con enlaces firmados, botón de acción, líneas de pie y despedida.
- **Detalles de solicitud** (`IncludesRequestDetails`):
  - `getFullDetails()`: Solicitante, Sucursal, Concepto de Gasto, Descripción, Tipo de Pago (solo legacy), Proveedor, Folio (factura) y Total con prefijo de moneda.
  - `getMinimalDetails()`: Proveedor y Total.
  - `getDocuments()`: enlaces `URL::temporarySignedRoute('documents.view', 48 h)` a cada adjunto de `advance_documents`.
  - `getStageInfo()`: departamento y, en legacy, "Etapa – Nivel" de la última aprobación.
- **Tokens de aprobación por correo**: los botones "Autorizar / Rechazar" apuntan a `/approval/{token}` (vigencia 48 h) o, para lotes del CEO, a `/investment-batch-approval/{token}` (vigencia 96 h). Ver [[Rutas]].
- **Destinatarios por rol**: `User::role('ceo')` y `User::role('project_manager')` notifican a **todos** los usuarios con ese rol; si nadie lo tiene, no se envía nada (sin error).
- **Autorizador global de inversión**: `config('investment-requests.authorizer_email')` (variable `INVESTMENT_AUTHORIZER_EMAIL`).

### Clave `strategy` (pagos con estrategia)

Los correos que listan pagos de inversión incluyen en cada elemento la clave `strategy` con el resultado de `InvestmentPaymentRequest::strategySummary()`: `null` si el pago no es de tipo estrategia, o el texto `Estrategia {tasa}% · Monto Estrategia ${monto} {moneda}` (ejemplo ficticio: "Estrategia 4.5% · Monto Estrategia $10,000.00 MXN"). Las vistas lo pintan como una línea gris bajo el concepto con `@if (!empty($item['strategy']))`:

| Clase | Vista que renderiza `strategy` |
|---|---|
| `InvestmentBatchSubmittedNotification` | `emails.request-notification` |
| `InvestmentBatchReadyForReviewNotification` | `emails.investment-batch-ready-for-review` (nuevos e históricos) |
| `InvestmentBatchRequesterSummaryNotification` | `emails.investment-batch-requester-summary` (aprobados y rechazados) |
| `WeeklyPaymentScheduleCreated` | `emails.request-notification` |
| `InvestmentBatchFinalApprovalSummaryToPmNotification` | No en el correo; sí en el PDF adjunto (`$p->strategySummary()`). Ver [[PDFs]] |

Ver [[Estrategias]].

## Resumen

| Clase | Disparador | Destinatario | Canales | Vista |
|---|---|---|---|---|
| `PaymentRequestCreated` | Crear solicitud legacy | Autorizador N1 del depto | database, mail | `request-notification` |
| `PaymentRequestApproved` | Aprobación legacy que pasa a N2 o a la siguiente etapa | Siguiente autorizador | database, mail | `request-notification` |
| `PaymentRequestCompleted` | Última aprobación (Tesorería) | Solicitante | database, mail | `request-notification` |
| `PaymentRequestRejected` | Rechazo N1, o rechazo N2 | Solicitante | database, mail | `request-notification` |
| `PaymentRequestLevel2Rejected` | Rechazo N2 legacy | Autorizador N1 de la etapa | database, mail | `request-notification` |
| `InvestmentRequestCreated` | Crear Solicitud de Inversión | Todos los PM (proyecto con `requires_pm_approval`) o autorizador global | database, mail | `request-notification` |
| `InvestmentRequestCompleted` | Aprobación de la Solicitud de Inversión | Solicitante | database, mail | `request-notification` |
| `InvestmentRequestRejected` | Rechazo de la Solicitud de Inversión | Solicitante | database, mail | `request-notification` |
| `InvestmentRequestApproved` | **Sin uso** | — | database, mail | `request-notification` |
| `InvestmentRequestLevel2Rejected` | **Sin uso** | — | database, mail | `request-notification` |
| `InvestmentPaymentRequestCreated` | **Sin uso actual** (servicio no invocado) | Autorizador global | database, mail | `request-notification` |
| `InvestmentBatchSubmittedNotification` | Enviar lote | Todos los CEO | mail | `request-notification` |
| `InvestmentBatchReadyForReviewNotification` | CEO aprueba al menos un pago del lote | Todos los PM | mail | `investment-batch-ready-for-review` |
| `InvestmentBatchRequesterSummaryNotification` | Decisión del CEO (`ceo`) o aprobación final del PM (`final`) | Cada solicitante | mail | `investment-batch-requester-summary` |
| `InvestmentBatchFinalApprovalSummaryToPmNotification` | Aprobación final del PM con pagos aprobados | Todos los PM, por proyecto | mail + PDF adjunto | `final-approval-summary-pm` |
| `WeeklyPaymentScheduleCreated` | Crear programación semanal | Autorizador global | database, mail | `request-notification` |
| `DraftPaymentsClosingSoonReminderNotification` | Comando 2 h antes del cierre de envío | Cada usuario con drafts | mail, database | `draft-payments-closing-soon-reminder` |
| `DraftPaymentsAutoCancelledNotification` | Comando de cancelación automática | Cada usuario afectado | mail, database | `draft-payments-auto-cancelled` |
| `DraftPaymentsAutoCancelledPmSummaryNotification` | Comando de cancelación automática | Todos los PM | mail, database | `draft-payments-auto-cancelled-pm-summary` |
| `ResetPasswordMail` (Mailable) | Solicitud de recuperación de contraseña | Usuario | mail | `emails.reset-password` (Blade HTML) |

## Módulo legacy (Solicitudes de Pago)

Todas las dispara `ApprovalService` (ver [[Modulo legacy Payment Requests]]). Formato database: notificación Filament con acción "Ver Solicitud" → `/admin/payment-requests/{uuid}/edit`.

### `PaymentRequestCreated`
- **Disparador**: `ApprovalService::createApprovals()` al crear la solicitud (portal o Filament).
- **Destinatario**: `authorizerLevel1` del departamento.
- **Asunto**: "Nueva Solicitud de Pago #{folio}".
- **Contenido**: detalles completos, departamento/etapa, documentos (48 h), botón "Autorizar / Rechazar Solicitud" (`/approval/{token}`), pie "Este enlace es válido por 48 horas." y enlace al panel admin.
- **Database**: "Nueva Solicitud de Pago", estado `warning`.

### `PaymentRequestApproved`
- **Disparador**: `ApprovalService::approve()` → `createLevel2Approval()` o `createNextStageApproval()`.
- **Destinatario**: autorizador N2 del mismo departamento, o N1 del departamento de la siguiente etapa.
- **Asunto**: "Solicitud de Pago #{folio} - Requiere tu Autorización". Indica quién aprobó antes. Mismo botón con token.

### `PaymentRequestCompleted`
- **Disparador**: transición a `completed`.
- **Destinatario**: solicitante. **Asunto**: "Solicitud de Pago #{folio} - Finalizada". Detalles mínimos, botón "Ver Solicitud" (panel admin). Database `success`.

### `PaymentRequestRejected`
- **Disparador**: rechazo de nivel 1, y también rechazo de nivel 2.
- **Destinatario**: solicitante. **Asunto**: "Solicitud de Pago #{folio} - Rechazada". Incluye motivo. Database `danger`.

### `PaymentRequestLevel2Rejected`
- **Disparador**: `resetLevel1AfterLevel2Rejection()`.
- **Destinatario**: autorizador N1 (con token nuevo de 48 h). **Asunto**: "Solicitud de Pago #{folio} - Rechazada por Nivel 2". Detalles: motivo, solicitante, proveedor, total.

## Solicitudes de Inversión

Disparadas por `InvestmentApprovalService` (ver [[Flujo de solicitud de inversion]]). Enlaces "Ver Solicitud" → `/admin/investment-requests/{uuid}/edit`.

### `InvestmentRequestCreated`
- **Disparador**: `InvestmentApprovalService::createApprovals()` (portal o Filament):
  - Proyecto con `requires_pm_approval`: una aprobación a nombre del primer PM y correo con el **mismo token** a **todos** los PM.
  - Sin candado y con `require_authorization = true`: aprobación y correo al autorizador global.
  - Con `require_authorization = false` (bypass): se autoaprueba y **no** se envía correo.
- **Asunto**: "Nuevo Concepto de Inversión #{folio}" o, si es aditiva, "ADITIVA — Concepto de Inversión #{folio}" con banner "ADITIVA AL PRESUPUESTO".
- **Contenido**: detalles completos, documentos, botón con token (48 h).

### `InvestmentRequestCompleted`
- **Disparador**: `InvestmentApprovalService::approve()`. **Destinatario**: solicitante. **Asunto**: "Concepto de Inversión #{folio} - Finalizada".

### `InvestmentRequestRejected`
- **Disparador**: `InvestmentApprovalService::reject()`. **Destinatario**: solicitante. **Asunto**: "Concepto de Inversión #{folio} - Rechazada", con motivo.

### `InvestmentRequestApproved` y `InvestmentRequestLevel2Rejected`
Clases equivalentes a las legacy ("…Requiere tu Autorización" y "…Rechazada por Nivel 2"), pero **no se instancian en ningún lugar** del código: el flujo de inversión es de un solo nivel.

## Pagos de Inversión y lotes

Ver [[Flujo de pago de inversion]], [[Lotes de pago]], [[Aprobacion del CEO]] y [[Revision del PM]].

### `InvestmentPaymentRequestCreated`
- **Definida para**: `InvestmentPaymentApprovalService::createApproval()` (autorizador global, token 48 h, asunto "Nueva Solicitud de Pago de Inversión #{folio}").
- **Estado**: `createApproval()` no se llama desde ningún controller, comando ni Filament; es remanente del flujo anterior a los lotes.

### `InvestmentBatchSubmittedNotification`
- **Disparador**: `InvestmentPaymentBatchController::submit()` al enviar el lote (el lote recibe `ceo_approval_token` con vigencia de 96 h).
- **Destinatario**: todos los usuarios con rol `ceo`.
- **Asunto**: "Lote pendiente — {proyecto} · {departamento} ({n} pagos)".
- **Contenido**: Solicitante, Departamento, Proyecto, Semana / Año, cantidad de pagos, total del lote; tabla de pagos (folio, proveedor, concepto, descripción, `strategy`, total, moneda); botón "Revisar y Autorizar Pagos" → `/investment-batch-approval/{token}`; pie "Este enlace es válido por 96 horas."

> [!question] Por confirmar
> "Total del lote" suma `total` de todos los pagos y lo etiqueta "MXN" sin aplicar tipo de cambio; con pagos en otra moneda el total mezcla divisas.

### `InvestmentBatchReadyForReviewNotification`
- **Disparador**: `InvestmentBatchApprovalController::review()` cuando el CEO aprueba al menos un pago.
- **Destinatario**: todos los PM.
- **Asunto**: "{n} pagos nuevos en {proyecto}" (+ "(+ {m} pendientes anteriores del mismo proyecto)" si aplica).
- **Contenido**: pagos del **mismo proyecto** en `ceo_approved` / `projectmanager_review`, separados en "Pagos Nuevos" (de este lote) y "Pagos Pendientes Anteriores", agrupados por departamento, con subtotales y gran total (sin conversión de moneda); botón "Revisar pagos" → `/investment-payment-review`.

### `InvestmentBatchRequesterSummaryNotification`
- **Disparadores**:
  - `InvestmentBatchApprovalController::review()` con `stage = 'ceo'`.
  - `InvestmentPaymentReviewController::submit()` con `stage = 'final'`.
- **Destinatario**: cada solicitante (un correo por usuario con sus pagos aprobados y rechazados).
- **Asunto** (según etapa y resultado): "Tus pagos de inversión en {proyecto} fueron aprobados ({n})", "…fueron rechazados ({n})", "Resultado de tus pagos en {proyecto}: X aprobados, Y rechazados"; en etapa final: "Aprobación final de tus pagos en {proyecto} ({n})", etc. El texto final pide subir documentos.
- **Contenido**: tablas de aprobados y rechazados (folio, proveedor, concepto, descripción, `strategy`, monto). Si el PM ajustó el monto (`approved_amount` ≠ `total`), muestra el monto aprobado y el original. Motivo de rechazo. Botón "Ver mis pagos" → `/investment-sheets/consolidated`.

### `InvestmentBatchFinalApprovalSummaryToPmNotification`
- **Disparador**: `InvestmentPaymentReviewController::submit()` cuando hay pagos aprobados; un envío por proyecto.
- **Destinatario**: todos los PM (incluido el que aprobó).
- **Asunto**: "Aprobación final — {proyecto} ({n} pagos)".
- **Contenido**: quién aprobó y cuándo, resumen por departamento con totales por moneda (usa `approved_amount` si existe), conteo de rechazados, botón "Revisar en el portal" → consolidado del proyecto.
- **Adjunto**: PDF `pdf.final-approval-summary-pm` (carta horizontal) llamado `aprobacion-final-{slug-proyecto}-{AAAAMMDD-HHmm}.pdf`, generado con `attachData()`. Ver [[PDFs]].

## Programación semanal

### `WeeklyPaymentScheduleCreated`
- **Disparador**: `WeeklyPaymentScheduleController::store()` → `WeeklyPaymentScheduleApprovalService::createApproval()`.
- **Destinatario**: autorizador global (token 48 h).
- **Asunto**: "Programación de Pagos Semanal - Semana {n}/{año}".
- **Contenido**: creado por, semana, año, pagos incluidos/excluidos, monto total a procesar (suma sin conversión); tablas "Pagos a Procesar" y "Pagos Excluidos" (con motivo); botón "Autorizar / Rechazar Programación" → `/approval/{token}`. Ver [[Programacion semanal]].
- **Database**: "Programación de Pagos Semanal" con acción a `/weekly-payment-schedule`.

## Política de ventanas

Disparadas por comandos programados. Ver [[Politica de ventanas de pago]] y [[Cancelacion automatica de borradores]].

### `DraftPaymentsClosingSoonReminderNotification`
- **Disparador**: `payment-drafts:notify-closing-soon` (cada 5 min; actúa 110–130 min antes del cierre de envío; idempotente por caché).
- **Destinatario**: cada usuario con pagos en `draft` (todos los proyectos).
- **Asunto**: "Tienes {n} pago(s) sin enviar — cierra en 2 horas".
- **Contenido**: hora de cierre, tabla de drafts (folio, proveedor, monto), "TOTAL EN RIESGO" por moneda, botón "Enviar ahora".

### `DraftPaymentsAutoCancelledNotification`
- **Disparador**: `payment-drafts:cancel-expired`.
- **Destinatario**: cada solicitante con drafts cancelados.
- **Asunto**: "Tus {n} pagos no enviados fueron cancelados" (singular si es uno).
- **Contenido**: fecha de cierre, tabla (folio, concepto, proveedor, monto), "TOTAL LIBERADO" por moneda, aviso de que hay que recapturar.

### `DraftPaymentsAutoCancelledPmSummaryNotification`
- **Disparador**: `payment-drafts:cancel-expired`.
- **Destinatario**: todos los PM.
- **Asunto**: "Resumen de cancelaciones automáticas — {día}".
- **Contenido**: resumen por departamento (número y total liberado por moneda), total general y detalle por departamento (folio, solicitante, proveedor, monto).

> [!info] Payload database de estas tres
> Guardan un arreglo propio (`type`, conteos, fechas) sin `title`/`body`; en la campana se ven vacías. Ver [[Notificaciones in-app]].

## Autenticación

### `ResetPasswordMail`
- **Disparador**: `PasswordResetServiceProvider` reemplaza el correo de `Illuminate\Auth\Notifications\ResetPassword` (`toMailUsing`). Aplica al flujo de "olvidé mi contraseña" de Fortify y de Filament.
- **URL**: `Filament::getResetPasswordUrl($token, $user)` (restablecimiento en el panel `/admin`).
- **Asunto**: "Recuperación de Contraseña - {APP_NAME}".
- **Vista**: `emails.reset-password` (HTML propio), con minutos de vigencia de `auth.passwords.users.expire` (default 60).
- **Cola**: el Mailable no implementa `ShouldQueue` y la notificación base de Laravel tampoco: se envía de forma **síncrona**.

Ver [[Autenticacion y 2FA]].

## Relacionado
- [[Colas y correo]] · [[Notificaciones in-app]] · [[PDFs]] · [[Rutas]] · [[Estrategias]]
- [[Flujo de pago de inversion]] · [[Flujo de solicitud de inversion]] · [[Flujo legacy de solicitud de pago]] · [[Cancelacion automatica de borradores]]
