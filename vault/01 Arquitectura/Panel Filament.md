---
tipo: arquitectura
aliases:
  - Panel Filament
tags:
  - payment-request/arquitectura
fuentes:
  - app/Providers/Filament/AdminPanelProvider.php
  - app/Filament/Resources/
  - app/Filament/Pages/PaymentPolicySettings.php
  - config/filament-shield.php
  - config/filament-logger.php
  - app/Models/User.php
  - app/Providers/PasswordResetServiceProvider.php
actualizado: 2026-10-15
---

# Panel Filament

Panel de administración en `/admin` construido con Filament 3.3. Lo usan perfiles administrativos para mantener catálogos, usuarios, roles, la política de ventanas y para auditar. La operación diaria (captura, aprobaciones, revisión) vive en el portal Inertia (ver [[Frontend Inertia React]]).

## Configuración del panel (`AdminPanelProvider`)

| Ajuste | Valor |
|---|---|
| `id` / `path` | `admin` / `/admin` (panel `default`) |
| Autenticación | `->login()` y `->passwordReset()` propios de Filament; `authMiddleware: Authenticate` |
| Branding | `images/logo_white.svg` (claro), `images/logo_dark.svg` (oscuro), alto 2rem, `images/favicon.svg` |
| Estilos | Fuente Geist (Google Fonts), colores `primary` y `gray` = `Color::Slate`; CSS extra `public/css/filament/admin/custom.css` inyectado con el render hook `panels::head.end` |
| Descubrimiento | `app/Filament/Resources`, `app/Filament/Pages`, `app/Filament/Widgets` (no hay widgets propios) |
| Recurso adicional | `config('filament-logger.activity_resource')` → `Z3d0X\FilamentLogger\Resources\ActivityResource` |
| Widgets | `AccountWidget`, `FilamentInfoWidget` (dashboard por defecto) |
| Plugins | `FilamentShieldPlugin::make()` |
| Grupos declarados | `Administración` y `Auditoría`, ambos colapsables |

### Acceso al panel

`User::canAccessPanel()` devuelve `$this->is_active`: cualquier usuario **activo** puede entrar a `/admin`; lo que ve dentro depende de los permisos Shield (policies). Ver [[Permisos Shield]] y [[Policies]].

## Navegación: grupos, recursos y páginas

| Grupo (`$navigationGroup`) | Orden | Recurso / página | Modelo | Páginas |
|---|---|---|---|---|
| Operaciones | 1 | `PaymentRequestResource` — Solicitudes de Pago | [[PaymentRequest]] | list, create, edit + RelationManager `Approvals`; acciones de tabla `approve`, `reject`, `pdf` |
| Operaciones | 2 | `InvestmentRequestResource` — Solicitudes de Inversión | [[InvestmentRequest]] | list, create, edit + RelationManager `Approvals`; acciones `approve`, `reject`, `pdf` |
| Catálogos | 1 | `CurrencyResource` — Monedas | [[Currency]] | list, create, edit |
| Catálogos | 2 | `ExpenseConceptResource` — Conceptos de Gasto | [[ExpenseConcept]] | list (con acciones `Descargar Template` e `Importar CSV`), create, edit |
| Catálogos | 3 | `PaymentTypeResource` — Tipos de Pago | [[PaymentType]] | list, create, edit |
| Catálogos | 4 | `ProjectResource` — Proyectos | [[Project]] | list, create, edit |
| Catálogos | 5 | `InvestmentExpenseCategoryResource` — Categorías de Gasto de Inversión | [[InvestmentExpenseCategory]] | list, create, edit |
| Catálogos | 6 | `InvestmentExpenseConceptResource` — Conceptos de Inversión | [[InvestmentExpenseConcept]] | list, create, edit |
| Catálogos | 7 | `StrategyResource` — Estrategias | [[Strategy]] | list, create, edit |
| Organización | 1 | `SocietyResource` — Sociedades | [[Society]] | list, create, edit |
| Organización | 2 | `BranchResource` — Sucursales | [[Branch]] | list, create, edit |
| Organización | 3 | `DepartmentResource` — Departamentos | [[Department]] | list, create, edit |
| Organización | 4 | `PositionResource` — Posiciones | [[Position]] | list, create, edit |
| Accesos | 1 | `UserResource` — Usuarios | [[User]] | list, create, edit (roles, departamentos, proyectos, activo) |
| (Shield) | — | `RoleResource` — Roles | [[Role]] | list, create, view, edit |
| Auditoría | 1 | `InvestmentPaymentRequestResource` — Pagos de Inversión | [[InvestmentPaymentRequest]] | list, view (**solo lectura**) |
| Auditoría | 2 | `InvestmentPaymentBatchResource` — Lotes de Pagos | [[InvestmentPaymentBatch]] | list, view (**solo lectura**) |
| Auditoría | — | `ActivityResource` (filament-logger) | `Spatie\Activitylog\Models\Activity` | list, view |
| Configuración | — | Página `PaymentPolicySettings` — Política de Ventanas | [[PaymentRequestPolicy (modelo)]] | página singleton (`Filament\Pages\Page` + `InteractsWithForms`) |

