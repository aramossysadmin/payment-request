---
tipo: modulo
aliases:
  - Dashboards
  - Panel
  - Control Presupuestal
tags:
  - payment-request/dashboards
fuentes:
  - app/Http/Controllers/DashboardController.php
  - resources/js/pages/dashboard.tsx
  - app/Http/Controllers/InvestmentDashboardController.php
  - resources/js/pages/investment-dashboard/index.tsx
  - app/Services/InvestmentDepartmentBreakdownService.php
  - app/Models/InvestmentPaymentRequest.php
  - app/Filament/Resources/RoleResource.php
  - config/fortify.php
actualizado: 2026-11-03
---

# Dashboards

El portal tiene dos tableros en el frontend Inertia: el **Panel** (`/dashboard`), que resume el [[Modulo legacy Payment Requests|módulo legacy de Solicitudes de Pago]], y el **Control Presupuestal** (`/investment-dashboard`), que muestra la ejecución del presupuesto de inversión por proyecto. Además existe un servicio de agregación por departamento (`InvestmentDepartmentBreakdownService`) que usan el consolidado y su PDF.

| Tablero | Ruta | Controller | Página React | Fuente de datos |
|---|---|---|---|---|
| Panel | `GET /dashboard` (`dashboard`) | `DashboardController` (invocable) | `resources/js/pages/dashboard.tsx` | `PaymentRequest` (legacy) |
| Control Presupuestal | `GET /investment-dashboard` (`investment-dashboard`) | `InvestmentDashboardController` (invocable) | `resources/js/pages/investment-dashboard/index.tsx` | `InvestmentRequest` + `InvestmentPaymentRequest` |

Ambas rutas están dentro del grupo `auth` + `verified` de `routes/web.php`. El Panel exige la casilla `view_portal::dashboard` de Shield (tarjeta "Panel"); sin ella, `DashboardController` redirige a Hojas de Inversión. El Control Presupuestal no lleva permiso Shield. Ver [[Rutas]] y [[ADR-024 Panel y Solicitudes de Pago controlados desde Shield]].

## Panel (`/dashboard`)

Muestra solo información de **Solicitudes de Pago legacy** (`PaymentRequest`). Todas las consultas pasan por el scope `PaymentRequest::scopeVisibleTo()` (super_admin ve todo; el resto ve lo propio, lo de sus departamentos —pertenencia o autorización— y aquello donde tiene una aprobación).

Props que envía `DashboardController::__invoke()`:

| Prop | Tipo | Contenido |
|---|---|---|
| `isAuthorizer` | inmediata | `true` si el usuario es autorizador nivel 1 o 2 de algún departamento (`User::authorizedDepartments()`). |
| `isSuperAdmin` | inmediata | Rol `super_admin`. No se usa en la página actualmente. |
| `stats` | `Inertia::defer` | `pendingCount` (estados `pending_department`, `pending_administration`, `pending_treasury`), `pendingByStage` (conteo por etapa), `pendingApprovalCount` (aprobaciones `pending` del usuario, solo si es autorizador), `monthlyTotal` (suma de `total` de solicitudes creadas en el mes en curso). |
| `recentRequests` | `Inertia::defer` | Últimas 10 solicitudes visibles, serializadas con `PaymentRequestResource`. |
| `pendingApprovals` | `Inertia::defer` | Últimas 5 solicitudes con aprobación `pending` del usuario (vacío si no es autorizador). |
| `chartData` | `Inertia::defer` (grupo `chart`) | Conteo de solicitudes creadas en los últimos 6 meses, con etiquetas `Ene`…`Dic`. |

En la página (`dashboard.tsx`) cada bloque está envuelto en `<Deferred>` con skeleton de carga:

- Tarjetas `StatCard`: "Solicitudes Pendientes" (con desglose Depto/Admin/Teso), "Solicitudes por Aprobar" (solo autorizadores) y "Monto Total del Mes".
- Botón "+ Nueva Solicitud de Pago" (enlace a `payment-requests.create`).
- Gráfica `MonthlyChart` "Solicitudes por Mes" y lista "Pendientes de Aprobación".
- Tabla "Solicitudes Recientes" (folio, razón social, monto, fecha, estado).

> [!warning] Montos sin conversión de moneda
> `monthlyTotal` suma `total` tal cual (sin tipo de cambio) y el frontend lo formatea siempre como MXN (`Intl.NumberFormat('es-MX', { currency: 'MXN' })`). Si hay solicitudes legacy en otra moneda, el monto mostrado mezcla divisas. Ver [[Multimoneda]].

> [!info] El Panel no refleja el flujo de inversión
> El Panel no muestra Solicitudes de Inversión ni Pagos de Inversión; para eso está el Control Presupuestal y el [[Consolidado por proyecto]]. Además, el home de Fortify es `/investment-sheets/consolidated` (`config/fortify.php`), no `/dashboard`.

> [!info] Casillas en el Panel
> En la página, "+ Nueva Solicitud de Pago" requiere `auth.can.createPaymentRequests` y los folios son enlace solo con `auth.can.viewPaymentRequestDetail`. Un rol con Panel y sin Solicitudes de Pago sigue viendo sus cifras.

## Control Presupuestal (`/investment-dashboard`)

Tablero de ejecución presupuestal de un proyecto. Título de la página: "Control Presupuestal de Inversión".

### Selección de proyecto (filtro doble)

`InvestmentDashboardController::__invoke()` arma la lista de proyectos con:

