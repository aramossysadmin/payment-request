---
tipo: modulo
aliases:
  - Consolidado por proyecto
  - Hoja de inversión consolidada
tags:
  - payment-request/modulos
  - payment-request/inversion
fuentes:
  - app/Http/Controllers/InvestmentSheetConsolidatedIndexController.php
  - app/Http/Controllers/InvestmentSheetConsolidatedController.php
  - app/Http/Controllers/InvestmentPaymentRequestController.php
  - app/Http/Controllers/InvestmentPaymentHistoryPdfController.php
  - app/Http/Controllers/InvestmentPaymentHistoryExcelController.php
  - app/Http/Controllers/InvestmentSheetInvestmentRequestsPdfController.php
  - app/Http/Controllers/InvestmentSheetInvestmentRequestsExcelController.php
  - app/Services/InvestmentDepartmentBreakdownService.php
  - resources/js/pages/investment-sheets/consolidated-index.tsx
  - resources/js/pages/investment-sheets/consolidated.tsx
actualizado: 2026-10-15
---

# Consolidado por proyecto

Pantalla principal de trabajo del módulo de inversión: muestra por proyecto el presupuesto, las solicitudes de inversión agrupadas por concepto, y desde ahí se capturan, envían y completan los pagos de inversión.

## Rutas

| Ruta | Controlador | Página |
|---|---|---|
| `GET investment-sheets/consolidated` | `InvestmentSheetConsolidatedIndexController` | `investment-sheets/consolidated-index.tsx` |
| `GET investment-sheets/consolidated/{project}` | `InvestmentSheetConsolidatedController` | `investment-sheets/consolidated.tsx` |
| `GET investment-payment-requests/{investmentRequestId}` (JSON) | `InvestmentPaymentRequestController::index` | panel de pagos por concepto |

## Índice de proyectos

`InvestmentSheetConsolidatedIndexController`:

- Lista proyectos con `Project::accessibleBy($user)` (bypass para `super_admin`, `ceo`, `project_manager`).
- Los proyectos cerrados se ocultan a usuarios normales; `super_admin` y `project_manager` los siguen viendo en modo solo lectura.
- Por proyecto calcula `sheets_count` (conceptos únicos = `investment_expense_concept_id` + `department_id`) y `total` (suma de `investment_requests.total`).

> [!question] Por confirmar
> El `total` del índice suma `investment_requests.total` sin multiplicar por `exchange_rate`, a diferencia del consolidado (que normaliza a MXN). Con solicitudes en otra moneda las cifras no coinciden.

## Acceso y filtros del consolidado

- 403 si `! $user->canAccessProject($project)`.
- Filtro de departamento: usuario mono-departamento → su departamento por defecto; multi-departamento → "Todos".
- Búsqueda (proveedor, folio de factura, folio, nombre de concepto), filtro por estado y paginación de 50.
- `super_admin`, `ceo` y `project_manager` (`canSeeAllDepartments`) ven todos los departamentos y pueden editar concepto/descripción de solicitudes (`canEditRequestConcept`).

## Tarjetas superiores (dashboard del proyecto)

| Tarjeta | Contenido |
|---|---|
| Calendario | Semana ISO actual (`isoWeek`) y fecha de hoy |
| Fechas del Proyecto | Inicio, apertura y duración en días |
| Presupuesto | Original, Aditivas, Deductivas, Aditivas/Deductivas en proceso, Presupuesto Actualizado, Comprometido, Pagado, Disponible; badge "Autorizado" si el proyecto tiene `authorized_budget`; liga a la Constancia de Situación Fiscal de la sociedad (URL firmada 48 h) |
| Inversión por Departamento | `InvestmentDepartmentBreakdownService::for()`: total, comprometido, pagado, pendiente y % por departamento |

Fórmulas del dashboard (todas normalizadas a MXN con `sumTotalMxn()`):

```text
Original          = Σ solicitudes completed, no aditivas, no deductivas
Aditivas          = Σ solicitudes completed con is_addendum
Deductivas        = Σ solicitudes completed con is_deductive
Actualizado       = Original + Aditivas − Deductivas
Comprometido      = Σ pagos del proyecto en COMMITTED_STATUSES
Pagado            = Σ pagos del proyecto en PAID_STATUSES
Disponible        = Actualizado − Comprometido − Pagado
```

"En proceso" = solicitudes aditivas/deductivas en `pending_department` (excluye rechazadas). Detalle de los estados que consumen en [[Presupuesto por concepto]].

## Detalle de Solicitudes de Inversión

Tabla agrupada por concepto + departamento. Para cada solicitud el controlador agrega atributos calculados: `remaining_balance` (del propio request) y `group_budget`, `group_committed`, `group_paid`, `group_remaining` (del grupo proyecto + concepto + departamento con solicitudes `completed`). El botón **Pagos** aparece solo si todas las solicitudes del grupo están `completed`.

## Panel de pagos por concepto

Al pulsar **Pagos** se abre un panel lateral (`PaymentsDrawer`) que pide por `fetch` `GET investment-payment-requests/{id}`. El endpoint devuelve:

- `payments`: todos los pagos del grupo (proyecto + concepto + departamento, solicitudes `completed`), con folio, proveedor, tipo, estrategia (`strategySummary()`), montos, estado y solicitante.
- `summary`: `total_concept`, `committed`, `total_paid`, `remaining` desde `InvestmentRequest::budgetBreakdown()`.

