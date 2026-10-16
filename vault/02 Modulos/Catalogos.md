---
tipo: modulo
aliases:
  - Catálogos
  - Catálogos Filament
tags:
  - payment-request/catalogos
fuentes:
  - app/Filament/Resources/BranchResource.php
  - app/Filament/Resources/CurrencyResource.php
  - app/Filament/Resources/DepartmentResource.php
  - app/Filament/Resources/PositionResource.php
  - app/Filament/Resources/SocietyResource.php
  - app/Filament/Resources/ExpenseConceptResource.php
  - app/Filament/Resources/ExpenseConceptResource/Pages/ListExpenseConcepts.php
  - app/Filament/Resources/InvestmentExpenseCategoryResource.php
  - app/Filament/Resources/InvestmentExpenseConceptResource.php
  - app/Filament/Resources/PaymentTypeResource.php
  - app/Filament/Resources/StrategyResource.php
  - app/Filament/Resources/StrategyResource/Pages/EditStrategy.php
  - app/Filament/Resources/UserResource.php
  - app/Filament/Resources/UserResource/Pages/EditUser.php
  - app/Filament/Resources/UserResource/Pages/CreateUser.php
  - app/Filament/Resources/RoleResource.php
  - app/Enums/DocumentMode.php
  - app/Enums/PaymentTypeCategory.php
actualizado: 2026-10-15
---

# Catálogos

Recursos del panel Filament (`/admin`) que mantienen los datos maestros del portal. Todos usan permisos Shield vía su Policy (`view_any_*`, `create_*`, `update_*`, `delete_*`, …; ver [[Permisos Shield]]), tienen acciones de editar, eliminar (soft delete) y restaurar, y muestran los registros en papelera (`getEloquentQuery()` quita `SoftDeletingScope`). [[Proyectos]] se documenta aparte.

## Mapa de navegación

| Grupo Filament | Recursos (orden) |
|---|---|
| Organización | Sociedad (1) · Sucursal (2) · Departamento (3) · Posición (4) |
| Catálogos | Moneda (1) · Concepto de Gasto (2) · Tipo de Pago (3) · Proyecto (4) · Categoría de Gasto de Inversión (5) · Concepto de Inversión (6) · Estrategia (7) |
| Accesos | Usuarios (1) |
| Grupo de Filament Shield | Roles (`/admin/shield/roles`) |

> [!info] Grupos declarados vs usados
> `AdminPanelProvider` solo declara los grupos colapsables "Administración" y "Auditoría"; los recursos usan además "Organización", "Catálogos", "Accesos", "Operaciones" y "Configuración", que Filament crea de forma implícita. Ver [[Panel Filament]].

## Organización

### Sociedad (`SocietyResource` → [[Society]])

| Campo | Regla | Uso |
|---|---|---|
| Nombre | obligatorio, único (se guarda en mayúsculas) | Razón social del grupo. |
| RFC / CIF | máx. 20, alfanumérico | RFC México (12–13) o CIF España (9). |
| Constancia de Situación Fiscal | PDF, máx. 10 MB, disco `local`, carpeta `society-fiscal-documents`, privada | Constancia SAT o certificado censal AEAT. |

Se muestra en el índice de Hojas de Inversión (nombre y RFC de la sociedad de la sucursal del proyecto). Filtro de tabla: ninguno salvo papelera.

### Sucursal (`BranchResource` → [[Branch]])

| Sección | Campos |
|---|---|
| Información general | Nombre (obligatorio), Sociedad (obligatoria), Centro de Costos (CeCo, máx. 50), Activa (toggle) |
| Conexión SAP | Base de datos SAP, Sucursal SAP (BPLId), Usuario SAP (Service Layer), Contraseña SAP |

- La contraseña SAP se guarda **cifrada** (`sap_password` con cast `encrypted`, depende de `APP_KEY`); el campo es tipo password y si se deja vacío conserva la actual. Ver [[APP_KEY y datos cifrados]].
- Columna "SAP" en la tabla: badge "Configurado" / "Sin SAP" según `Branch::isSapConfigured()` (activa y con datos SAP completos).
- El texto de ayuda de "Activa" indica que es un control reservado: el filtrado en selectores y la exclusión del envío a SAP se aplicarán cuando se active la integración. Ver [[SAP Business One]].
- Uso: sucursal de Solicitudes de Pago legacy, Solicitudes y Pagos de Inversión, y de cada proyecto.

