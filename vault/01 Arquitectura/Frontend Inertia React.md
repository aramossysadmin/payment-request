---
tipo: arquitectura
aliases:
  - Frontend Inertia React
tags:
  - payment-request/arquitectura
fuentes:
  - resources/js/app.tsx
  - resources/js/ssr.tsx
  - resources/js/layouts/
  - resources/js/components/
  - resources/js/pages/
  - resources/js/contexts/display-currency.tsx
  - resources/js/hooks/
  - resources/js/lib/
  - resources/js/types/
  - resources/css/app.css
  - resources/views/app.blade.php
  - vite.config.ts
  - components.json
actualizado: 2026-11-04
---

# Frontend Inertia React

El portal de usuarios (todo lo que no es `/admin`) es una SPA servida por Inertia v2 con React 19 + TypeScript + Tailwind 4 y componentes shadcn/ui. Laravel decide qué página y qué props se renderizan; React solo pinta e interactúa.

## Arranque

- `resources/views/app.blade.php`: plantilla raíz. Aplica la clase `dark` según la cookie `appearance` (compartida por `HandleAppearance`), carga `@vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])` y el `lang` del HTML sale de `app()->getLocale()`.
- `resources/js/app.tsx`: `createInertiaApp` resuelve páginas con `import.meta.glob('./pages/**/*.tsx')`, envuelve **cada página** en `<DisplayCurrencyProvider>` y llama `initializeTheme()`. Título: `"{título} - {VITE_APP_NAME}"`.
- `resources/js/ssr.tsx` existe, pero SSR está **deshabilitado** (`config/inertia.php`).
- `vite.config.ts`: plugins `laravel` (entrada `app.css` + `app.tsx`), `react` con **React Compiler** (`babel-plugin-react-compiler`), `tailwindcss` y `wayfinder({ formVariants: true })`.

## Estructura de `resources/js`

| Carpeta | Contenido |
|---|---|
| `pages/` | Una carpeta por módulo. Ver tabla de páginas abajo. |
| `layouts/` | `app-layout.tsx` → `app/app-sidebar-layout.tsx` (existe también `app-header-layout.tsx` sin uso principal); `auth-layout.tsx` → `auth/auth-simple-layout.tsx` (hay variantes `card` y `split`); `settings/layout.tsx` (menú Perfil, Contraseña, Apariencia). |
| `components/` | Componentes de negocio y de shell (ver abajo). |
| `components/ui/` | shadcn/ui estilo `new-york` sobre Radix: `button`, `card`, `dialog`, `sheet`, `select`, `popover`, `command`, `tabs`, `sidebar`, `input-otp`, `skeleton`, `tooltip`, etc. |
| `contexts/` | `display-currency.tsx`: moneda de visualización global. Ver [[Multimoneda]]. |
| `hooks/` | `use-appearance` (tema claro/oscuro/sistema), `use-payment-policy` (+ `useTimeRemaining`), `use-two-factor-auth`, `use-clipboard`, `use-current-url`, `use-initials`, `use-mobile`, `use-mobile-navigation`. |
| `lib/` | `utils.ts` (`cn`, `toUrl`), `currency.ts` (`formatCurrency`), `format-policy-time.ts` (horas en TZ México + hora local), `payment-type-labels.ts`. |
| `types/` | Tipos de dominio (`payment-request.ts`, `investment-request.ts`, `payment-policy.ts`, `dashboard.ts`, `auth.ts`, `navigation.ts`, `ui.ts`) y `global.d.ts` con las props compartidas de Inertia. |
| `actions/`, `routes/`, `wayfinder/` | **Generados por Wayfinder** al correr Vite; ignorados por git. |

## Páginas (`resources/js/pages`)

| Carpeta / archivo | Módulo | Nota de la bóveda |
|---|---|---|
| `dashboard.tsx` | Panel inicial con KPIs y gráficas (props diferidas con `<Deferred>` y skeletons) | [[Dashboards]] |
| `payment-requests/` (`index`, `create`, `edit`, `show`) | Solicitudes de pago (legacy) | [[Modulo legacy Payment Requests]] |
| `investment-sheets/` (`index`, `create`, `edit`, `show`) | Solicitudes de inversión | [[Solicitudes de inversion]] |
| `investment-sheets/consolidated-index.tsx`, `consolidated.tsx` | Hojas de inversión por proyecto (captura de pagos, historial, reportes). `consolidated.tsx` es el archivo más grande del frontend (~3,900 líneas). | [[Consolidado por proyecto]] |
| `investment-dashboard/index.tsx` | Control presupuestal | [[Dashboards]] |
| `investment-payment-review/index.tsx` | Revisión de pagos del PM | [[Revision del PM]] |
| `investment-batch-approval/` (`show`, `success`, `invalid`) | Aprobación del CEO por token (pública) | [[Aprobacion del CEO]] |
| `weekly-payment-schedule/index.tsx` | Programación de pagos semanal | [[Programacion semanal]] |
| `guide/index.tsx` | Guía de usuario estática | [[Guia de usuario]] |
| `auth/*` | Login, 2FA challenge, reset, verify, confirm password, register | [[Autenticacion y 2FA]] |
| `settings/*` | Perfil, contraseña, apariencia, 2FA | [[Autenticacion y 2FA]] |
| `welcome.tsx` | Herencia del starter kit; la ruta `/` redirige a `login`, así que no se renderiza. | — |

