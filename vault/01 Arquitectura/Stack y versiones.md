---
tipo: arquitectura
aliases:
  - Stack y versiones
tags:
  - payment-request/arquitectura
fuentes:
  - composer.json
  - composer.lock
  - package.json
  - package-lock.json
  - vite.config.ts
  - components.json
  - config/inertia.php
actualizado: 2026-10-15
---

# Stack y versiones

Inventario de lenguajes, frameworks y paquetes que usa el portal, con la versión **instalada** (tomada de `composer.lock` y `node_modules/*/package.json`), no solo la restricción declarada.

> [!info] Origen del proyecto
> `composer.json` conserva el nombre `laravel/react-starter-kit`: el portal nació del starter kit oficial de Laravel con React + Inertia + Fortify y se le agregaron Filament, Shield, Spatie y el resto de paquetes.

## Backend (PHP)

| Paquete | Restricción en `composer.json` | Versión instalada | Uso en el proyecto |
|---|---|---|---|
| PHP | `^8.2` | 8.4.x (Herd local) | Lenguaje. Se usan enums, constructor property promotion, readonly. |
| `laravel/framework` | `^12.0` | 12.54.1 | Framework base. Estructura "slim" de Laravel 11+ (ver [[Backend Laravel]]). |
| `inertiajs/inertia-laravel` | `^2.0` | 2.0.22 | Puente servidor → React. SSR deshabilitado (`config/inertia.php` → `ssr.enabled = false`). |
| `laravel/fortify` | `^1.30` | 1.36.1 | Autenticación headless + 2FA (ver [[Autenticacion y 2FA]]). |
| `laravel/wayfinder` | `^0.1.9` | 0.1.14 | Genera funciones TypeScript para rutas y controllers. |
| `filament/filament` | `3.3` (fija) | 3.3.0 | Panel de administración `/admin` (ver [[Panel Filament]]). Trae `livewire/livewire` 3.7.11. |
| `bezhansalleh/filament-shield` | `*` | 3.9.10 | Roles/permisos en Filament sobre `spatie/laravel-permission` 6.24.1 (ver [[Permisos Shield]]). |
| `z3d0x/filament-logger` | `*` | 0.8.0 | Bitácora de accesos/recursos/modelos sobre `spatie/laravel-activitylog` 4.12.3 (ver [[Auditoria y activity log]]). |
| `spatie/laravel-model-states` | `*` | 2.12.1 | Máquinas de estado de `PaymentRequest` e `InvestmentRequest` (ver [[Estados]]). |
| `barryvdh/laravel-dompdf` | `*` | 3.1.2 (dompdf 3.1.5) | Generación de PDFs (ver [[PDFs]]). |
| `maatwebsite/excel` | `*` | 3.1.69 (PhpSpreadsheet 1.30.5) | Exportaciones Excel (ver [[Exportaciones Excel]]). |
| `laravel/tinker` | `^2.10.1` | 2.11.1 | Consola interactiva (consultas y correcciones controladas). |
| `pragmarx/google2fa` / `bacon/bacon-qr-code` | (dependencias de Fortify) | 9.0.0 / 3.0.3 | TOTP y QR para 2FA. |
| `nesbot/carbon` | (dependencia de Laravel) | 3.11.3 | Fechas; `CarbonImmutable` por defecto (ver [[Zona horaria y locale]]). |

### Dependencias de desarrollo

| Paquete | Versión instalada | Uso |
|---|---|---|
| `pestphp/pest` (+ `pest-plugin-laravel`) | 4.4.2 | Tests (ver [[Tests]]). |
| `laravel/pint` | 1.29.0 | Formateo PHP (`composer lint`). |
| `laravel/boost` | 2.3.1 | Servidor MCP para asistentes de IA (`.mcp.json`, `.cursor/`). |
| `laravel/pail` | 1.2.6 | Tail de logs en `composer dev:ssr`. |
| `laravel/sail` | 1.53.0 | Instalado pero no se usa (el entorno local es Herd, ver [[Desarrollo local]]). |
| `fakerphp/faker`, `mockery/mockery`, `nunomaduro/collision` | — | Soporte de tests. |