### Departamento (`DepartmentResource` → [[Department]])

| Campo | Regla |
|---|---|
| Nombre | obligatorio, único (mayúsculas) |
| Descripción | opcional |
| Autorizador Nivel 1 | obligatorio, solo usuarios activos |
| Autorizador Nivel 2 | opcional, solo usuarios activos, debe ser distinto del nivel 1 |

Uso: los autorizadores definen el flujo del [[Modulo legacy Payment Requests]] (los departamentos `ADMINISTRACIÓN` y `TESORERÍA` se buscan por nombre exacto) y la visibilidad (`visibleTo`) de solicitudes legacy y de inversión. La pertenencia de usuarios a departamentos se gestiona desde Usuarios (pivote `department_user`).

> [!warning] Nombres con significado
> Renombrar los departamentos `ADMINISTRACIÓN` o `TESORERÍA` rompe el pipeline legacy (`ApprovalService::PIPELINE`).

### Posición (`PositionResource` → [[Position]])

Nombre (obligatorio, único) y descripción. Solo se asigna al usuario (`users.position_id`, obligatorio en el formulario de usuario); no participa en permisos ni flujos.

## Catálogos

### Moneda (`CurrencyResource` → [[Currency]])

| Campo | Regla |
|---|---|
| Nombre | obligatorio, único |
| Prefijo | obligatorio, máx. 10 (ej. `MXN`) |
| Tipo de cambio | obligatorio, ≥ 0, paso 0.0001, default 1 |

`exchange_rate` significa "1 unidad = X MXN" y se usa para normalizar sumas a MXN y para convertir a la moneda de despliegue en consolidado, PDFs y Excel. Ver [[Multimoneda]].

### Concepto de Gasto (`ExpenseConceptResource` → [[ExpenseConcept]])

- Campos: Nombre (obligatorio, único) y Activo (los inactivos no aparecen en nuevas solicitudes de pago).
- Uso: concepto de las Solicitudes de Pago legacy (`ExpenseConcept::active()` en el formulario) y campo histórico `expense_concept_id` de Solicitudes de Inversión.
- Acciones extra en el listado:
  - **Descargar Template**: CSV `template_conceptos_de_gasto.csv` con columna `Nombre` y dos filas de ejemplo.
  - **Importar CSV**: sube a `csv-imports` (disco `local`), lee con conversión Windows-1252 → UTF-8, pasa nombres a mayúsculas, omite filas de ejemplo, nombres > 255 caracteres, existentes (activos, inactivos o en papelera) y crea el resto. Al terminar borra el archivo temporal y muestra un resumen.

### Tipo de Pago (`PaymentTypeResource` → [[PaymentType]])

| Campo | Opciones |
|---|---|
| Nombre | obligatorio, único |
| Documentos de Factura (PDF + XML) | `DocumentMode`: Deshabilitado / Opcional / Obligatorio (default Deshabilitado) |
| Documentos Adicionales | `DocumentMode` (default Opcional) |
| Categoría | `PaymentTypeCategory`: `payment` "Solicitud de Pago" / `investment` "Concepto de Inversión" (default `payment`) |
| Activo | toggle |

Uso: el portal legacy solo ofrece `PaymentType::active()->forPayments()` y los modos controlan la validación de adjuntos en `StorePaymentRequestRequest` (factura = exactamente 1 PDF + 1 XML). La categoría `investment` se usa en el selector de `InvestmentRequestResource` (Filament).

> [!info] Tipos de pago de inversión
> Los Pagos de Inversión **no** usan este catálogo: su tipo es el enum `InvestmentPaymentType` (factura, reembolso, estrategia, anticipo, …). Ver [[Enums]] y [[Solicitudes de pago de inversion]].

### Categoría de Gasto de Inversión (`InvestmentExpenseCategoryResource` → [[InvestmentExpenseCategory]])

