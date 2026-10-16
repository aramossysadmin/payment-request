---
tipo: integracion
aliases:
  - SAP Business One
  - SAP B1
tags:
  - payment-request/integracion
fuentes:
  - config/sap.php
  - app/Models/Branch.php
  - app/Models/InvestmentExpenseConcept.php
  - app/Models/Society.php
  - app/Filament/Resources/BranchResource.php
  - app/Filament/Resources/InvestmentExpenseConceptResource.php
  - app/Filament/Resources/PaymentRequestResource.php
  - app/Filament/Resources/InvestmentRequestResource.php
  - app/Http/Controllers/PaymentRequestApprovalController.php
  - app/Services/ApprovalService.php
  - database/migrations/2026_06_22_101052_add_sap_credentials_to_branches_table.php
  - database/migrations/2026_07_20_154721_add_cost_center_to_branches_table.php
  - database/migrations/2026_06_22_215829_add_sap_item_code_to_investment_expense_concepts_table.php
  - database/migrations/2026_03_04_030125_add_sap_folio_columns_to_payment_requests_table.php
  - resources/js/components/payment-request-detail.tsx
actualizado: 2026-10-15
---

# SAP Business One

Estado de la relación del portal con SAP Business One (SAP B1). Hoy **no hay comunicación automática** con SAP: existe la configuración y los campos de mapeo preparados para una futura integración vía Service Layer, y en el módulo legacy se capturan **manualmente** los folios que SAP generó.

> [!info] Estado actual
> - `config/sap.php` existe, pero ningún código lo lee (no hay `config('sap.*')`, ni cliente HTTP, ni jobs de envío).
> - Las sucursales ya guardan credenciales SAP y los conceptos de inversión su `ItemCode`; ambos modelos tienen métodos de verificación previa (`isSapConfigured()`, `isSapMapped()`) que aún no se invocan fuera del panel.
> - Lo único operativo son los **folios SAP capturados a mano** en solicitudes legacy.

## Configuración global (`config/sap.php`)

| Clave | Variable de entorno | Default | Uso previsto |
|---|---|---|---|
| `sap.service_layer_url` | `SAP_SERVICE_LAYER_URL` | `null` | URL única del Service Layer de SAP B1 para todo el portal. |
| `sap.timeout` | `SAP_TIMEOUT` | `30` (segundos) | Timeout de las llamadas. |
| `sap.verify_ssl` | `SAP_VERIFY_SSL` | `false` | Verificación del certificado TLS del Service Layer. |

> [!warning] `verify_ssl` desactivado por defecto
> Cuando se implemente el cliente, conviene activar `SAP_VERIFY_SSL` (o instalar el certificado de la CA interna) para no aceptar certificados arbitrarios.

## Credenciales por sucursal ([[Branch]])

Cada sucursal (`branches`) pertenece a una [[Society]] y guarda su propia conexión SAP:

| Columna | Tipo | Etiqueta en el panel | Nota |
|---|---|---|---|
| `cost_center` | string(50), nullable | Centro de Costos (CeCo) | Para mapeo contable/operativo. Aún sin restricción `unique`. |
| `sap_database` | string, nullable | Base de datos SAP | Nombre de la compañía/BD en SAP. |
| `sap_branch_id` | string, nullable | Sucursal SAP | Identificador `BPLId` de SAP. |
| `sap_user` | string, nullable | Usuario SAP | Usuario del Service Layer. |
| `sap_password` | text, nullable, cast `encrypted` | Contraseña SAP | Cifrada con `APP_KEY`. Campo `password()->revealable()`; si se deja vacío al editar, **se conserva** la actual (`dehydrated(fn ($state) => filled($state))`). |
| `is_active` | boolean, default `true` | Activa | Según el texto de ayuda, es un toggle "reservado": todavía no filtra selectores del portal ni excluye envíos a SAP. |

