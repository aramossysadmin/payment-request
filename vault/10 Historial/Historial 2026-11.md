---
tipo: historial
aliases:
  - Historial noviembre 2026
tags:
  - payment-request/historial
fuentes:
  - git log
actualizado: 2026-11-04
---

# Historial noviembre 2026

Control por rol de Panel y Solicitudes de Pago, tipo de pago Nómina Fiscal, corrección del conteo de conceptos, consulta de Estrategias y tarjeta de Programación semanal. 5 commits.

### 2026-11-02 · `9d21f3d` · Panel y Solicitudes de Pago controlados desde Shield

- **Qué cambió:** Panel y Solicitudes de Pago se habilitan por rol con casillas de Shield, también para `super_admin` en el portal. Todos entran por Hojas de Inversión. La guía oculta lo que el rol no puede ver y ya no dice que los pagos de inversión van a Solicitudes de Pago.
- **Archivos principales:** `app/Models/User.php`, `app/Http/Controllers/PaymentRequestController.php`, `app/Http/Controllers/DashboardController.php`, `app/Filament/Resources/RoleResource.php`, `resources/js/components/app-sidebar.tsx`
- **Migraciones:** `database/migrations/2026_11_02_192516_add_portal_dashboard_permission.php`
- **Notas actualizadas:** [[Modulo legacy Payment Requests]] · [[Dashboards]] · [[Guia de usuario]] · [[Permisos Shield]] · [[Middleware y rutas protegidas]] · [[Roles y actores]] · [[Autenticacion y 2FA]] · [[Frontend Inertia React]] · [[Rutas]] · [[Migraciones]]
- **Decisión:** [[ADR-024 Panel y Solicitudes de Pago controlados desde Shield]]
- **Impacto en producción:** requiere respaldo antes del despliegue, porque corre una migración de datos. Los usuarios sin rol pierden ambos recursos al desplegar, y todos pasan a entrar por Hojas de Inversión. Ocultar los recursos a cada rol se hace después, desde Shield.

### 2026-11-02 · `3ec15ad` · Tipo de pago Nómina Fiscal

- **Qué cambió:** nuevo tipo de pago de inversión, disponible al crear y editar un pago y al subir sus documentos. Acepta 1 archivo PDF, imagen o Excel (`.xls`, `.xlsx`); Excel aplica solo a este tipo.
- **Archivos principales:** `app/Enums/InvestmentPaymentType.php`, `app/Http/Requests/StoreInvestmentPaymentRequest.php`, `app/Http/Requests/UpdateInvestmentPaymentRequest.php`, `app/Http/Requests/UploadInvestmentPaymentDocumentsRequest.php`, `resources/js/pages/investment-sheets/consolidated.tsx`
- **Migraciones:** ninguna
- **Notas actualizadas:** [[Enums]] · [[Solicitudes de pago de inversion]] · [[Documentos de pago]] · [[Exportaciones Excel]] · [[Comandos y scheduler]] · [[Seguridad de la aplicacion]] · [[ADR-001 Catalogo de tipos de pago en base de datos]]
- **Decisión:** ninguna
- **Impacto en producción:** ninguno en datos ni permisos. Los documentos de nómina usan las mismas reglas de acceso que los demás tipos.

### 2026-11-03 · `e9840ff` · Conteo de conceptos correcto en PostgreSQL

- **Qué cambió:** los conteos de conceptos usaban `CAST(... AS CHAR)`, que en PostgreSQL conserva solo el primer carácter del ID y agrupaba conceptos distintos. Ahora usan `AS VARCHAR`. Solo cambian los conteos; montos, presupuesto y saldos no se afectan.
- **Archivos principales:** `app/Http/Controllers/InvestmentSheetConsolidatedIndexController.php`, `app/Http/Controllers/InvestmentSheetConsolidatedController.php`, `app/Services/InvestmentDepartmentBreakdownService.php`
- **Migraciones:** ninguna
- **Notas actualizadas:** [[Consolidado por proyecto]] · [[Dashboards]]
- **Decisión:** ninguna
- **Impacto en producción:** ninguno en datos. Las Hojas de Inversión pasan a mostrar el número real de conceptos, mayor al que se veía.

### 2026-11-04 · `115feb0` · Consulta de Estrategias

- **Qué cambió:** nueva página `/strategies`, de solo lectura, para consultar por Hoja de Inversión o de todas a la vez las estrategias de todos los departamentos, en cualquier estado. Muestra fechas y semanas de captura y programación, filtra por departamento, estado y semanas, y descarga PDF y Excel. Se habilita con la casilla `view_portal::strategies` y el rol nuevo `estrategias`.
- **Archivos principales:** `app/Services/StrategyPaymentHistory.php`, `app/Http/Controllers/StrategyHistoryController.php`, `app/Exports/StrategyHistoryExport.php`, `app/Filament/Resources/RoleResource.php`, `resources/js/pages/strategies/index.tsx`, `resources/views/pdf/strategies.blade.php`
- **Migraciones:** `database/migrations/2026_11_03_184104_add_portal_strategies_permission_and_role.php`
- **Notas actualizadas:** [[Estrategias]] · [[Permisos Shield]] · [[Roles y actores]] · [[Middleware y rutas protegidas]] · [[Acceso por proyecto]] · [[Rutas]] · [[Migraciones]] · [[Guia de usuario]] · [[PDFs]] · [[Exportaciones Excel]] · [[Frontend Inertia React]] · [[Indice de decisiones]]
- **Decisión:** [[ADR-025 Consulta de Estrategias con permiso del portal]]
- **Impacto en producción:** requiere respaldo antes del despliegue, porque corre una migración de datos. Los `super_admin` verán el recurso al desplegar, salvo que se desmarque en su rol. A los demás usuarios se les asigna el rol `estrategias` desde el panel. El selector de Hojas ahora admite la opción "Todas" solo en esta página.

### 2026-11-04 · sha pendiente · Total a procesar, ancho fijo y selector de semana en Programación

- **Qué cambió:** la tarjeta "Total a procesar" significa siempre lo pendiente de comprobante. En semanas con programación guardada ya no muestra "Total programado" como monto principal; ese dato pasa a un renglón "Programado" debajo. La página conserva su ancho al cambiar de semana. El botón de semana abre un selector con búsqueda, y las flechas ya no se saltan la semana 53. No cambian datos ni la suma entre monedas. El navegador de Hojas de Inversión sigue igual.
- **Archivos principales:** `resources/js/pages/weekly-payment-schedule/index.tsx`, `resources/js/components/week-navigator.tsx`
- **Migraciones:** ninguna
- **Notas actualizadas:** [[Programacion semanal]]
- **Decisión:** ninguna
- **Impacto en producción:** ninguno en datos. Solo cambia la pantalla de Programación de Pagos.

## Relacionado
- [[Indice del historial]] · [[Historial 2026-09]] · [[Permisos Shield]]