Detalle funcional de catálogos en [[Catalogos]]; de la política en [[Politica de ventanas de pago]].

> [!note] Recursos de solo lectura
> `InvestmentPaymentRequestResource` e `InvestmentPaymentBatchResource` sobrescriben `canCreate()`, `canEdit()`, `canDelete()` y `canDeleteAny()` para devolver `false`: el panel solo audita el flujo de pagos de inversión; las acciones se hacen en el portal.

> [!note] Página singleton
> `PaymentPolicySettings` es una `Page` (no un `Resource`) porque solo existe un registro de política. `canAccess()` = rol `super_admin` o `project_manager`.

### Consultas y borrado

- Casi todos los recursos de catálogo sobrescriben `getEloquentQuery()` para quitar `SoftDeletingScope`: se listan también los registros eliminados (con filtro) y tienen acciones `Delete`/`Restore` (+ bulk). Los modelos usan `SoftDeletes`, así que "eliminar" en el panel es borrado lógico. Ver [[Proteccion de la base de datos]].
- `PaymentRequestResource` e `InvestmentRequestResource` además restringen la consulta: `super_admin` ve todo; quien autoriza departamentos (`authorizedDepartments()`) ve las solicitudes de esos departamentos más aquellas en las que tiene una aprobación asignada; cualquier otro usuario ve solo las propias (`user_id`). En el panel no se aplica el filtro por proyecto del portal (ver [[Acceso por proyecto]]).
- En esos dos recursos, los campos de folios SAP (`number_purchase_invoices`, `number_vendor_payments`) solo se habilitan para `super_admin` o para quien aprobó la etapa `administration` / `treasury`. Ver [[SAP Business One]].

## Filament Shield (`config/filament-shield.php`)

| Clave | Valor | Efecto |
|---|---|---|
| `super_admin.enabled` / `define_via_gate` / `intercept_gate` | `true` / `true` / `before` | El rol `super_admin` pasa todas las comprobaciones vía `Gate::before`. |
| `panel_user.enabled` | `true` (`panel_user`) | Rol base de acceso al panel. |
| `permission_prefixes.resource` | `view, view_any, create, update, restore, restore_any, replicate, reorder, delete, delete_any, force_delete, force_delete_any` | Permisos generados por recurso (ej. `view_any_branch`). |
| `entities` | pages, widgets, resources | `custom_permissions = false`. |
| `generator.option` | `policies_and_permissions` | Genera las policies en `app/Policies`. |
| `exclude` | página `Dashboard`, widgets `AccountWidget`, `FilamentInfoWidget` | Sin permiso propio. |
| `shield_resource.navigation_group` | `'Accesos'` | Ver advertencia abajo. |

`RoleResource` es una copia publicada del recurso de Shield; sus prefijos propios son `view, view_any, create, update, delete, delete_any`.

> [!question] Por confirmar: grupo del recurso Roles
> `RoleResource::getNavigationGroup()` usa `Utils::isResourceNavigationGroupEnabled()` (que solo evalúa si `shield_resource.navigation_group` es verdadero) y devuelve la traducción `filament-shield::filament-shield.nav.group`, que en español es "Filament Shield". Es decir, el valor `'Accesos'` del config no se usa como etiqueta: Roles aparecería en un grupo "Filament Shield" separado de Usuarios. Confirmar en pantalla.

> [!question] Por confirmar: grupos declarados
> El panel declara los grupos `Administración` y `Auditoría`, pero ningún recurso usa `Administración`; los grupos reales son Operaciones, Catálogos, Organización, Accesos, Auditoría y Configuración. Los grupos no declarados se ordenan después de los declarados. Revisar si se quiere declarar el orden completo.

## Filament Logger (`config/filament-logger.php`)

| Logger | Log name | Qué registra |
|---|---|---|
| `ResourceLogger` | `Resource` | Acciones en recursos del panel (excluye el propio `ActivityResource`). Grupo de navegación `Auditoría`. |
| `AccessLogger` | `Access` | Logins/logouts del panel. |
| `NotificationLogger` | `Notification` | Notificaciones enviadas. |
| `ModelLogger` | `Model` | Cambios en 22 modelos registrados (catálogos, solicitudes, aprobaciones, lotes, programación, usuarios, roles). |

Formatos de fecha `d/m/Y H:i:s` y `d/m/Y`. Los modelos también usan el trait `LogsActivity` con `logFillable()->logOnlyDirty()`; `User` excluye contraseña, tokens y campos 2FA, y `Branch` excluye `sap_password`. Ver [[Auditoria y activity log]].

## Recuperación de contraseña

`PasswordResetServiceProvider` reemplaza globalmente el correo de `ResetPassword` para que use `Filament::getResetPasswordUrl()` y la plantilla `emails.reset-password`. Ver [[Autenticacion y 2FA]].

## Relacionado
- [[Permisos Shield]] · [[Policies]] · [[Roles y actores]] · [[Catalogos]] · [[Auditoria y activity log]] · [[Politica de ventanas de pago]] · [[Backend Laravel]]