> [!warning] Restricciones `*`
> Varios paquetes críticos (`filament-shield`, `filament-logger`, `laravel-model-states`, `laravel-dompdf`, `maatwebsite/excel`) están declarados con `*`. Un `composer update` sin acotar puede saltar de versión mayor. La versión real queda fijada solo por `composer.lock`.

## Frontend (Node)

| Paquete | Versión instalada | Uso |
|---|---|---|
| `react` / `react-dom` | 19.2.4 | UI. Compilado con `babel-plugin-react-compiler` 1.0.0 (React Compiler activo en `vite.config.ts`). |
| `@inertiajs/react` | 2.3.14 | Cliente Inertia v2 (se usan `Deferred`, `Link prefetch`, `useForm`). |
| `typescript` | 5.9.3 | Tipado (`npm run types` → `tsc --noEmit`). |
| `vite` | 7.3.1 | Bundler; plugins `laravel-vite-plugin` 2.1.0, `@vitejs/plugin-react` 5.1.4, `@tailwindcss/vite`. |
| `tailwindcss` | 4.2.1 | Estilos (configuración CSS-first en `resources/css/app.css`, sin `tailwind.config.js`). |
| `@laravel/vite-plugin-wayfinder` | 0.1.7 | Regenera `resources/js/actions`, `resources/js/routes`, `resources/js/wayfinder` al compilar (`formVariants: true`). |
| `radix-ui` + `@radix-ui/react-*` | 1.4.3 | Primitivas de los componentes shadcn/ui (estilo `new-york`, ver `components.json`). |
| `@headlessui/react` | ^2.2.0 | Componentes accesibles adicionales. |
| `lucide-react` | 0.475.0 | Iconos. |
| `recharts` | 3.8.0 | Gráficas (dashboard, control presupuestal). |
| `cmdk`, `input-otp`, `class-variance-authority`, `clsx`, `tailwind-merge`, `tw-animate-css` | — | Utilidades de UI (comboboxes, input de código 2FA, variantes de clases). |
| `eslint` 9, `prettier` 3 (+ `prettier-plugin-tailwindcss`) | — | Lint y formato (`npm run lint`, `npm run format`). |

## Infraestructura de ejecución

| Pieza | Local | Producción |
|---|---|---|
| Servidor web | Laravel Herd (`https://payment-request.test`) | Ver [[Produccion]] |
| Base de datos | SQLite (`database/database.sqlite`, default de `config/database.php`) | PostgreSQL (ver [[Produccion]]) |
| Tests | SQLite en memoria (`.env.testing`, ver [[Proteccion de la base de datos]]) | — |
| Colas, caché, sesión | Driver `database` (defaults de `config/queue.php`, `config/cache.php`, `config/session.php`) | Ver [[Colas y correo]] |
| Correo | SMTP a Mailtrap sandbox | Resend (ver [[Colas y correo]]) |

## Scripts útiles

| Comando | Qué hace |
|---|---|
| `composer dev` | Levanta en paralelo `php artisan serve`, `php artisan queue:listen --tries=1` y `npm run dev` (con Herd normalmente solo se necesitan la cola y Vite). |
| `composer test` | `config:clear` → Pint en modo test → `php artisan test`. |
| `composer lint` | `pint --parallel`. |
| `npm run build` | Compila assets a `public/build` (y regenera Wayfinder). |
| `npm run types` | Verificación de tipos TypeScript. |

> [!note] Hook `post-autoload-dump`
> Ejecuta `php artisan filament:upgrade`, que republica los assets de Filament en `public/css/filament` y `public/js/filament` (estos sí se versionan).

## CI

`.github/workflows/lint.yml` (Pint + Prettier + ESLint con PHP 8.4) y `.github/workflows/tests.yml` corren en push/PR a `main`, `develop`, `master`, `workos`.

> [!question] Por confirmar
> La carpeta `tests/` y `phpunit.xml` están en `.gitignore`, por lo que el workflow `tests.yml` en GitHub no tiene tests que correr. Confirmar si el workflow sigue siendo útil o si conviene desactivarlo.

## Relacionado
- [[Estructura de carpetas]] · [[Backend Laravel]] · [[Frontend Inertia React]] · [[Panel Filament]] · [[Desarrollo local]] · [[Tests]]
