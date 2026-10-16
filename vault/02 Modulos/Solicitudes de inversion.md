---
tipo: modulo
aliases:
  - Solicitudes de inversión
  - Hojas de inversión
  - Conceptos de inversión
tags:
  - payment-request/modulos
  - payment-request/inversion
fuentes:
  - app/Models/InvestmentRequest.php
  - app/Http/Controllers/InvestmentRequestController.php
  - app/Http/Controllers/InvestmentRequestApprovalController.php
  - app/Http/Controllers/InvestmentRequestPdfController.php
  - app/Http/Requests/StoreInvestmentRequestRequest.php
  - app/Http/Requests/UpdateInvestmentRequestRequest.php
  - app/Services/InvestmentApprovalService.php
  - app/Policies/InvestmentRequestPolicy.php
  - app/Filament/Resources/InvestmentRequestResource.php
  - config/investment-requests.php
  - resources/js/pages/investment-sheets/
actualizado: 2026-10-15
---

# Solicitudes de inversión

Una solicitud de inversión (`InvestmentRequest`, ruta `investment-sheets`) es el **presupuesto autorizado** de un concepto de inversión dentro de un proyecto y departamento. Los pagos de inversión se capturan contra ella y consumen su saldo (ver [[Presupuesto por concepto]]).

## Qué es y cómo se identifica

| Aspecto | Detalle | Fuente |
|---|---|---|
| Llave de ruta | `uuid` (se genera en `creating`) | `InvestmentRequest::getRouteKeyName()` |
| Folio | `folio_number` = máximo histórico (incluye borrados) + 1 | `InvestmentRequest::booted()` |
| Estado | Máquina de estados spatie: `pending_department` → `completed` / `rejected` | `app/States/InvestmentRequest/` |
| Borrado | `SoftDeletes` (nunca se borra físicamente) | `app/Models/InvestmentRequest.php` |
| Auditoría | `LogsActivity` sobre campos `fillable`, solo cambios | `InvestmentRequest::getActivitylogOptions()` |
| Normalización | `provider`, `rfc`, `invoice_folio` y `description` se guardan en MAYÚSCULAS y sin espacios extremos | mutators `set*Attribute` |

Relaciones principales: `user`, `department`, `project`, `currency`, `branch`, `investmentExpenseConcept`, `expenseConcept` (legacy), `paymentType`, `approvals` (`InvestmentRequestApproval`) e `investmentPaymentRequests`.

## Rutas y pantallas

| Acción | Ruta | Controlador | Página |
|---|---|---|---|
| Listado | `GET investment-sheets` | `InvestmentRequestController::index` | `investment-sheets/index.tsx` |
| Crear | `GET/POST investment-sheets` | `create` / `store` | `investment-sheets/create.tsx` |
| Ver | `GET investment-sheets/{uuid}` | `show` | `investment-sheets/show.tsx` |
| Editar | `GET/PUT investment-sheets/{uuid}/edit` | `edit` / `update` | `investment-sheets/edit.tsx` |
| Micro-edición | `PATCH investment-sheets/{uuid}/description` | `updateDescription` | modal en el consolidado |
| Aprobar / rechazar | `POST investment-sheets/{uuid}/approve` y `/reject` (throttle 10/min) | `InvestmentRequestApprovalController` | botones en `show.tsx` |
| PDF | `GET investment-sheets/{uuid}/pdf` | `InvestmentRequestPdfController` | descarga |
| Eliminar | `DELETE investment-sheets/{uuid}` | `destroy` (soft delete) | — |

El listado filtra por pestañas `status_group`: `pending` (default, `PendingDepartment`), `completed` y `rejected`, además de búsqueda por proveedor, folio de factura o folio. Pagina de 15 en 15 y usa el scope `InvestmentRequest::scopeVisibleTo()`.

## Captura (crear)

`StoreInvestmentRequestRequest` valida:

- `currency_id`, `branch_id`, `iva_rate` (enum `IvaRate`), `subtotal`, `iva`, `total` son obligatorios; `project_id`, `department_id`, `investment_expense_concept_id`, proveedor, RFC (12–13 alfanuméricos), contacto y descripción son opcionales.
- **Multi-departamento:** si el usuario pertenece a más de un departamento debe elegir uno de los suyos (`department_id`); si es mono-departamento se asigna su departamento principal.
- **Concepto vs. departamento:** el concepto elegido debe pertenecer a una categoría ligada al departamento resuelto (`category.departments`).
- **Adjuntos:** `invoice_documents` = exactamente 1 PDF + 1 XML si se envían; `advance_documents` = hasta 5 archivos PDF/JPG/PNG de 10 MB.

En `InvestmentRequestController::store()`:

1. Si el proyecto está cerrado (`is_active = false`) responde 403 (ver [[Proyectos]]).
2. Asigna `user_id`, `department_id`, `project_id` e `investment_expense_concept_id`.
3. Calcula `is_addendum` (ver abajo).
4. Guarda archivos en disco `local` bajo `investment-advance-documents/AAAA/MM/{folio}`.
5. Llama `InvestmentApprovalService::createApprovals()`.

