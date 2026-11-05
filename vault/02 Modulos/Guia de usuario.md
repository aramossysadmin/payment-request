---
tipo: modulo
aliases:
  - Guía de usuario
  - Guía de Usuario
tags:
  - payment-request/guia
fuentes:
  - resources/js/pages/guide/index.tsx
  - routes/web.php
  - resources/js/components/app-sidebar.tsx
actualizado: 2026-11-04
---

# Guía de usuario

Página estática dentro del portal (`/guide`) con instrucciones de uso para el usuario final. Es contenido **escrito a mano en el componente React**: no consulta datos del backend ni se adapta al rol del usuario.

## Ruta y acceso

- `GET /guide` (`guide`), closure `fn () => Inertia::render('guide/index')` dentro del grupo `auth` + `verified` de `routes/web.php`. Ver [[Rutas]].
- Menú lateral: grupo "Documentación" → "Guía de Usuario" (`app-sidebar.tsx`), visible para todos los usuarios autenticados.
- La pestaña "Solicitudes de Pago" solo aparece con `auth.can.viewPaymentRequests`; sin ella, la guía abre en "Presupuestos de Inversión". En la pestaña General, las líneas "Plataforma", "Solicitudes" y "Estrategias" dependen de `auth.can.viewDashboard`, `auth.can.viewPaymentRequests` y `auth.can.viewStrategies`.

## Estructura de la página

Componente `resources/js/pages/guide/index.tsx`: título "Guía de Usuario", subtítulo "Consulta cómo utilizar las funcionalidades del sistema de solicitudes" y cuatro pestañas (`Tabs`).

| Pestaña (`value`) | Componente | Secciones |
|---|---|---|
| Solicitudes de Pago (`payment-requests`) | `PaymentRequestsGuide` | Crear una Solicitud de Pago · Consultar Solicitudes (filtros Pendientes / Finalizados / Todos, búsqueda) · Flujo de Aprobación (3 etapas: Departamento, Administración, Tesorería; hasta 2 niveles; rechazo de nivel 2 regresa a nivel 1) · Aprobar o Rechazar (web y por correo, enlace de 48 h, motivo mínimo 10 caracteres) · Folios SAP (Factura Proveedores en Administración, Pago Efectuado en Tesorería) · Descargar PDF (solo finalizadas) · Editar (solo en Pendiente Departamento) |
| Presupuestos de Inversión (`investment-requests`) | `InvestmentRequestsGuide` | Crear una Solicitud De Inversión · Consultar Conceptos · Flujo de Aprobación (autorizador único, pasa directo a Completado) · Aprobar o Rechazar · Hojas Consolidadas · Solicitar Pago · Control Presupuestal · Editar y Descargar PDF |
| Tesorería (`tesorery`) | `TesoreryGuide` | Programación de Pagos Semanal (semana natural) · Seleccionar Pagos (checkbox y motivo de exclusión) · Autorizar Lote de Pagos · Historial de Pagos · Notificaciones de tesorería |
| General (`general`) | `GeneralGuide` | Navegación del sistema (secciones del sidebar) · Dashboard · Notificaciones (correo y campana) · Documentos Adjuntos (enlaces firmados de 48 h) · Perfil y Seguridad |

Incluye componentes auxiliares de presentación: `SectionTitle`, `StatusBadgeGuide` (badges de estado de ejemplo) y `FlowArrow` (flechas del diagrama de flujo).

Historial de cambios del archivo (git): creada en abril 2026, reorganizada a 4 pestañas con Tesorería en abril 2026 y renombrado "Conceptos de Inversión" → "Solicitudes De Inversión" en mayo 2026. No tiene cambios posteriores.

## Diferencias con el comportamiento actual

La guía no se ha actualizado desde mayo 2026 y describe varios flujos que ya cambiaron:

> [!warning] Contenido desactualizado
> - No menciona: [[Lotes de pago]], [[Aprobacion del CEO]], [[Revision del PM]], [[Documentos de pago]], [[Comprobantes de pago]], el tipo de pago [[Estrategias|estrategia]] (solo describe la consulta en la pestaña General), la [[Politica de ventanas de pago]], el estado cerrado de [[Proyectos]] ni el [[Acceso por proyecto]].
> - **Flujo de Solicitudes de Inversión**: dice que hay un autorizador único; hoy, si el proyecto tiene `requires_pm_approval`, la aprobación va a los PM, y existe un bypass global de autorización. Ver [[Flujo de solicitud de inversion]].
> - **Tesorería**: describe "Autorizar Lote" / "Rechazar Lote" y que los pagos pasan a "Pagado"; la [[Programacion semanal]] actual envía la programación a aprobación por correo y los estados son `scheduled_for_bank` / `receipt_attached`.
> - **Control Presupuestal**: dice que el comprometido son "conceptos pendientes"; hoy comprometido y pagado se calculan sobre estados de pagos de inversión. Ver [[Dashboards]].
> - **Dashboard**: menciona tarjetas de "Total, pendientes, completadas y rechazadas"; el Panel actual muestra Pendientes (por etapa), Por Aprobar y Monto del Mes.
> - **Perfil y Seguridad**: no menciona apariencia ni autenticación de dos factores. Ver [[Autenticacion y 2FA]].

> [!question] Por confirmar
> Si la guía se va a mantener como documentación oficial para usuarios, requiere una actualización completa; si no, conviene ocultarla del menú para evitar confusión.

## Relacionado
- [[Frontend Inertia React]] · [[Rutas]] · [[Dashboards]] · [[Modulo legacy Payment Requests]]
- [[Solicitudes de inversion]] · [[Consolidado por proyecto]] · [[Programacion semanal]] · [[Notificaciones in-app]]