## Navegación lateral

`components/app-sidebar.tsx` define los grupos del menú con `href` en duro:

| Grupo | Entradas |
|---|---|
| Plataforma | Panel (`/dashboard`), solo con `auth.can.viewDashboard` |
| Solicitudes | Solicitudes de Pago, solo con `auth.can.viewPaymentRequests` |
| Presupuestos de Inversión | Solicitudes De Inversión, Hojas de Inversión, Control Presupuestal, **Estrategias** solo con `auth.can.viewStrategies`, y **Revisión de Pagos** solo si el usuario tiene rol `project_manager` o `super_admin` |
| Tesorería | Programación de Pagos |
| Documentación | Guía de Usuario |

El logo del menú lleva a Hojas de Inversión. El filtrado del menú es solo cosmético; la protección real está en rutas y controllers (ver [[Middleware y rutas protegidas]]).

## Componentes de negocio relevantes

| Componente | Uso |
|---|---|
| `data-table`, `sortable-header`, `column-filter-popover`, `pagination`, `numbered-pagination` | Tablas con filtros por columna y paginación. |
| `approval-timeline`, `status-badge`, `payment-request-detail`, `request-list-item` | Detalle y trazabilidad de aprobaciones. |
| `file-upload`, `document-preview` | Carga y vista previa de documentos (iframe/imagen con URL firmada). |
| `provider-autocomplete` | Autocompletado de proveedor/RFC. Ver [[Busqueda de proveedores]]. |
| `payment-policy-banner`, `week-navigator` | Ventanas de captura/envío y navegación por semana ISO. Ver [[Politica de ventanas de pago]]. |
| `project-combobox`, `project-closed-banner` | Selección de proyecto y aviso de proyecto cerrado. Ver [[Proyectos]]. |
| `notification-dropdown` | Campana de notificaciones in-app (prop compartida `notifications`). Ver [[Notificaciones in-app]]. |
| `app-sidebar-header` | Breadcrumbs + selector de moneda de visualización (`DisplayCurrencySelector`). |
| `two-factor-setup-modal`, `two-factor-recovery-codes` | Alta de 2FA. |
| `monthly-chart`, `stat-card` | Gráficas (Recharts) y tarjetas KPI. |

## Llamadas al backend: Wayfinder vs URLs en duro

- Wayfinder genera funciones tipadas en `@/routes/*` (por nombre de ruta) y `@/actions/*` (por controller). Unos 22 archivos de páginas/componentes/layouts las importan (auth, settings, sidebar, partes de los módulos).
- Otra parte del código usa **URLs literales**: `router.get('/investment-sheets', ...)`, `router.post('/investment-payment-requests', ...)`, `router.post('/investment-payment-review', ...)`, `fetch('/providers/search?...')`, `fetch('/investment-payment-requests/{id}')`.
- Formularios: `useForm` / `<Form>` de Inertia en auth y settings; en los módulos de negocio se arma `FormData` y se envía con `router.post` (necesario para archivos).

> [!warning] Riesgo de URLs en duro
> Si se renombra una ruta en `routes/web.php`, las llamadas con URL literal no fallan en compilación (las de Wayfinder sí). Al tocar rutas, buscar también el path literal en `resources/js`.

## Estilos y tema

- `resources/css/app.css`: Tailwind 4 configurado en CSS (`@import 'tailwindcss'`, `@custom-variant dark`), tipografía Geist / Geist Mono desde Google Fonts y paleta "slate" en OKLCH como variables CSS (claro y oscuro).
- Modo oscuro por clase `.dark`; preferencia guardada en cookie `appearance` y aplicada por `use-appearance`.
- El panel Filament usa la misma fuente Geist y colores Slate para coherencia visual (ver [[Panel Filament]]).

## Formato de números y fechas

- Fechas: `toLocaleDateString('es-MX', ...)` / `Intl.DateTimeFormat('es-MX')` en las páginas. Las horas de la política de ventanas usan `formatPolicyTime()` que muestra la hora oficial de México y, si el navegador está en otra zona, también la hora local. Ver [[Zona horaria y locale]].
- Montos: `useCurrencyFormatters()` / `useDisplayCurrency()` del contexto de moneda; algunas páginas usan `Intl.NumberFormat('es-MX', { currency: 'MXN' })` directo. Ver [[Multimoneda]].

## Relacionado
- [[Backend Laravel]] · [[Estructura de carpetas]] · [[Stack y versiones]] · [[Rutas]] · [[Multimoneda]] · [[Guia de usuario]]
