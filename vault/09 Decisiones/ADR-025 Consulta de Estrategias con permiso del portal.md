---
tipo: decision
aliases:
  - ADR-025 Consulta de Estrategias con permiso del portal
tags:
  - payment-request/decisiones
fuentes:
  - app/Services/StrategyPaymentHistory.php
  - app/Http/Controllers/StrategyHistoryController.php
  - app/Filament/Resources/RoleResource.php
  - app/Http/Middleware/HandleInertiaRequests.php
  - database/migrations/2026_11_03_184104_add_portal_strategies_permission_and_role.php
estado: vigente
actualizado: 2026-11-04
---

# ADR-025 Consulta de Estrategias con permiso del portal

La consulta de solo lectura de estrategias se habilita con una casilla de Shield y un rol propio, y muestra todas las Hojas de Inversión y departamentos.

## Contexto
Quien da seguimiento a las estrategias necesita verlas de todos los departamentos, incluidas las de Hojas que no tiene asignadas, sin ganar capacidad de capturar o aprobar pagos.

## Decisión
- **Permiso `view_portal::strategies` y rol `estrategias`:** el rol solo da esta consulta y se suma a los que ya tenga el usuario.
- **Casilla real, también para `super_admin`:** usa `User::hasPortalPermission()`, igual que [[ADR-024 Panel y Solicitudes de Pago controlados desde Shield]]. La migración la marca en `super_admin`, y se puede desmarcar después.
- **Sin [[Acceso por proyecto]]:** la casilla da acceso a todas las Hojas y departamentos, incluida la opción "Todas".
- **Todos los estados:** se incluyen borradores, rechazados y cancelados, y los pagos que cambiaron de tipo después de capturarse como estrategia.
- **Semanas calculadas desde las fechas mostradas**, no desde `payment_week_number`.

## Consecuencias
- Asignar o quitar la consulta no requiere despliegue.
- Quien tenga la casilla puede ver montos y documentos de cualquier Hoja, así que debe asignarse solo a quien lo necesite.
- `RoleResource` debe conservar la tarjeta del permiso, porque `EditRole` ejecuta `syncPermissions()` y quitaría del rol un permiso sin casilla.
- Los filtros de semana se aplican en PHP; si el volumen creciera mucho, habría que revisar la carga completa de pagos en la pantalla y los reportes.

## Relacionado
- [[Indice de decisiones]] · [[Estrategias]] · [[Permisos Shield]] · [[Roles y actores]]
