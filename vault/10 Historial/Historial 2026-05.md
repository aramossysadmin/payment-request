---
tipo: historial
aliases:
  - Historial mayo 2026
tags:
  - payment-request/historial
fuentes:
  - git log
actualizado: 2026-10-15
---

# Historial mayo 2026

Importación masiva del ecosistema de inversión, ajustes de pantallas de Hojas de Inversión y, sobre todo, el nuevo **flujo de pagos de inversión por lotes semanales** en cinco fases. 56 commits.

## Catálogos e importación masiva
| Fecha | Commit | Cambio |
|---|---|---|
| 2026-05-07 | `aab5b78` | Categorías de gasto de inversión con varios departamentos |
| 2026-05-08 | `0680608` | Mismo nombre de categoría permitido en distintas super categorías |
| 2026-05-08 | `6b75d13` | Seeders y plantillas de importación masiva del ecosistema de inversión |
| 2026-05-08 | `60dfca5` | Importación histórica de pagos de inversión |
| 2026-05-14 | `5802386` | Etiquetas e íconos de catálogos de inversión |
| 2026-05-14 | `f81011a` | Relación correcta de concepto en el recurso de Solicitudes de Inversión |

## Hojas de Inversión: tablas, formularios y consolidado
| Fecha | Commit | Cambio |
|---|---|---|
| 2026-05-14 | `ddd3c52` | Columnas de la tabla de solicitudes: agregar, renombrar, reordenar |
| 2026-05-14 | `acb28de` | Fecha de creación con formato `Y-m-d H:i:s` |
| 2026-05-14 | `b9823e1` | Todas las columnas ocultables |
| 2026-05-15 | `49eb2c0` | Prefijo de moneda en lugar del nombre |
| 2026-05-15 | `71215af` | "Conceptos de Inversión" pasa a "Solicitudes de Inversión" |
| 2026-05-15 | `57522b0` | Ocultar tarjeta de proveedor en el alta |
| 2026-05-15 | `e5fcc61` | Datos Generales a todo el ancho |
| 2026-05-15 | `f17870c` | Ocultar "Aplica retención" en el alta |
| 2026-05-15 | `6a776b8` | Detalle: ocultar campos vacíos y renombrar Concepto de Gasto |
| 2026-05-15 | `8182a9c` | Detalle: ocultar Razón Social, RFC y Folio Factura |
| 2026-05-15 | `051ae99` | Ocultar campos de proveedor en la vista de inversión |
| 2026-05-15 | `46a98e0` | Paginación compacta |
| 2026-05-15 | `093daa9` | Columnas del consolidado renombradas y ocultas |
| 2026-05-15 | `1a82c58` | Búsqueda por nombre de concepto en tiempo real |
| 2026-05-15 | `ced0214` | Nombre del concepto en el encabezado del drawer |
| 2026-05-15 | `166db30` | Dashboard de inversión: KPIs más grandes y encabezados fijos |
| 2026-05-15 | `c0714d2` | Tabla Resumen por Concepto con cuadrícula |
| 2026-05-15 | `3ba45c4` | Encabezados sin salto de línea |
| 2026-05-15 | `59289ef` | Consolidado con cuadrícula, scroll y 50 por página |
| 2026-05-15 | `1ef33e1` | Sin borde en la columna de expandir |
| 2026-05-21 | `192ceb7` | Visibilidad de datos del departamento en Hojas de Inversión |

## Flujo de pagos de inversión por lotes (fases 1–5)
Ver [[ADR-006 Pagos de inversion por lotes semanales]] y [[Flujo de pago de inversion]].

| Fecha | Commit | Cambio |
|---|---|---|
| 2026-05-17 | `693a7d7` | Fase 1: borradores y lotes semanales |
| 2026-05-17 | `8ee1a3b` | Fase 2: primera aprobación del CEO con correo de lote y checkboxes |
| 2026-05-17 | `634e838` | Página de éxito tras aprobación del CEO |
| 2026-05-17 | `6290945` | Fase 3: portal de revisión del PM |
| 2026-05-17 | `6f56578` | Fase 4: aprobación final del CEO con token y montos ajustados (eliminada en julio) |
| 2026-05-17 | `0d268a4` | Fase 5: carga de documentos y cierre del flujo |
| 2026-05-17 | `f6fbc2e` | Subida de documentos con drag-and-drop |
| 2026-05-17 | `ba7bc8c` | Pagos del nuevo flujo integrados a la Programación semanal |
| 2026-05-17 | `11501d2` | Recursos Filament de solo lectura para auditoría de pagos |
| 2026-05-17 | `69c30d5` | Tarjeta "Historial de Pagos" en el consolidado |
| 2026-05-17 | `c59b33f` | Columna Descripción en tablas de pagos y solicitudes |
| 2026-05-17 | `9cf7973` | Columna Descripción en Pagos en Borrador |
| 2026-05-21 | `10a6c98` | Mejoras de UX en Pagos en Borrador |
| 2026-05-21 | `0f82bad` | Saldo correcto al editar un borrador |
| 2026-05-26 | `4b52ce7` | Filtro por Hoja de Inversión en Programación y Revisión de Pagos |

## Notificaciones del flujo de inversión
| Fecha | Commit | Cambio |
|---|---|---|
| 2026-05-22 | `5557844` | Fase 2 consolidada: un correo por solicitante y alerta al PM |
| 2026-05-28 | `63ebfc6` | Proyecto destacado, rol CEO y token de 96 h |
| 2026-05-28 | `25e1bc1` | Aprobación final del CEO consolidada en un correo y vista por proyecto |
| 2026-05-28 | `f94071d` | Nombre del proyecto en correos de resultado |
| 2026-05-28 | `a17a9f7` | Un correo por usuario en fases 3 y 4 |
| 2026-05-28 | `dfaf2e9` | Secciones por departamento + semana + año en correo y vista final |
| 2026-05-28 | `628985d` | Tasa de IVA 21% en el enum y en los selectores |

## Seguridad y repositorio
| Fecha | Commit | Cambio |
|---|---|---|
| 2026-05-13 | `b94a1f5` | Sincronizar `package-lock.json` |
| 2026-05-14 | `a884d00` | Archivo de pendientes agregado |
| 2026-05-14 | `bdbd1cd` | Revertido el commit anterior |
| 2026-05-14 | `a72fd62` | Archivo de pendientes en `.gitignore` |
| 2026-05-20 | `f5e575c` | Archivos de Solicitudes de Pago servidos por ruta autenticada |
| 2026-05-20 | `3a82d13` | Archivos locales de la herramienta de desarrollo fuera de git |
| 2026-05-22 | `ca0b968` | Carpeta de archivo local fuera de git |

## Relacionado
- [[Indice del historial]] · [[Historial 2026-04]] · [[Historial 2026-06]] · [[Lotes de pago]] · [[Aprobacion del CEO]] · [[Revision del PM]]