1. `Project::accessibleBy($user)`: solo proyectos asignados al usuario en la pivote `project_user`; super_admin, ceo y project_manager ven todos. Ver [[Acceso por proyecto]].
2. Si el usuario **no** es `super_admin` ni `project_manager`, se filtra `is_active = true` (los proyectos cerrados se ocultan). PM y super_admin sí ven proyectos cerrados, y la página muestra el `ProjectClosedBanner`. Ver [[Proyectos]].

Filtros por query string: `project_id` (por defecto el primer proyecto de la lista, ordenada por nombre) y `department_id` (`all` = todos). Al cambiar de proyecto el frontend reinicia el departamento a `all` (`router.get('/investment-dashboard', …, { preserveState: true })`).

> [!question] Por confirmar
> El `project_id` recibido por query string no se valida contra la lista de proyectos accesibles. Los montos sí se filtran con `InvestmentRequest::scopeVisibleTo()` (que ya incluye el filtro por proyecto autorizado), así que un proyecto no autorizado debería devolver ceros; conviene confirmar que este es el comportamiento deseado o agregar un `abort_unless` explícito.

### Cálculos

Base: `InvestmentRequest` del proyecto en estado `Completed`, filtradas por `visibleTo($user)` y, opcionalmente, por departamento.

| Indicador | Cálculo (`InvestmentDashboardController`) |
|---|---|
| Presupuesto | Suma de `total` de las solicitudes completadas. |
| Comprometido | Suma de `total` de pagos cuyo `status` está en `InvestmentPaymentRequest::COMMITTED_STATUSES` (`draft`, `submitted`, `ceo_approved`, `projectmanager_review`, `projectmanager_approved`, `final_approved`, `documents_pending`). |
| Pagado | Suma de `total` de pagos en `InvestmentPaymentRequest::PAID_STATUSES` (`pending_approval`, `approved`, `completed`, `scheduled_for_bank`, `receipt_attached`). |
| Saldo Disponible | Presupuesto − Comprometido − Pagado. |
| % consumido | (Comprometido + Pagado) / Presupuesto × 100, redondeado a 1 decimal. |
| Presupuesto inicial vs aditivas | Suma de `total` con `is_addendum = false` vs `true`; `growthPercent` = aditivas / inicial × 100. |

Secciones de la página:

- **Presupuesto Total** con barra Inicial (azul) vs Aditivas (ámbar) y etiqueta "+X% por aditivas".
- **Por departamento** (`byDepartment`): pagado + comprometido / presupuesto con barra de progreso.
- **Resumen de Ejecución**: cuatro KPIs, `CircularProgress` y barra general.
- **Resumen por Concepto** (`conceptTable`): una fila por par concepto de inversión + departamento con presupuesto base, aditivas (monto y número), total, comprometido, pagado, saldo y %.

Colores de las barras (`ProgressBar`/`CircularProgress`): verde < 50 %, azul ≥ 50 %, ámbar ≥ 75 %, rojo ≥ 100 %.

> [!warning] Sin normalización a MXN
> El docblock de `sumPayments()` lo dice explícitamente: suma `total` "sin normalizar a MXN; el dashboard es de proyectos de moneda nacional". Las solicitudes y pagos en otra moneda se suman en su moneda nativa y se muestran como MXN. En cambio, el [[Consolidado por proyecto]] y `InvestmentDepartmentBreakdownService` sí normalizan con `exchange_rate`. Ver [[Multimoneda]].

> [!question] Por confirmar
> `getConceptTable()` usa SQL crudo `CASE WHEN investment_requests.is_addendum = 0` / `= 1`. Funciona en SQLite; conviene confirmar que se comporta igual en el motor de producción (en PostgreSQL comparar un `boolean` con un entero no es válido sin conversión). Ver [[Produccion]].

## `InvestmentDepartmentBreakdownService`

Servicio estático `InvestmentDepartmentBreakdownService::for(Project $project, User $user)` que devuelve una colección por departamento con `id`, `name`, `total`, `committed`, `paid`, `pending` (= máx(0, total − comprometido − pagado)), `percent_consumed` y `count`.

- Todos los montos se **normalizan a MXN** multiplicando por `currencies.exchange_rate` de la moneda de cada registro.
- Incluye todas las solicitudes del proyecto visibles para el usuario (`visibleTo`), **sin filtrar por estado** (a diferencia del Control Presupuestal, que solo toma `Completed`).
- `count` cuenta conceptos de inversión distintos (las solicitudes sin concepto cuentan individualmente).
- Lo consumen `InvestmentSheetConsolidatedController` (tarjeta "Inversión por Departamento") e `InvestmentSheetInvestmentRequestsPdfController` (cabecera del PDF). Ver [[PDFs]].

> [!info] Conversión del ID a texto
> El `selectRaw` usa `CAST(investment_requests.investment_expense_concept_id AS VARCHAR)` y `CONCAT(...)`. No usar `AS CHAR`: en PostgreSQL produce `char(1)` (un solo carácter) y subcontaba `count`, algo que SQLite no reproduce. Corregido en noviembre de 2026.

## Relacionado
- [[Consolidado por proyecto]] · [[Solicitudes de inversion]] · [[Solicitudes de pago de inversion]] · [[Estados]]
- [[Proyectos]] · [[Acceso por proyecto]] · [[Multimoneda]] · [[Modulo legacy Payment Requests]]
- [[InvestmentRequest]] · [[InvestmentPaymentRequest]] · [[PaymentRequest]] · [[Frontend Inertia React]]
