---
tipo: historial
aliases:
  - Historial junio 2026
tags:
  - payment-request/historial
fuentes:
  - git log
actualizado: 2026-10-15
---

# Historial junio 2026

Mes de mayor actividad: multimoneda, política de ventanas de pago, usuarios multi-departamento, credenciales SAP, separación Comprometido vs Pagado y nuevo tema visual. En este mes también se migró producción a Docker + PostgreSQL (sin commit asociado; ver [[ADR-013 Migracion a Docker PostgreSQL y cron]]). 67 commits.

## Presupuesto, consolidado y dashboard
| Fecha | Commit | Cambio |
|---|---|---|
| 2026-06-03 | `259cbd8` | Presupuesto y consumo por concepto segmentados por departamento |
| 2026-06-03 | `78e3b09` | Pagado y pendiente por departamento con barra de progreso |
| 2026-06-03 | `0c565dc` | Proyectos nuevos visibles en consolidado y dashboard |
| 2026-06-04 | `2fd0da1` | Dashboard de proyecto en el consolidado |
| 2026-06-04 | `c49a36d` | Dashboard de proyecto a dos columnas |
| 2026-06-04 | `2a5ab1a` | Deductivas en proceso en la sección de Presupuesto |
| 2026-06-04 | `dc73c31` | Corrección del cálculo de aditivas/deductivas pendientes |
| 2026-06-24 | `65ab625` | Rediseño del encabezado del consolidado (calendario, fechas, presupuesto) |
| 2026-06-24 | `32dec2d` | "Pagado" del dashboard contaba solo estados legacy |
| 2026-06-24 | `cd5ff0b` | Ocultar fila redundante de tarjetas resumen |
| 2026-06-24 | `a742a9b` | "Saldo disponible" unificado (moneda, filtro y alcance) |
| 2026-06-24 | `b893938` | "Pagos registrados" en MXN con estatus real |
| 2026-06-24 | `e27c7d6` | Comprometido vs Pagado en todo el módulo ([[ADR-014 Comprometido vs Pagado]]) |
| 2026-06-25 | `f30b444` | Registro de la decisión Comprometido vs Pagado en la memoria del proyecto |

## Multimoneda
Ver [[ADR-008 Montos normalizados a MXN]].

| Fecha | Commit | Cambio |
|---|---|---|
| 2026-06-08 | `ca3fb70` | Columna Tipo de cambio en monedas |
| 2026-06-08 | `faa460f` | Merge de la columna Tipo de cambio |
| 2026-06-08 | `388d389` | Moneda por defecto en Proyectos |
| 2026-06-08 | `bebcc75` | Selector de moneda de visualización en el consolidado |
| 2026-06-08 | `467fe15` | Columna ambigua `id` en `sumTotalMxn` |
| 2026-06-08 | `c94b6b5` | Función de formato nativo faltante en drawer y modal |

## Solicitudes de Inversión
| Fecha | Commit | Cambio |
|---|---|---|
| 2026-06-01 | `c0aea5c` | Bypass temporal de autorización ([[ADR-007 Bypass global de autorizacion de solicitudes]]) |
| 2026-06-01 | `d8fe5ac` | Texto más discreto para el comentario de aprobación automática |
| 2026-06-01 | `f6123f8` | Descripción en mayúsculas ([[ADR-023 Normalizacion de textos en modelos]]) |
| 2026-06-03 | `86beef5` | Edición en línea de la descripción |
| 2026-06-08 | `12aaff6` | Se quita la tarjeta de Documentos Adjuntos (cotización) |
| 2026-06-10 | `aca3ccd` | Se restaura Documentos Adjuntos con nuevo nombre |
| 2026-06-10 | `cd0a234` | Editor de Concepto/Descripción para roles privilegiados |
| 2026-06-11 | `9dec566` | El editor ahora sí guarda el concepto (mass assignment) |
| 2026-06-24 | `e0fff36` | Alta: Proyecto y Sucursal en dos columnas |
| 2026-06-24 | `a67a887` | Alta: aviso para adjuntar cotización |
| 2026-06-24 | `772fb55` | Alta: aviso de cotización en color primario |
| 2026-06-24 | `de4c427` | Alta: conceptos en orden alfabético |
| 2026-06-24 | `332d69a` | Alta: encabezado y secciones con ícono y subtítulo |

