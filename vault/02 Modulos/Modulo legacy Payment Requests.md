---
tipo: modulo
aliases:
  - Módulo legacy Payment Requests
  - Solicitudes de Pago (legacy)
tags:
  - payment-request/legacy
fuentes:
  - app/Models/PaymentRequest.php
  - app/Models/PaymentRequestApproval.php
  - app/States/PaymentRequest/PaymentRequestState.php
  - app/Http/Controllers/PaymentRequestController.php
  - app/Http/Controllers/PaymentRequestApprovalController.php
  - app/Http/Controllers/PaymentRequestPdfController.php
  - app/Http/Controllers/PaymentRequestDocumentController.php
  - app/Http/Controllers/EmailApprovalController.php
  - app/Http/Requests/StorePaymentRequestRequest.php
  - app/Http/Requests/UpdatePaymentRequestRequest.php
  - app/Http/Requests/RejectPaymentRequestRequest.php
  - app/Services/ApprovalService.php
  - app/Policies/PaymentRequestPolicy.php
  - app/Filament/Resources/PaymentRequestResource.php
  - resources/js/pages/payment-requests
  - resources/js/components/app-sidebar.tsx
  - app/Models/User.php
  - app/Http/Middleware/HandleInertiaRequests.php
actualizado: 2026-11-02
---

# Módulo legacy Payment Requests

Primer módulo del portal (febrero–abril 2026): **Solicitudes de Pago** generales (no ligadas a proyectos de inversión) con un flujo secuencial de autorización Departamento → Administración → Tesorería, aprobación por correo con token y captura de **folios SAP**. Sigue activo en `/payment-requests` y en Filament (`/admin/payment-requests`, grupo "Operaciones"), con acceso controlado por rol desde Shield; el trabajo actual del portal se concentra en el flujo de inversión.

## Modelo `PaymentRequest`

| Campo | Notas |
|---|---|
| `uuid` | Se genera en `creating`; es la llave de ruta (`getRouteKeyName()`). |
| `folio_number` | Consecutivo: `max(folio_number)` incluyendo borrados lógicos + 1 (`booted()`). |
| `provider`, `rfc`, `invoice_folio`, `description` | Se guardan en MAYÚSCULAS. RFC alfanumérico de 12–13 caracteres, opcional. |
| `user_id`, `department_id` | Solicitante y departamento (del formulario si el usuario tiene varios; si no, su departamento principal). |
| `currency_id`, `branch_id`, `expense_concept_id`, `payment_type_id` | Catálogos (ver [[Catalogos]]). Tipos de pago solo de categoría `payment`. |
| `advance_documents` | Arreglo JSON de rutas en disco `local` (`advance-documents/AAAA/MM/{folio}/{uuid}.ext`). |
| `subtotal`, `iva_rate` (`IvaRate`: 0 %, 4 %, 8 %, 10 %, 16 %, 21 %), `iva`, `retention`, `total` | Montos. |
| `number_purchase_invoices` | **Folio SAP Factura Proveedores** (etapa Administración). |
| `number_vendor_payments` | **Folio SAP Pago Efectuado** (etapa Tesorería). |
| `status` | Estado Spatie `PaymentRequestState`. |

Usa `SoftDeletes` y `LogsActivity`. Relaciones: `user`, `department`, `currency`, `branch`, `paymentType`, `expenseConcept`, `approvals` (`PaymentRequestApproval`). Ver [[PaymentRequest]] y [[PaymentRequestApproval]].

### Estados

| Clase | `$name` | Etiqueta |
|---|---|---|
| `PendingDepartment` (default) | `pending_department` | Pendiente Departamento |
| `PendingAdministration` | `pending_administration` | Pendiente Administración |
| `PendingTreasury` | `pending_treasury` | Pendiente Tesorería |
| `Completed` | `completed` | Finalizado |

Transiciones permitidas solo hacia adelante (`PaymentRequestState`). No existe estado de rechazo. Ver [[Estados]].

## Flujo de autorización (`ApprovalService`)

Pipeline fijo en `ApprovalService::PIPELINE`:

| Etapa | Departamento responsable | Siguiente estado |
|---|---|---|
| `department` | El departamento de la solicitud | `pending_administration` |
| `administration` | Departamento con nombre exacto `ADMINISTRACIÓN` | `pending_treasury` |
| `treasury` | Departamento con nombre exacto `TESORERÍA` | `completed` |

```mermaid
flowchart LR
    A["Creada"] --> B["Depto N1"]
    B -->|"si hay N2"| C["Depto N2"]
    B -->|"sin N2"| D["Administracion N1"]
    C --> D
    D --> E["Administracion N2 opcional"]
    E --> F["Tesoreria N1"]
    F --> G["Tesoreria N2 opcional"]
    G --> H["Finalizado"]
```

- **Creación** (`createApprovals`): crea la aprobación `department` nivel 1 para el `authorizerLevel1` del departamento, con token UUID válido 48 h, y notifica `PaymentRequestCreated`.
- **Aprobar** (`approve`): marca la aprobación, guarda folios SAP si vienen en `$data`; si es nivel 1 y el departamento tiene `authorizer_level_2_id`, crea la aprobación nivel 2 y notifica `PaymentRequestApproved`; si no, transiciona al siguiente estado y crea la aprobación nivel 1 de la siguiente etapa (`PaymentRequestApproved`). Al llegar a `completed` notifica al solicitante `PaymentRequestCompleted`.
- **Rechazar nivel 1**: guarda comentarios y notifica al solicitante `PaymentRequestRejected`.
- **Rechazar nivel 2**: crea una nueva aprobación nivel 1 pendiente (token nuevo, 48 h), notifica al autorizador nivel 1 `PaymentRequestLevel2Rejected` y al solicitante `PaymentRequestRejected`.

Los departamentos de Administración y Tesorería se buscan por nombre; si no existen o no tienen autorizador, la solicitud avanza de estado pero no se crea aprobación (queda sin responsable).

> [!question] Por confirmar
> Un rechazo de nivel 1 no cambia el estado ni crea una aprobación nueva: la solicitud queda en su estado "Pendiente …" sin aprobador pendiente y sin forma de reactivarla desde la UI. Confirmar si este es el comportamiento esperado.

Detalle del flujo en [[Flujo legacy de solicitud de pago]].

## Portal Inertia

| Método | URI | Acción | Autorización |
|---|---|---|---|
| GET | `/payment-requests` | `index`: lista paginada (15) con búsqueda (proveedor, folio factura, folio), filtro de estado y grupo `pending` (default) / `completed` / todos | Casilla `view_any_payment::request`, luego `visibleTo($user)`. Sin la casilla redirige a Hojas de Inversión con aviso |
| GET | `/payment-requests/create` | Formulario | Casilla `create_payment::request`. Sin ella redirige a Hojas de Inversión con aviso |
| POST | `/payment-requests` | `store` + `createApprovals` | `StorePaymentRequestRequest::authorize()` exige la casilla `create_payment::request`; sin ella responde 403 antes de validar |
| GET | `/payment-requests/{uuid}` | `show` | `Gate::authorize('view')` |
| GET/PUT/PATCH | `/payment-requests/{uuid}/edit`, `/payment-requests/{uuid}` | Editar; solo en `pending_department`. Si se suben archivos nuevos, se **borran del disco** los anteriores | `Gate::authorize('update')` |
| DELETE | `/payment-requests/{uuid}` | Soft delete | `Gate::authorize('delete')` |
| POST | `/payment-requests/{uuid}/approve` | `PaymentRequestApprovalController::approve` (acepta folios SAP), `throttle:10,1` | Debe tener aprobación `pending` |
| POST | `/payment-requests/{uuid}/reject` | `reject`, comentario mínimo 10 caracteres, `throttle:10,1` | Debe tener aprobación `pending` |
| PATCH | `/payment-requests/{uuid}/sap-folios` | `updateSapFolios` | Solo quien aprobó la etapa `administration` (folio factura) o `treasury` (folio pago) |
| GET | `/payment-requests/{uuid}/pdf` | PDF, solo `completed` | `Gate::authorize('view')` |
| GET | `/payment-requests/{uuid}/documents/{index}` | Descarga/visualiza adjunto | `Gate::authorize('view')` |

