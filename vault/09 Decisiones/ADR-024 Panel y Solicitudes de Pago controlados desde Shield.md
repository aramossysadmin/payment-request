---
tipo: decision
aliases:
  - ADR-024 Panel y Solicitudes de Pago controlados desde Shield
tags:
  - payment-request/decisiones
fuentes:
  - app/Models/User.php
  - app/Http/Middleware/HandleInertiaRequests.php
  - app/Http/Controllers/DashboardController.php
  - app/Http/Controllers/PaymentRequestController.php
  - app/Http/Requests/StorePaymentRequestRequest.php
  - app/Filament/Resources/RoleResource.php
  - config/fortify.php
  - bootstrap/app.php
  - database/migrations/2026_11_02_192516_add_portal_dashboard_permission.php
estado: vigente
actualizado: 2026-11-02
---

# ADR-024 Panel y Solicitudes de Pago controlados desde Shield

Panel y Solicitudes de Pago se habilitan o deshabilitan por rol con casillas de Shield, y todos los usuarios entran al portal por Hojas de Inversión.

## Contexto
Usuarios capturaban pagos de inversión en Solicitudes de Pago, el módulo tradicional, y luego preguntaban por qué no se pagaron. Las casillas de Shield no controlaban el portal: el menú estaba fijo, la lista y el formulario no revisaban permisos, y el Panel no tenía tarjeta. Además, el login llevaba a `/payment-requests`.

## Decisión
- **Casilla real, también para `super_admin`:** el portal usa `User::hasPortalPermission()` (`checkPermissionTo()`), que no pasa por `Gate::before`. Así se puede ocultar a `super_admin` desmarcando su rol, sin quitarle `/admin`.
- **Una sola tarjeta "Solicitud de Pago"** para portal y `/admin`.
- **Tarjeta nueva "Panel"** con `view_portal::dashboard`, agregada en `RoleResource`. Debe estar en el formulario porque `EditRole` ejecuta `syncPermissions()`.
- **Hojas de Inversión como entrada para todos:** `fortify.home`, `redirectUsersTo()` y el logo del menú.
- **Redirección con aviso en lista y formulario**, porque los navegadores autocompletan esas URL. Guardar sin permiso responde 403.
- **La casilla del Panel se asigna a todos los roles al desplegar;** se desmarca por rol después.

## Consecuencias
- Ocultar o mostrar los recursos no requiere despliegue.
- Los permisos se suman: un usuario con varios roles deja de ver un recurso solo si ninguno tiene la casilla.
- Un rol sin la casilla "Ver el listado" de Solicitud de Pago pierde el portal y `/admin`, salvo `super_admin`, que conserva `/admin`.
- Con la casilla desmarcada, `super_admin` todavía puede abrir un detalle con su URL directa, porque `PaymentRequestPolicy` pasa por `Gate::before`.
- Un aprobador con token vencido y sin la tarjeta no puede atender solicitudes abiertas hasta volver a marcarla.
- Las tarjetas marcadas a medias dan resultados parciales: por ejemplo, con "Crear" y sin "Ver el listado", al guardar se redirige con el aviso.
- Los demás recursos del menú siguen sin casillas en el portal.

## Fecha
2026-11-02

## Fuentes
- Commit de esta decisión; ver [[Historial 2026-11]].

## Relacionado
- [[Indice de decisiones]] · [[Permisos Shield]] · [[Modulo legacy Payment Requests]] · [[Dashboards]] · [[Autenticacion y 2FA]]