- `Branch::isSapConfigured()` = activa **y** con los 4 campos SAP llenos. La tabla del panel muestra la columna "SAP" con badge `Configurado` / `Sin SAP`.
- `Branch::getActivitylogOptions()` excluye `sap_password` del activity log.
- Los selectores de sucursal del portal (`PaymentRequestController`, `InvestmentRequestController`, `InvestmentSheetConsolidatedController`) listan **todas** las sucursales (`Branch::orderBy('name')`), sin filtrar por `is_active`.

> [!danger] `sap_password` depende de `APP_KEY`
> Si `APP_KEY` cambia o se pierde, las contraseñas SAP se vuelven irrecuperables y habría que recapturarlas sucursal por sucursal. Ver [[APP_KEY y datos cifrados]].

> [!warning] Solo nombres, nunca valores
> Esta bóveda documenta nombres de campos y variables. Las credenciales reales viven en la base de datos (cifradas) y en el `.env` del servidor; no se copian a notas, commits ni tickets.

## Mapeo de conceptos de inversión ([[InvestmentExpenseConcept]])

| Columna | Detalle |
|---|---|
| `sap_item_code` | string(50), nullable, **único**. El mutator recorta espacios, pasa a mayúsculas y guarda `null` si queda vacío. |

- `InvestmentExpenseConcept::isSapMapped()` indica si el concepto tiene `ItemCode`.
- En el panel (`InvestmentExpenseConceptResource`) hay columna y filtro ternario "con / sin `sap_item_code`".

## Datos fiscales de sociedades ([[Society]])

`societies.rfc` (string 20, mayúsculas) y `societies.constancia_situacion_fiscal` (ruta del PDF de la CSF en `storage/app/private/society-fiscal-documents`). No intervienen en ninguna llamada a SAP hoy.

## Folios SAP en el módulo legacy

En [[Modulo legacy Payment Requests]], el número que SAP asignó a cada documento se captura a mano:

| Columna | Etiqueta | Se captura en | Quién puede editarla |
|---|---|---|---|
| `number_purchase_invoices` | Folio SAP Factura Proveedores | Al aprobar la etapa **Administración** | Quien aprobó la etapa `administration` (o `super_admin` en el panel) |
| `number_vendor_payments` | Folio SAP Pago Efectuado | Al aprobar la etapa **Tesorería** | Quien aprobó la etapa `treasury` (o `super_admin` en el panel) |

Flujo en el código:
1. `POST payment-requests/{id}/approve` acepta `number_purchase_invoices` y `number_vendor_payments` (`nullable|integer|min:1`). `ApprovalService::approve()` guarda los que vengan (`array_intersect_key`).
2. Después de aprobar, `PATCH payment-requests/{id}/sap-folios` (`PaymentRequestApprovalController::updateSapFolios`) permite corregirlos: solo actualiza el campo de la etapa que el usuario aprobó (`canEditField()` busca una aprobación `approved` del usuario en esa etapa); si no aprobó ninguna → 403.
3. En el panel, `PaymentRequestResource::canEditSapField()` habilita cada campo para `super_admin` o para quien aprobó esa etapa.
4. En el portal, `components/payment-request-detail.tsx` muestra y edita los folios. La [[Guia de usuario]] explica ambos momentos de captura.

`investment_requests` también tiene ambas columnas y `InvestmentRequestResource` las muestra con la misma lógica (`InvestmentRequestApproval` con `stage = administration/treasury`).

> [!question] Por confirmar
> Las aprobaciones de solicitudes de inversión se crean con etapa única (PM o autorizador global; `stage` default `department`), así que en la práctica nadie tendría una aprobación `administration`/`treasury` de inversión y solo `super_admin` podría editar esos folios. Confirmar si los folios SAP siguen siendo relevantes para inversión.

## Relacionado
- [[Branch]] · [[Society]] · [[InvestmentExpenseConcept]] · [[Modulo legacy Payment Requests]] · [[Flujo legacy de solicitud de pago]] · [[Catalogos]] · [[APP_KEY y datos cifrados]] · [[Seguridad de la aplicacion]]
