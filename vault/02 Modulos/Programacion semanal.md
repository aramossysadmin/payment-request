---
tipo: modulo
aliases:
  - Programación semanal
  - Programación de Pagos Semanal
tags:
  - payment-request/modulos
  - payment-request/inversion
fuentes:
  - app/Http/Controllers/WeeklyPaymentScheduleController.php
  - app/Http/Controllers/WeeklyPaymentScheduleExportController.php
  - app/Http/Requests/StoreWeeklyPaymentScheduleRequest.php
  - app/Services/WeeklyPaymentScheduleApprovalService.php
  - app/Exports/WeeklyPaymentScheduleExport.php
  - app/Notifications/WeeklyPaymentScheduleCreated.php
  - app/Http/Controllers/EmailApprovalController.php
  - resources/js/pages/weekly-payment-schedule/index.tsx
actualizado: 2026-11-04
---

# Programación semanal

Pantalla "Programación de Pagos Semanal" (`/weekly-payment-schedule`) donde se ven, por proyecto y semana, los pagos listos para banco, se arma la programación semanal para autorización, se exporta a Excel y se adjuntan comprobantes de pago.

> [!warning] "Guardar Programación" deshabilitado a propósito
> El botón **Guardar Programación** está deshabilitado en el frontend (`disabled`, título "Deshabilitado temporalmente") por **decisión operativa** (commit "Weekly Schedule: semanas programadas visibles + comprobante de pago", julio 2026). El backend (`store`) sigue existiendo y funcional. Consecuencia práctica: hoy los pagos pasan de `completed` directamente a `receipt_attached` sin pasar por `scheduled_for_bank`.

## Rutas

| Ruta | Controlador |
|---|---|
| `GET weekly-payment-schedule` | `WeeklyPaymentScheduleController::index` |
| `POST weekly-payment-schedule` | `WeeklyPaymentScheduleController::store` |
| `GET weekly-payment-schedule/export` | `WeeklyPaymentScheduleExportController` |
| `POST weekly-payment-schedule/{uuid}/receipt` | `WeeklyPaymentReceiptController::store` (ver [[Comprobantes de pago]]) |
| `GET /approval/{token}` (+ `/approve`, `/reject`) | `EmailApprovalController` (autorización por correo) |

## Pantalla (`index`)

1. Selector **Hoja de Inversión** con los proyectos visibles (`InvestmentRequest::visibleTo()`). Sin selección o con un proyecto no visible: estado vacío.
2. Pagos del proyecto en `approved` (legacy), `completed` o `receipt_attached`, con `payment_provision_date` no nula. El monto mostrado es `approved_amount ?? total`.
3. Navegador de semanas: filtra en cliente por `payment_week_number`, sin el año. La semana inicial es `Carbon::now()->weekOfYear`. Se cambia con las flechas o con un selector que lista todas las semanas ISO por año, incluida la 53 cuando existe, permite buscar por número y muestra la cantidad de pagos de cada una. El selector pone arriba la semana actual y sigue en orden descendente; las semanas futuras quedan al final, en el grupo "Próximas semanas".
4. Si existe una programación no rechazada para la semana/año seleccionados, se muestra en **solo lectura** con sus pagos incluidos/excluidos y su estado (`pending_approval`, `approved`, `rejected`).
5. Si no existe: tabla "Pagos Autorizados — Semana N" con casillas de inclusión y motivo de exclusión. Los pagos `receipt_attached` se ven pero no son programables.
6. **Historial de Programaciones** del proyecto (últimas 20); clic en una fila navega a esa semana.
7. Columna "Comprobante de pago" en ambas vistas.
8. Tarjeta **Total a procesar**: siempre es lo pendiente de comprobante, calculado en cliente con `approved_amount ?? total`.
   - **Sin programación:** suma los pagos seleccionados con las casillas, sin los `receipt_attached`.
   - **Con programación:** suma los pagos incluidos que no están en `receipt_attached`, y debajo muestra "Programado: $…" con el total histórico de los incluidos.

> [!warning] Suma sin convertir
> La tarjeta y el Historial de Programaciones suman los montos tal cual, sin tipo de cambio ni etiqueta de moneda. En proyectos o semanas con varias monedas, el número no representa un total real. Ver [[Multimoneda]].

## Guardar programación (`store`)

Validación (`StoreWeeklyPaymentScheduleRequest`): `week_number` 1–53, `year` ≥ 2024, `items` (mín. 1) con `id`, `included` y `exclusion_reason` opcional (máx. 500).

1. Si algún pago es de proyecto cerrado, no crea nada.
2. Crea `WeeklyPaymentSchedule` en `pending_approval` con sus `items`.
3. Los pagos **excluidos** se posponen: `payment_week_number` = semana + 1 (si la semana es ≥ 52 pasa a 1).
4. `WeeklyPaymentScheduleApprovalService::createApproval()`.

## Autorización (`WeeklyPaymentScheduleApprovalService`)

- `createApproval()`: busca al usuario con correo `config('investment-requests.authorizer_email')` (variable `INVESTMENT_AUTHORIZER_EMAIL`); si no existe, no hace nada. Crea una `WeeklyPaymentScheduleApproval` con token UUID de **48 horas** y le envía `WeeklyPaymentScheduleCreated` (correo + base de datos) con el botón "Autorizar / Rechazar Programación".
- El enlace abre `/approval/{token}`, que `EmailApprovalController` resuelve buscando el token en cuatro tablas de aprobación y muestra la vista Blade `approval.show-schedule`.
- `approve()`: aprobación `approved`, programación `approved` y los pagos **incluidos** pasan a `scheduled_for_bank`.
- `reject()`: exige comentario (mín. 10 caracteres en `EmailApprovalController`), aprobación y programación `rejected`. Los pagos no cambian de estado.

## Exportación a Excel

Diálogo "Exportar a Excel" con alcance **semana seleccionada** o **todas las semanas**:

- Ruta `GET weekly-payment-schedule/export?project_id=…&week=…&year=…`; 403 si el proyecto no es visible.
- Estados exportados: `approved`, `completed`, `scheduled_for_bank`, `receipt_attached`.
- Con semana: filtra `payment_week_number` y el año ISO de `payment_provision_date`.
- Columnas (`WeeklyPaymentScheduleExport`): Semana, Folio, Proveedor, Concepto, Departamento, Tipo de pago, Estrategia (%), Monto Estrategia, Fecha Provisión, Total (`approved_amount ?? total`), Moneda, Estatus, Documentos (conteo), Comprobante.
- Archivo: `programacion-pagos-{proyecto}-S{semana}-{año}.xlsx` o `…-todas-las-semanas.xlsx`.

Ver [[Exportaciones Excel]].

> [!question] Por confirmar
> - La semana inicial de la pantalla usa `Carbon::now()->weekOfYear` y `year`, mientras que `payment_week_number` se calcula con `isoWeek`. Cerca de fin/inicio de año pueden no coincidir.
> - El navegador de semanas y la posposición de excluidos asumen 52 semanas (no contemplan la semana 53 ISO) y la posposición no cambia el año.
> - `store()` no valida que los pagos pertenezcan al proyecto seleccionado ni que estén en un estado programable.
> - El correo `WeeklyPaymentScheduleCreated` suma `total` (no `approved_amount`).

## Relacionado
- [[Documentos de pago]] · [[Comprobantes de pago]] · [[Flujo de pago de inversion]]
- [[WeeklyPaymentSchedule]] · [[WeeklyPaymentScheduleItem]] · [[WeeklyPaymentScheduleApproval]]
- [[Exportaciones Excel]] · [[Catalogo de correos]] · [[Acceso por proyecto]]