`PaymentRequestPolicy` combina el permiso Shield (`view_payment::request`, `update_payment::request`, …) con `hasAccess()`: super_admin, solicitante, autorizador del departamento o usuario con alguna aprobación.

### Aprobación por correo

`/approval/{token}` (`EmailApprovalController`, `throttle:10,1`) busca el token en `PaymentRequestApproval`, `InvestmentRequestApproval`, `InvestmentPaymentApproval` y `WeeklyPaymentScheduleApproval`; valida vigencia (48 h) y estado, y llama al servicio correspondiente. No requiere sesión. Ver [[Rutas]].

### Control desde Shield

La tarjeta "Solicitud de Pago" de `/admin/shield/roles` controla el portal y `/admin` ([[ADR-024 Panel y Solicitudes de Pago controlados desde Shield]]). Para ocultar el recurso a un rol se desmarca toda la tarjeta; "Ver el listado" es la casilla que oculta el menú.

| Casilla | Efecto en el portal | `auth.can` |
|---|---|---|
| Ver el listado (`view_any_payment::request`) | Menú "Solicitudes de Pago" y lista con detalle lateral | `viewPaymentRequests` |
| Crear (`create_payment::request`) | Botones "Nueva", formulario y guardado | `createPaymentRequests` |
| Ver un registro (`view_payment::request`) | Página de detalle, PDF, documentos y enlaces de folio en el Panel | `viewPaymentRequestDetail` |
| Actualizar (`update_payment::request`) | Botón "Editar" y edición | `updatePaymentRequests` |
| Eliminar (`delete_payment::request`) | Borrado lógico (el portal no tiene botón) | — |

Las demás casillas (restaurar, replicar, reordenar y forzar eliminación) solo aplican en `/admin`.

> [!warning] `super_admin`
> La lista y el formulario usan `User::hasPortalPermission()`, que no pasa por el `Gate::before` de Shield: si se desmarca la casilla en su rol, `super_admin` deja de ver el recurso en el portal, pero lo conserva en `/admin`. Detalle, edición, borrado, PDF y documentos siguen en `PaymentRequestPolicy`, por lo que todavía puede abrir un detalle con su URL directa.

> [!info] Permisos de varios roles
> Los permisos se suman: un usuario deja de ver el recurso solo si **ninguno** de sus roles tiene la casilla.

> [!warning] Aprobaciones pendientes
> Las aprobaciones por correo (`/approval/{token}`) y las rutas `approve`/`reject` no dependen de estas casillas. Si el token ya venció, el aprobador solo puede atender la solicitud en pantalla, así que al desmarcar la tarjeta de su rol no tiene cómo hacerlo hasta volver a marcarla.

## Panel Filament (`PaymentRequestResource`)

Grupo "Operaciones". Formulario con los mismos campos (incluye "Folio SAP Factura Proveedores" y "Folio SAP Pago Efectuado"), acciones de tabla **Aprobar** / **Rechazar** (visibles si el usuario tiene una aprobación pendiente) y **PDF** (solo `completed`). La creación desde Filament también llama a `ApprovalService::createApprovals()`.

## Salidas

- Correos: `PaymentRequestCreated`, `PaymentRequestApproved`, `PaymentRequestCompleted`, `PaymentRequestRejected`, `PaymentRequestLevel2Rejected` (canales `database` + `mail`, plantilla `emails.request-notification`). Ver [[Catalogo de correos]].
- PDF `pdf.payment-request` (compartido con Solicitudes de Inversión). Ver [[PDFs]].
- El [[Dashboards|Panel]] (`/dashboard`) solo muestra datos de este módulo.

## Relacionado
- [[Flujo legacy de solicitud de pago]] · [[PaymentRequest]] · [[PaymentRequestApproval]] · [[Estados]]
- [[SAP Business One]] · [[Catalogos]] · [[Permisos Shield]] · [[Policies]] · [[Rutas]] · [[Dashboards]]
