---
tipo: salida
aliases:
  - Exportaciones Excel
  - Reportes Excel
tags:
  - payment-request/excel
  - payment-request/salidas
fuentes:
  - app/Exports/InvestmentRequestsConsolidatedExport.php
  - app/Exports/PaymentHistoryExport.php
  - app/Exports/WeeklyPaymentScheduleExport.php
  - app/Http/Controllers/InvestmentSheetInvestmentRequestsExcelController.php
  - app/Http/Controllers/InvestmentPaymentHistoryExcelController.php
  - app/Http/Controllers/WeeklyPaymentScheduleExportController.php
  - app/Http/Controllers/Concerns/ResolvesDisplayCurrency.php
  - resources/js/pages/investment-sheets/consolidated.tsx
  - resources/js/pages/weekly-payment-schedule/index.tsx
  - composer.lock
  - app/Exports/StrategyHistoryExport.php
  - app/Http/Controllers/StrategyHistoryExcelController.php
  - app/Services/StrategyPaymentHistory.php
  - resources/js/pages/strategies/index.tsx
actualizado: 2026-11-04
---

# Exportaciones Excel

Cuatro exportaciones `.xlsx` generadas con **Laravel Excel** (`maatwebsite/excel` 3.1.69 sobre `phpoffice/phpspreadsheet` 1.30.5). Las clases viven en `app/Exports/` y se descargan con `Excel::download()` desde controllers invocables; se generan en el request (sin cola).

Todas implementan `FromCollection`, `WithHeadings`, `WithMapping`, `WithColumnFormatting`, `WithStyles` (fila 1 en negrita) y `ShouldAutoSize` (ancho automático de columnas).

## Resumen

| Export | Controller | Ruta | Hoja | Archivo |
|---|---|---|---|---|
| `InvestmentRequestsConsolidatedExport` | `InvestmentSheetInvestmentRequestsExcelController` | `GET /investment-sheets/consolidated/{project}/investment-requests-excel` | `Solicitudes` | `Solicitudes de Inversion - {proyecto} - {depto o TODOS} - {AAAA-MM-DD}.xlsx` |
| `PaymentHistoryExport` | `InvestmentPaymentHistoryExcelController` | `GET /investment-sheets/consolidated/{project}/payment-history-excel` | `Historial` | `historial-pagos-{slug}-{AAAAMMDD}.xlsx` |
| `WeeklyPaymentScheduleExport` | `WeeklyPaymentScheduleExportController` | `GET /weekly-payment-schedule/export` | (sin título) | `programacion-pagos-{slug}-S{n}-{año}.xlsx` o `…-todas-las-semanas.xlsx` |
| `StrategyHistoryExport` | `StrategyHistoryExcelController` | `GET /strategies/excel` | `Estrategias` | `estrategias-{slug}-{AAAAMMDD}.xlsx` o `estrategias-todas-las-hojas-de-inversion-{AAAAMMDD}.xlsx` |

Las dos primeras se disparan desde la página del [[Consolidado por proyecto]] (`consolidated.tsx`); la tercera desde [[Programacion semanal]] (`weekly-payment-schedule/index.tsx`); la cuarta desde la consulta de [[Estrategias]] (`strategies/index.tsx`).

## `InvestmentRequestsConsolidatedExport`

Equivalente tabular del PDF de Solicitudes de Inversión. Ver [[PDFs]].

- **Acceso**: middleware `role:super_admin|ceo|project_manager`, `abort_unless` del mismo rol y `canAccessProject($project)`.
- **Filtros**: `department_id` y `display_currency_id`. Base: `InvestmentRequest` del proyecto con `visibleTo($user)`, ordenadas por departamento y folio.
- **Grupo**: concepto de inversión + departamento (las solicitudes sin concepto forman su propio grupo); presupuesto y saldo se calculan por grupo.

| Col | Encabezado | Contenido |
|---|---|---|
| A | Departamento | Nombre del departamento |
| B | Categoría | Categoría del concepto de inversión |
| C | Concepto | Concepto de inversión |
| D | Folio | `#00000` |
| E | Tipo | Inicial / Aditiva |
| F | Estatus | Etiqueta del estado |
| G | Descripción | |
| H | Total ({moneda}) | Monto convertido a la moneda de despliegue |
| I | Presupuesto de Grupo ({moneda}) | |
| J | Saldo de Grupo ({moneda}) | |
| K | Moneda | Prefijo de despliegue |

Formato numérico `#,##0.00_-` (`FORMAT_NUMBER_COMMA_SEPARATED2`) en H, I y J.

## `PaymentHistoryExport`

Equivalente del PDF de historial de pagos, con más columnas.

- **Acceso**: `canAccessProject($project)`. Usuarios que no son super_admin/ceo/PM solo pueden pedir sus departamentos (403 si no) y deben enviar `week_number` + `week_year` (si faltan, `$request->validate()` lanza error de validación).
- **Filtros**: los mismos del PDF (`department_id`, semana, `status`, `quick_filter`, `search`, `display_currency_id`). Excluye `draft`.