## Pagos de inversión: documentos, historial y revisión
| Fecha | Commit | Cambio |
|---|---|---|
| 2026-06-01 | `97f382b` | Carga de documentos en Pagos Pendientes sin filtro de semana |
| 2026-06-01 | `3d39185` | PDF/XML del historial por URL firmada ([[ADR-003 Documentos en disco privado con URL firmada]]) |
| 2026-06-08 | `7f16163` | Navegador de semanas en Historial de Pagos |
| 2026-06-08 | `13bad48` | Super admin: documentos siempre habilitados en pagos históricos |
| 2026-06-08 | `741bcac` | Tipos de pago de inversión, vista previa de documentos y mejoras al consolidado y la programación |
| 2026-06-10 | `3002b39` | Historial: selector de departamento, descarga PDF y "todas las semanas" |
| 2026-06-12 | `66da7ff` | Historial: columnas nuevas, filtros por columna, orden y búsqueda |
| 2026-06-12 | `36afed8` | Historial: filtros de descripción, departamento y fechas |
| 2026-06-12 | `cf6d566` | PDF del historial en tamaño oficio horizontal |
| 2026-06-12 | `3fd7213` | Pagos Pendientes de Documentos: paginado, orden y filtros |
| 2026-06-12 | `560eb23` | Paginación numerada con elipsis |
| 2026-06-23 | `ec42bc1` | Carga de CFDI PDF+XML que fallaba en silencio ([[ADR-004 Validacion de archivos por contenido]]) |
| 2026-06-24 | `6245b67` | Revisión de Pagos: modal de detalle con semana y saldo |
| 2026-06-24 | `d9ac59e` | Aviso de que Comprometido incluye el pago actual |
| 2026-06-24 | `23d6290` | Ese aviso se cierra por sesión |
| 2026-06-24 | `616fc42` | Historial: reorganización y edición de fecha (super admin) |
| 2026-06-24 | `7e9bf84` | Historial: departamento sin prefijo y acción solo ícono |
| 2026-06-30 | `32567b8` | Revisión de Pagos: documentos adjuntos en el modal |
| 2026-06-30 | `efc7c6c` | Tipos de pago Cotización, Pagaré y Domiciliado |
| 2026-06-30 | `5354f5f` | Tasa de IVA 4% en todos los módulos |

## Notificaciones
| Fecha | Commit | Cambio |
|---|---|---|
| 2026-06-10 | `79b6300` | Correo al PM con resumen y PDF adjunto tras la aprobación final |
| 2026-06-22 | `6b751dc` | Correo de resultado del lote muestra el monto aprobado |

## Política de ventanas de pago
Ver [[ADR-009 Politica de ventanas como pagina singleton]].

| Fecha | Commit | Cambio |
|---|---|---|
| 2026-06-11 | `ef876ad` | Fase 1: backend, datos, Filament y prórroga (snooze) |
| 2026-06-11 | `9de05cf` | Página de Filament en lugar de Resource para el singleton |
| 2026-06-12 | `1f44f53` | Fase 2: botones, banner global y contador |
| 2026-06-12 | `200f018` | Zona horaria adaptativa según el navegador |
| 2026-06-12 | `b9456f7` | Fase 3: cancelación automática, notificaciones y recordatorio |
| 2026-06-12 | `cef7e4d` | Banner siempre visible |

## Usuarios, sucursales y SAP
| Fecha | Commit | Cambio |
|---|---|---|
| 2026-06-22 | `e502cd2` | Usuarios multi-departamento ([[ADR-010 Usuarios con multiples departamentos]], [[ADR-011 Lote hereda el departamento de la solicitud]]) |
| 2026-06-22 | `046baf1` | Credenciales SAP B1 por sucursal y toggle de activa ([[ADR-012 Credenciales SAP cifradas por sucursal]]) |
| 2026-06-22 | `a30f38e` | Conceptos de inversión con `sap_item_code` |
| 2026-06-22 | `496410a` | Campos SAP opcionales en Filament |

## Interfaz general
| Fecha | Commit | Cambio |
|---|---|---|
| 2026-06-24 | `e72966d` | Tema visual slate + tipografía Geist, tomado de otro portal interno |
| 2026-06-24 | `b7f3f24` | Ajustes de revisión del PR #1 (accesibilidad, memo, locale) |

## Relacionado
- [[Indice del historial]] · [[Historial 2026-05]] · [[Historial 2026-07]] · [[Multimoneda]] · [[Politica de ventanas de pago]] · [[Presupuesto por concepto]]