El formulario solo ofrece proyectos activos y accesibles (`Project::active()->accessibleBy($user)`) y conceptos activos de las categorías de los departamentos del usuario.

## Aditivas y deductivas

| Tipo | Campo | Cómo se marca | Efecto en el consolidado |
|---|---|---|---|
| Inicial | `is_addendum = false`, `is_deductive = false` | Primera solicitud `completed` del par proyecto + concepto | Suma a "Original" |
| Aditiva | `is_addendum = true` | Automático en `store()`: ya existe una solicitud `completed` con el mismo `project_id` + `investment_expense_concept_id` | Suma a "Aditivas"; el correo al autorizador lleva el aviso "ADITIVA AL PRESUPUESTO" (`InvestmentRequestCreated`) |
| Deductiva | `is_deductive = true` | No hay código de la aplicación que lo asigne | Resta en "Presupuesto Actualizado" del consolidado |

> [!info] Las aditivas no son conceptos nuevos
> El conteo de "conceptos" del índice y del consolidado agrupa por `investment_expense_concept_id` + `department_id`; una aditiva extiende el mismo rubro (`InvestmentSheetConsolidatedIndexController`).

> [!question] Por confirmar
> - `is_deductive` (migración `2026_06_04_170012_add_is_deductive_to_investment_requests_table.php`) no está en `$fillable` ni lo asigna ningún controlador o recurso Filament. ¿Se marca manualmente en BD?
> - `InvestmentRequest::budgetBreakdown()` suma **todas** las solicitudes `completed` del grupo sin restar deductivas, mientras que el dashboard del consolidado sí las resta. ¿Debe la deductiva reducir el saldo del concepto?
> - `is_addendum` solo se calcula en el `store()` de Inertia; la creación desde Filament (`CreateInvestmentRequest::handleRecordCreation()`) no lo calcula.

## Aprobación

`InvestmentApprovalService::createApprovals()` decide con esta precedencia:

1. **Proyecto con candado** (`projects.requires_pm_approval = true`): crea UNA aprobación a nombre del primer usuario con rol `project_manager` y notifica a **todos** los PMs. Si no hay PMs, cae al autorizador global.
2. **Bypass global** (`config('investment-requests.require_authorization')` = `false`, variable `INVESTMENT_REQUEST_REQUIRE_AUTHORIZATION`): crea una aprobación ya `approved` con comentario "Aprobación automática…" y transiciona a `completed`.
3. **Flujo por defecto:** una aprobación para el usuario cuyo correo es `config('investment-requests.authorizer_email')` (variable `INVESTMENT_AUTHORIZER_EMAIL`).

Cada aprobación pendiente lleva `approval_token` (UUID) con vigencia de **48 horas**, usado por el enlace del correo `/approval/{token}` (ver [[Flujo legacy de solicitud de pago]] para el controlador compartido `EmailApprovalController`).

Al aprobar (`InvestmentApprovalService::approve()`), solo si el estado es `pending_department` y el usuario tiene aprobación pendiente: marca `approved`, invalida el token, transiciona a `completed` y notifica al solicitante (`InvestmentRequestCompleted`). Al rechazar: marca `rejected` con comentarios, transiciona a `rejected` y notifica (`InvestmentRequestRejected`). Detalle de estados en [[Flujo de solicitud de inversion]].

También se puede aprobar/rechazar desde Filament (`InvestmentRequestResource`, acciones `approve` / `reject`).

## Edición

- **Edición completa** (`edit` / `update`): requiere `Gate update` y estado `pending_department`. Bloquea si el proyecto actual o el destino están cerrados. Si se suben archivos nuevos, el conjunto reemplaza la columna `advance_documents`; los archivos físicos previos se conservan en disco.
- **Micro-edición** (`updateDescription`): solo roles `super_admin`, `ceo`, `project_manager`; permite cambiar `description` e `investment_expense_concept_id` en **cualquier** estado (incluido `completed`).

## Visibilidad y permisos

`InvestmentRequest::scopeVisibleTo()`:

- `super_admin` ve todo.
- El resto ve: las suyas, las de departamentos donde es autorizador (nivel 1/2) o miembro, y las que tienen una aprobación a su nombre.
- Filtro adicional por proyectos asignados al usuario, excepto roles `ceo` y `project_manager`; las solicitudes sin `project_id` no se bloquean.

`InvestmentRequestPolicy` combina permisos Shield (`view_investment::request`, etc.) con `hasAccess()` (super_admin, dueño, autorizador del departamento o aprobador). Ver [[Permisos Shield]] y [[Acceso por proyecto]].

## PDF

`InvestmentRequestPdfController` solo genera el PDF si el estado es `completed`; usa la vista `pdf.payment-request` con título "Concepto de Inversión" y descarga `solicitud-inversion-{folio}.pdf`. Ver [[PDFs]].

## Relacionado
- [[Flujo de solicitud de inversion]] · [[Presupuesto por concepto]] · [[Consolidado por proyecto]] · [[Solicitudes de pago de inversion]]
- [[InvestmentRequest]] · [[InvestmentRequestApproval]] · [[InvestmentExpenseConcept]] · [[Project]]
- [[Catalogo de correos]] · [[Roles y actores]] · [[Multimoneda]]