| Col | Encabezado | Contenido |
|---|---|---|
| A | Folio | `#00000` |
| B | Estatus | Etiqueta en español |
| C | F. Programación Pago | `Y-m-d` |
| D | Semana | Semana ISO de la fecha de programación |
| E | Concepto | |
| F | Descripción | |
| G | Categoría | |
| H | Departamento | |
| I | Proveedor | |
| J | RFC | |
| K | Tipo de Pago | `InvestmentPaymentType::labelFor()` |
| L | Estrategia (%) | Tasa de la estrategia o vacío |
| M | Monto Estrategia (moneda nativa) | `strategy_amount` o vacío |
| N | Monto Solicitado ({moneda}) | Convertido a moneda de despliegue |
| O | Monto Aprobado ({moneda}) | `approved_amount` convertido, o vacío |
| P | Moneda Nativa | Prefijo de la moneda del pago |
| Q | Solicitante | |
| R | F. Solicitud | `Y-m-d` |
| S | Moneda Display | Prefijo de despliegue |

Formato `#,##0.00_-` en M, N y O. La columna L no tiene formato explícito.

## `WeeklyPaymentScheduleExport`

- **Acceso**: valida `project_id` (obligatorio), `week` (1–53, opcional) y `year` (2020–2100, obligatorio si viene `week`). Responde 403 "No tienes acceso a este proyecto." si el usuario no tiene ninguna Solicitud de Inversión visible (`visibleTo`) en ese proyecto.
- **Universo**: pagos del proyecto con `payment_provision_date` y estado `approved`, `completed`, `scheduled_for_bank` o `receipt_attached`, filtrados por `payment_week_number` y por año ISO de la fecha de programación. No filtra por departamento.
- Ordenados por semana y folio.

| Col | Encabezado | Contenido |
|---|---|---|
| A | Semana | `S{semana}/{año ISO}` |
| B | Folio | `#00000` |
| C | Proveedor | |
| D | Concepto | Concepto de inversión |
| E | Departamento | |
| F | Tipo de pago | Etiqueta (Factura, Anticipo, Reembolso, Estrategia, Cotización, Pagaré, Domiciliado, Factura España, Nómina Fiscal) |
| G | Estrategia (%) | Tasa si es estrategia |
| H | Monto Estrategia | `strategy_amount` si es estrategia |
| I | Fecha Provisión | `d/m/Y` |
| J | Total | `approved_amount` si existe, si no `total` (moneda nativa, sin conversión) |
| K | Moneda | Prefijo |
| L | Estatus | Aprobado / Completado / Programado en banco / Comprobante adjunto |
| M | Documentos | Número de documentos adjuntos |
| N | Comprobante | `Sí (n)` o `—` |

Formato `#,##0.00` (`FORMAT_NUMBER_COMMA_SEPARATED1`) en H y J.

## `StrategyHistoryExport`

- **Acceso**: casilla `view_portal::strategies` (403 sin ella).
- **Filtros**: `project_id` (ID o `all`; 404 si es inválido), `department_id`, `status`, `capture_week` y `provision_week` (formato `AAAA-S`). Exporta todas las filas filtradas, no solo la página visible. Ver [[Estrategias]].

| Col | Encabezado | Contenido |
|---|---|---|
| A | Folio | `#00000` |
| B | Hoja de Inversión | Nombre del proyecto |
| C | Departamento | |
| D | Solicitante | |
| E | Concepto | |
| F | Proveedor | |
| G | Código | Nombre del snapshot, o "Anterior al catálogo" |
| H | Tasa (%) | Tasa de la estrategia o vacío |
| I | Monto Base | `strategy_amount` o vacío |
| J | Recargo | Total − `strategy_amount`, o vacío |
| K | Total | `total` en moneda original |
| L | Aprobado | `approved_amount` o vacío |
| M | Moneda | Prefijo |
| N | Tipo | "Estrategia" o "Tipo actual: …" |
| O | Estado | Etiqueta en español |
| P | Fecha Captura | `Y-m-d` |
| Q | Semana Captura | Semana ISO de la captura |
| R | Fecha Programación | `Y-m-d` |
| S | Semana Programación | Semana ISO de la programación |

Formato `#,##0.00_-` (`FORMAT_NUMBER_COMMA_SEPARATED2`) en I, J, K y L.

> [!info] Diferencia de moneda entre exports
> Los dos reportes del consolidado convierten a una moneda de despliegue (ver [[Multimoneda]] y [[PDFs#Moneda de despliegue]]); la programación semanal y las estrategias exportan cada monto en su moneda nativa con su prefijo.

## Relacionado
- [[PDFs]] · [[Rutas]] · [[Consolidado por proyecto]] · [[Programacion semanal]] · [[Estrategias]]
- [[Acceso por proyecto]] · [[Multimoneda]] · [[Stack y versiones]]