| Campo | Regla |
|---|---|
| Categorías (nombre) | obligatorio, único **dentro de la misma Super Categoría** |
| Super Categoría | opcional; se elige de las existentes o se crea una nueva (se guarda en mayúsculas) |
| Departamentos | multiselección, mínimo 1 (pivote) |
| Activo | las inactivas no están disponibles para nuevos conceptos |

Tabla con conteo de conceptos y filtros por departamento y estado. Uso: los departamentos de la categoría determinan qué conceptos de inversión ve cada usuario al capturar una Solicitud de Inversión (`InvestmentRequestController::create`: conceptos activos cuya categoría está ligada a alguno de sus departamentos).

### Concepto de Inversión (`InvestmentExpenseConceptResource` → [[InvestmentExpenseConcept]])

| Campo | Regla |
|---|---|
| Concepto | obligatorio |
| Categoría de Gasto | obligatoria |
| Número de artículo SAP | `sap_item_code`, único (ItemCode de SAP B1) |
| Activo | los inactivos no están disponibles para nuevas solicitudes |

Filtros: categoría, "Mapeado a SAP" (con / sin código) y estado. Es el concepto presupuestal sobre el que se agrupan solicitudes, aditivas y pagos. Ver [[Presupuesto por concepto]].

### Estrategia (`StrategyResource` → [[Strategy]])

- **Tasa** (%, 0.001–100, obligatoria): solo editable al crear; al escribirla se calculan automáticamente **Nombre** y **Factor** (tasa ÷ 100), ambos de solo lectura.
- **Activa**: las inactivas no aparecen al solicitar un pago.
- Al crear se rechaza una tasa repetida contra todas las estrategias (activas, inactivas y en papelera) y se ofrece un botón para activar o restaurar la existente.
- `EditStrategy` descarta cualquier campo que no sea `is_active`.

Ejemplo ficticio: Estrategia 4.5 % sobre $10,000.00 → $10,450.00. Detalle en [[Estrategias]].

## Accesos

### Usuarios (`UserResource` → [[User]])

| Sección | Campos |
|---|---|
| (general) | Nombre, Correo (único), Contraseña y confirmación (obligatorias al crear, mín. 8; vacías en edición = sin cambio) |
| Asignación | Departamento Principal (obligatorio salvo roles super_admin, ceo, project_manager), Posición (obligatoria), Departamentos Adicionales (checkboxes) |
| Roles y estado | Roles (CheckboxList; solo un super_admin ve el rol `super_admin`), Activo |
| Proyectos autorizados | Multiselección; solo para usuarios sin rol privilegiado. Ver [[Acceso por proyecto]] |

Reglas implementadas:

- **Multi-departamento**: el principal y los adicionales se sincronizan en la pivote `department_user` al crear (`CreateUser::afterCreate`) y editar (`EditUser::afterSave`). Si cambia el principal, el anterior pasa automáticamente a adicional.
- **Bloqueo al quitar departamentos**: `EditUser::beforeSave()` impide quitar un departamento donde el usuario tenga Solicitudes de Inversión no completadas, Pagos de Inversión en proceso o lotes activos; muestra el detalle y detiene el guardado.
- Un super_admin no puede quitarse a sí mismo el rol `super_admin`.
- `Activo` (`is_active`) controla el acceso al panel (`User::canAccessPanel()`).
- Tabla: nombre, correo, departamento, posición, roles, proyectos (oculta), activo; filtros por estado, rol y papelera.

### Roles (`RoleResource`, Filament Shield)

Recurso de Shield publicado en `app/Filament/Resources/RoleResource.php`: nombre, guard, selector "seleccionar todo" y matriz de permisos por recurso, página y widget. Listado con conteo de permisos. Ver [[Permisos Shield]] y [[Roles y actores]].

## Relacionado
- [[Panel Filament]] · [[Permisos Shield]] · [[Proyectos]] · [[Estrategias]] · [[Multimoneda]]
- [[Branch]] · [[Currency]] · [[Department]] · [[Position]] · [[Society]] · [[ExpenseConcept]] · [[InvestmentExpenseCategory]] · [[InvestmentExpenseConcept]] · [[PaymentType]] · [[Strategy]] · [[User]] · [[Role]]
