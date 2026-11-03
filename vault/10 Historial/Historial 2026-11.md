---
tipo: historial
aliases:
  - Historial noviembre 2026
tags:
  - payment-request/historial
fuentes:
  - git log
actualizado: 2026-11-02
---

# Historial noviembre 2026

Control por rol de Panel y Solicitudes de Pago. 1 commit.

### 2026-11-02 · sha pendiente · Panel y Solicitudes de Pago controlados desde Shield

- **Qué cambió:** Panel y Solicitudes de Pago se habilitan por rol con casillas de Shield, también para `super_admin` en el portal. Todos entran por Hojas de Inversión. La guía oculta lo que el rol no puede ver y ya no dice que los pagos de inversión van a Solicitudes de Pago.
- **Archivos principales:** `app/Models/User.php`, `app/Http/Controllers/PaymentRequestController.php`, `app/Http/Controllers/DashboardController.php`, `app/Filament/Resources/RoleResource.php`, `resources/js/components/app-sidebar.tsx`
- **Migraciones:** `database/migrations/2026_11_02_192516_add_portal_dashboard_permission.php`
- **Notas actualizadas:** [[Modulo legacy Payment Requests]] · [[Dashboards]] · [[Guia de usuario]] · [[Permisos Shield]] · [[Middleware y rutas protegidas]] · [[Roles y actores]] · [[Autenticacion y 2FA]] · [[Frontend Inertia React]] · [[Rutas]] · [[Migraciones]]
- **Decisión:** [[ADR-024 Panel y Solicitudes de Pago controlados desde Shield]]
- **Impacto en producción:** requiere respaldo antes del despliegue, porque corre una migración de datos. Los usuarios sin rol pierden ambos recursos al desplegar, y todos pasan a entrar por Hojas de Inversión. Ocultar los recursos a cada rol se hace después, desde Shield.

## Relacionado
- [[Indice del historial]] · [[Historial 2026-09]] · [[Permisos Shield]]