El panel muestra barra de avance (pagado + comprometido) y la leyenda "El saldo descuenta lo comprometido (incluye borradores) y lo pagado". El botón **Solicitar Pago** solo se muestra si el departamento del concepto es uno de los del usuario y el saldo del grupo es > 0; se deshabilita si la ventana de captura está cerrada (ver [[Politica de ventanas de pago]]).

> [!question] Por confirmar
> `InvestmentPaymentRequestController::index` hace `InvestmentRequest::findOrFail()` sin aplicar `visibleTo()` ni `canAccessProject()`. Cualquier usuario autenticado que conozca el id numérico podría consultar el JSON.

## Modal de solicitud de pago

Componente del mismo archivo (`consolidated.tsx`), título "Solicitar Pago de Inversión" o "Editar Pago en Borrador". Secciones: Información del Proveedor, Datos Generales, Información de Pago, Documentos Adjuntos.

- Fecha de programación prellenada con `paymentPolicy.provision.nextDate`.
- Selector de tipo de pago (8 tipos, ver [[Solicitudes de pago de inversion]]).
- Tipo **estrategia**: fuerza IVA 16 % al seleccionarlo y calcula Subtotal/IVA/Total con `computeStrategyAmounts()`, espejo de `Strategy::amountsFor()` (ver [[Estrategias]]).
- Muestra el saldo disponible y bloquea el envío si `total > saldo`.
- Cuenta regresiva al cierre de la ventana de captura; el modal se cierra solo al llegar al cierre (salvo override).
- Envía `POST investment-payment-requests` o, en edición, `POST …/{uuid}` con `_method=PATCH` y `keep_documents[]`.

> [!question] Por confirmar
> La advertencia del modal compara `values.total` (en la moneda del pago) contra el saldo (en MXN) sin convertir. El backend sí convierte a MXN antes de validar, así que para monedas distintas a MXN el aviso visual puede no coincidir con la validación real.

## Pagos en Borrador

Tarjeta visible solo si existe un lote `draft` del **departamento filtrado** (o del principal del usuario) + proyecto + **semana ISO actual** (`isoWeek` / `isoWeekYear`). Lotes draft de semanas anteriores no se muestran aquí.

- Columnas: selección, folio, concepto (con estrategia), descripción, proveedor, fecha de programación, monto, acciones.
- **Editar** (deshabilitado si la ventana de captura está cerrada o el proyecto cerrado) y **Eliminar** (`DELETE investment-payment-batches/payments/{uuid}`).
- **Enviar a Autorización** envía solo los seleccionados (`POST investment-payment-batches/{uuid}/submit`); deshabilitado si la ventana de envío está cerrada. Ver [[Lotes de pago]].
- Para cada borrador se precalcula `concept_effective_remaining` excluyendo el propio pago.

## Pagos Pendientes de Documentos

Pagos `final_approved` del proyecto **del propio usuario** (super_admin ve todos, más los legacy `approved` sin lote). Muestran monto aprobado (y si fue ajustado por el PM), documentos actuales y botón para subir documentos. Tiene filtros, ordenamiento y paginación en cliente. Ver [[Documentos de pago]].

## Historial de Pagos

Todos los pagos del proyecto excepto `draft`. Usuarios normales: solo de sus departamentos; `super_admin`/`ceo`/`project_manager`: todos, con selector de departamento en cliente. Incluye montos, aprobado vs. solicitado, estrategia, semana ISO de la fecha de programación, motivos de rechazo (CEO, PM, final legacy), fechas de revisión, documentos y comprobantes con URL firmada de 48 h. Grupos rápidos: en proceso, completados, rechazados. Desde el detalle se puede subir/reemplazar documentos y, para `super_admin`, corregir la fecha de programación (`PATCH investment-payment-requests/{uuid}/provision-date`, recalcula `payment_week_number`).

## Exportaciones

| Botón | Ruta | Restricción |
|---|---|---|
| Historial PDF | `investment-sheets/consolidated/{project}/payment-history-pdf` | `canAccessProject`; usuarios normales deben enviar `week_number` + `week_year` |
| Historial Excel | `…/payment-history-excel` | Igual que el PDF |
| Solicitudes PDF | `…/investment-requests-pdf` | Rol `super_admin`, `ceo` o `project_manager` |
| Solicitudes Excel | `…/investment-requests-excel` | Mismos roles |

Las cuatro respetan la moneda de visualización seleccionada (`ResolvesDisplayCurrency`). Ver [[Exportaciones Excel]] y [[PDFs]].

> [!question] Por confirmar
> En los controladores de historial PDF/Excel, el grupo rápido `completed` incluye solo `final_approved`, `completed`, `approved`; en la pantalla incluye además `scheduled_for_bank` y `receipt_attached`.

## Proyecto cerrado

Si `project.is_active = false` se muestra el banner de proyecto cerrado y se deshabilitan editar, eliminar, enviar y subir documentos; el backend además responde 403 en cada acción. Ver [[Proyectos]].

## Relacionado
- [[Solicitudes de inversion]] · [[Solicitudes de pago de inversion]] · [[Lotes de pago]] · [[Documentos de pago]]
- [[Presupuesto por concepto]] · [[Flujo de pago de inversion]] · [[Estrategias]]
- [[Multimoneda]] · [[Acceso por proyecto]] · [[Politica de ventanas de pago]] · [[Dashboards]]
