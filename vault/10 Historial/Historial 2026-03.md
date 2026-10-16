---
tipo: historial
aliases:
  - Historial marzo 2026
tags:
  - payment-request/historial
fuentes:
  - git log
actualizado: 2026-10-15
---

# Historial marzo 2026

Maduración del módulo legacy de Solicitudes de Pago: aprobación por correo con token, catálogos con importación CSV y normalización de textos, tipos de pago en BD, roles y permisos. 32 commits.

## Solicitudes de pago (legacy)
| Fecha | Commit | Cambio |
|---|---|---|
| 2026-03-03 | `da11a88` | Autorizadores de otros departamentos ven las solicitudes asignadas |
| 2026-03-03 | `aa2e288` | Folios SAP en las etapas de Administración y Tesorería |
| 2026-03-04 | `1929482` | Botones de acción en el encabezado de la tarjeta de resumen |
| 2026-03-04 | `16770c5` | Retención pasa a booleano; IVA y total se calculan solos |
| 2026-03-04 | `50d2110` | Soporte UUID, pestaña "pendientes" por defecto y nuevo layout de detalle |
| 2026-03-09 | `4ad0603` | Booleanos en FormData se envían como `1`/`0` |
| 2026-03-12 | `a28e261` | Campo RFC, tasas de IVA configurables, tipo de pago Inversión y dashboard |
| 2026-03-14 | `f3c6074` | Subtotal y total sin símbolo de moneda |
| 2026-03-14 | `439950e` | RFC agregado a la respuesta del recurso de solicitudes |

## Aprobación por correo y plantillas
| Fecha | Commit | Cambio |
|---|---|---|
| 2026-03-14 | `3878698` | Aprobación desde el correo con token y autocompletado de proveedores ([[ADR-022 Aprobacion por correo con token]]) |
| 2026-03-14 | `990dbfc` | Plantillas de correo con marca corporativa y traducción al español |
| 2026-03-19 | `ef6f575` | Restablecimiento de contraseña con plantilla corporativa |

## Panel Filament, autenticación y permisos
| Fecha | Commit | Cambio |
|---|---|---|
| 2026-03-13 | `88c51b4` | Panel Filament con policies, branding y páginas de autenticación |
| 2026-03-14 | `d6288a7` | Tests de autenticación alineados con la configuración de Fortify |
| 2026-03-15 | `cbbd5a9` | Combobox de sucursales, normalización de roles y protecciones de super_admin |
| 2026-03-16 | `5be3049` | ToggleColumn, orden por defecto, tema claro por defecto |
| 2026-03-17 | `8285358` | Filtro de papelera al final y conversión de codificación CSV |
| 2026-03-20 | `7c061bf` | Seeders de catálogos, recurso de roles y reestructura de permisos |

## Catálogos y normalización de textos
| Fecha | Commit | Cambio |
|---|---|---|
| 2026-03-14 | `dd384df` | Conceptos de gasto: importación CSV, estado activo/inactivo y normalización |
| 2026-03-14 | `f2ae060` | Acción de importación CSV propia en lugar de la de Filament |
| 2026-03-14 | `4bc19e0` | Encabezados de tabla sin mayúsculas forzadas; prefijo de moneda ordenable |
| 2026-03-14 | `68a9062` | Normalización a mayúsculas: sociedades |
| 2026-03-14 | `3277bb0` | Normalización a mayúsculas: sucursales |
| 2026-03-14 | `13527ed` | Normalización a mayúsculas: monedas (nombre y prefijo) |
| 2026-03-14 | `2c20f89` | Normalización: departamentos |
| 2026-03-14 | `91198fa` | Normalización: posiciones |
| 2026-03-14 | `9775e7b` | Normalización: usuarios (nombre Title Case, correo en minúsculas) |
| 2026-03-14 | `26add19` | Normalización: solicitudes de pago (proveedor, RFC, folio, descripción) |
| 2026-03-14 | `2bcb686` | Normalización: comentarios de aprobación |
| 2026-03-15 | `b6f0a27` | Tipo de pago: de enum a catálogo en BD ([[ADR-001 Catalogo de tipos de pago en base de datos]]) |

Ver [[ADR-023 Normalizacion de textos en modelos]].

## Repositorio y herramientas
| Fecha | Commit | Cambio |
|---|---|---|
| 2026-03-16 | `ce9a83f` | Permisos de herramientas MCP de Boost en la configuración local |
| 2026-03-20 | `aa603f9` | `tests/`, `phpunit.xml` y `.env.testing` fuera del repositorio ([[ADR-002 Tests fuera de git]]) |

## Relacionado
- [[Indice del historial]] · [[Historial 2026-02]] · [[Historial 2026-04]] · [[Modulo legacy Payment Requests]] · [[Catalogos]]
