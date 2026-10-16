---
tipo: permisos
aliases:
  - Policies
  - Políticas de autorización
tags:
  - payment-request/permisos
fuentes:
  - app/Policies/
  - config/filament-shield.php
actualizado: 2026-10-15
---

# Policies

Las 18 policies de `app/Policies` y qué decide cada una. Casi todas son las generadas por Shield (`$user->can('{prefijo}_{recurso}')`); solo tres tienen lógica propia.

> [!info] Antes de cualquier policy
> `Gate::before` de Shield devuelve `true` para `super_admin`, así que ninguna policy se evalúa para ese rol.

## Registro

- Laravel las descubre por convención (`App\Models\X` → `App\Policies\XPolicy`). No hay `AuthServiceProvider` ni `Gate::policy()` propios.
- Shield registra explícitamente `RolePolicy` para `App\Models\Role`.
- `ActivityPolicy` **no** queda registrada (el modelo es `Spatie\Activitylog\Models\Activity`); ver [[Auditoria y activity log]].
- No tienen policy: [[InvestmentRequestApproval]], [[InvestmentPaymentApproval]], [[PaymentRequestApproval]], [[WeeklyPaymentSchedule]], [[WeeklyPaymentScheduleApproval]], [[WeeklyPaymentScheduleItem]], [[PaymentCaptureAttemptLog]], [[PaymentRequestPolicy (modelo)]].

## Policies estándar de Shield

Cada método devuelve `$user->can('{prefijo}_{identificador}')` para los 12 prefijos (`viewAny`, `view`, `create`, `update`, `delete`, `deleteAny`, `forceDelete`, `forceDeleteAny`, `restore`, `restoreAny`, `replicate`, `reorder`).

| Policy | Modelo | Identificador |
|---|---|---|
| `BranchPolicy` | [[Branch]] | `branch` |
| `CurrencyPolicy` | [[Currency]] | `currency` |
| `DepartmentPolicy` | [[Department]] | `department` |
| `ExpenseConceptPolicy` | [[ExpenseConcept]] | `expense::concept` |
| `InvestmentExpenseCategoryPolicy` | [[InvestmentExpenseCategory]] | `investment::expense::category` |
| `InvestmentExpenseConceptPolicy` | [[InvestmentExpenseConcept]] | `investment::expense::concept` |
| `InvestmentPaymentBatchPolicy` | [[InvestmentPaymentBatch]] | `investment::payment::batch` |
| `InvestmentPaymentRequestPolicy` | [[InvestmentPaymentRequest]] | `investment::payment::request` |
| `PaymentTypePolicy` | [[PaymentType]] | `payment::type` |
| `PositionPolicy` | [[Position]] | `position` |
| `ProjectPolicy` | [[Project]] | `project` |
| `RolePolicy` | [[Role]] | `role` |
| `SocietyPolicy` | [[Society]] | `society` |
| `UserPolicy` | [[User]] | `user` |
| `ActivityPolicy` | `Spatie\Activitylog\Models\Activity` | `activity` (no registrada) |

## Policies con lógica propia

### `InvestmentRequestPolicy` ([[InvestmentRequest]])

- `viewAny`, `create`, `deleteAny`, `forceDelete*`, `restore*`, `replicate`, `reorder`: permiso Shield `*_investment::request`.
- `view`, `update`, `delete`: permiso Shield **y** `hasAccess()`:
  1. `super_admin`, o
  2. es el solicitante (`user_id`), o
  3. es autorizador nivel 1/2 del departamento de la solicitud, o
  4. tiene una aprobación asignada en la solicitud.
- Se invoca en `InvestmentRequestController` (`show`, `edit`, `update`, `updateDescription`, `destroy`) y en `InvestmentRequestPdfController`.

> [!question] Por confirmar
> `hasAccess()` no considera la pertenencia por pivote `department_user` ni el acceso por proyecto, a diferencia de `InvestmentRequest::scopeVisibleTo()`. Un miembro del departamento (no autorizador) puede ver la solicitud en el listado pero recibir 403 al abrirla. Confirmar si es intencional.

### `PaymentRequestPolicy` ([[PaymentRequest]])

Misma estructura que la anterior con permisos `*_payment::request` y el mismo `hasAccess()`. Se usa en `PaymentRequestController`, `PaymentRequestPdfController` y `PaymentRequestDocumentController`.

> [!warning] No confundir
> `App\Policies\PaymentRequestPolicy` (autorización) ≠ `App\Models\PaymentRequestPolicy` (configuración de ventanas, ver [[PaymentRequestPolicy (modelo)]]).

### `StrategyPolicy` ([[Strategy]])

Permisos Shield `*_strategy`, excepto `forceDelete` y `forceDeleteAny`, que devuelven siempre `false`: las estrategias solo se envían a la papelera. El modelo además lanza `LogicException` en `forceDeleting`.

## Autorización fuera de policies

Buena parte de la autorización del portal Inertia no pasa por policies:

| Mecanismo | Dónde |
|---|---|
| Scopes `visibleTo()` / `accessibleBy()` | [[InvestmentRequest]], [[PaymentRequest]], [[Project]] |
| `abort_unless` de dueño, departamento o estado | `InvestmentPaymentBatchController`, `InvestmentPaymentDocumentController`, `WeeklyPaymentReceiptController` |
| Bloqueo por proyecto cerrado | Controladores de captura, revisión, documentos, programación y comprobantes |
| `hasRole` / `hasAnyRole` | Dashboards, consolidado, reportes, corrección de fecha de provisión |
| Token de un solo uso con expiración | Aprobaciones por correo |
| `canCreate`/`canEdit`/`canDelete` = `false` | Recursos Filament de solo lectura |

## Relacionado
- [[Permisos Shield]] · [[Roles y actores]] · [[Middleware y rutas protegidas]] · [[Acceso por proyecto]]
