---
tipo: historial
aliases:
  - Historial abril 2026
tags:
  - payment-request/historial
fuentes:
  - git log
actualizado: 2026-10-15
---

# Historial abril 2026

Nace el módulo de inversión: Solicitudes de Inversión (Hojas de Inversión), consolidado por proyecto, pagos de inversión, dashboard de presupuesto y programación semanal. También el endurecimiento de seguridad y las reglas del proyecto. 69 commits.

## Flujo legacy y correos
| Fecha | Commit | Cambio |
|---|---|---|
| 2026-04-09 | `20150aa` | Filtrar documentos de anticipo vacíos al guardar y mostrar |
| 2026-04-09 | `447b00b` | Merge de `main` remoto |
| 2026-04-10 | `8addb9a` | Autorizadores de departamento en dos niveles |
| 2026-04-10 | `3ed9942` | Categoría de tipo de pago y modos de documentos configurables |
| 2026-04-10 | `cbab081` | Correos con etapa, departamento y enlaces firmados a documentos |
| 2026-04-10 | `5efe7da` | Conservar nombres originales de archivo y documentos en la vista de aprobación |
| 2026-04-10 | `d386f18` | Descarga en PDF de solicitudes completadas (pago e inversión) |
| 2026-04-13 | `774bd0d` | Rediseño de correos por secciones |
| 2026-04-13 | `04d132c` | Descripción en correos y enlaces de aprobación para inversión |
| 2026-04-24 | `4e14235` | Etiqueta de campo más clara en solicitudes de pago |
| 2026-04-27 | `5ebd10f` | Campo Sucursal de solo lectura en el modal de solicitud |

## Solicitudes de Inversión (Hojas de Inversión)
| Fecha | Commit | Cambio |
|---|---|---|
| 2026-04-10 | `a196912` | Recurso de Solicitudes de Inversión (clon independiente de Solicitudes de Pago) |
| 2026-04-13 | `ca6006b` | Aprobación simplificada a un solo autorizador configurable |
| 2026-04-15 | `6bd2ebe` | `investment-requests` pasa a `investment-sheets`; recursos de catálogo |
| 2026-04-15 | `24ade5b` | Categorías de gasto de inversión asociadas a departamento |
| 2026-04-15 | `b7e833c` | Encabezados "Conceptos / Hojas de Inversión", combobox y filtro por departamento |
| 2026-04-15 | `1ad5f86` | Concepto de gasto de inversión, campos de contacto y notificaciones |
| 2026-04-15 | `c8a79bb` | Corrección de validación de retención al editar |
| 2026-04-15 | `4a68e48` | Nueva página de detalle de la hoja de inversión |
| 2026-04-15 | `96d78cb` | Restaurar Concepto de Gasto y Tipo de Pago en el detalle |
| 2026-04-15 | `bdd3c30` | Ocultar Tipo de Pago en el detalle (temporal) |
| 2026-04-15 | `d866078` | Detalle en el índice: mostrar Concepto, ocultar Tipo de Pago |
| 2026-04-15 | `68948dc` | Aditivas de presupuesto por concepto |
| 2026-04-15 | `8210961` | `expense_concept_id` opcional en `investment_requests` |
| 2026-04-15 | `b5b9bc3` | Reparar llaves foráneas tras recrear tabla en SQLite |
| 2026-04-15 | `4aa4179` | Concepto de Gasto en correo y página de aprobación |
| 2026-04-16 | `db1c1d9` | Proyecto, concepto y descripción en tarjetas; detalle reorganizado |
| 2026-04-16 | `fc6bfc3` | Moneda en la sección de importes y proyecto en el panel de detalle |
| 2026-04-16 | `f97ea6c` | Orden de RFC y Razón Social en el formulario |
| 2026-04-16 | `619c709` | Etiqueta de folio de factura incluye referencia de cotización |
| 2026-04-27 | `3a73f6a` | Cambio del correo del autorizador de inversiones por defecto |
| 2026-04-28 | `4e0a2f1` | Proveedor opcional en Solicitudes de Inversión |
| 2026-04-28 | `c5aba54` | Campo Super Categoría en categorías de gasto de inversión |
| 2026-04-28 | `f9f6d58` | Super Categoría con Select y alta en línea |
| 2026-04-28 | `735e3c0` | Super Categoría con opciones fijas |

## Consolidado por proyecto y dashboard
| Fecha | Commit | Cambio |
|---|---|---|
| 2026-04-15 | `bbd1864` | Vista consolidada de hojas de inversión por proyecto |
| 2026-04-15 | `9b4f03f` | Drawer lateral de pagos por concepto |
| 2026-04-15 | `9ad1521` | Corrección: drawer usa id numérico en la ruta |
| 2026-04-15 | `25ea0c3` | Tabla agrupada colapsable de conceptos |
| 2026-04-15 | `d8094c2` | Resumen del drawer con totales agrupados |
| 2026-04-15 | `2c09311` | Badge "Inicial" para el concepto base |
| 2026-04-15 | `a7a37ce` | Dashboard de control de presupuesto de inversión |
| 2026-04-15 | `cec5310` | Widget inicial vs aditiva |
| 2026-04-15 | `36f60d1` | Comparativo global con filtro por departamento |
| 2026-04-15 | `55c6876` | Orden de widgets y filtro móvil |
| 2026-04-15 | `47fa473` | Cuatro KPIs unificados en un widget |

## Pagos de inversión
| Fecha | Commit | Cambio |
|---|---|---|
| 2026-04-15 | `ed73ec7` | Solicitudes de pago de inversión con modal y aprobación por correo |
| 2026-04-15 | `77ee2dc` | Modal simplificado: factura/anticipo, proveedor precargado |
| 2026-04-15 | `6262a64` | Columna Concepto y etiqueta "Gasto de Inversión" en el modal |
| 2026-04-15 | `03f8d4f` | Mejor aviso de saldo excedido |
| 2026-04-16 | `0502e53` | Fecha de provisión de pago con número de semana automático |
| 2026-04-16 | `ee3d6d8` | Texto de ayuda para notación de porcentaje de anticipo |
| 2026-04-28 | `b0e8540` | Fecha de provisión renombrada y sin restricción de fecha |

## Programación semanal
| Fecha | Commit | Cambio |
|---|---|---|
| 2026-04-16 | `616edbe` | Programación semanal de pagos con aprobación por lote |
| 2026-04-16 | `7facb26` | Detalle de pagos en el correo de aprobación |
| 2026-04-16 | `e5d148a` | Página de aprobación dedicada |
| 2026-04-16 | `f01cbaa` | Diseño de tarjetas en la página de aprobación |
| 2026-04-16 | `26c8c78` | Correo solo con pagos incluidos y su concepto |
| 2026-04-16 | `f1d66f8` | Página de aprobación solo con pagos incluidos |

## Seguridad, permisos y guía
| Fecha | Commit | Cambio |
|---|---|---|
| 2026-04-13 | `044aedf` | Página de Guía de Usuario con pestañas |
| 2026-04-13 | `f93faee` | Menú lateral agrupado por secciones |
| 2026-04-15 | `8a803b5` | Endurecimiento: disco privado, MIME, mass assignment, policies, headers, rate limit ([[ADR-003 Documentos en disco privado con URL firmada]], [[ADR-004 Validacion de archivos por contenido]]) |
| 2026-04-23 | `7578ee6` | Guía de usuario con 4 pestañas (incluye Tesorería) |
| 2026-04-23 | `b234cd2` | Policies de Proyectos y Gastos de Inversión |

## Repositorio y reglas del proyecto
| Fecha | Commit | Cambio |
|---|---|---|
| 2026-04-14 | `32def6b` | Memoria persistente del proyecto e instrucciones de `CLAUDE.md` |
| 2026-04-15 | `8b3ba00` | Reglas de trabajo: commits obligatorios, plan previo, preguntar dudas |
| 2026-04-15 | `0770e62` | Secciones de `CLAUDE.md` reescritas en inglés (incluye protección de BD, [[ADR-005 Nunca borrar registros solo borrado logico]]) |
| 2026-04-24 | `4072c3a` | Sincronizar `package-lock.json` |
| 2026-04-24 | `7607b53` | Reglas de autenticación git con token del proyecto |

## Relacionado
- [[Indice del historial]] · [[Historial 2026-03]] · [[Historial 2026-05]] · [[Solicitudes de inversion]] · [[Consolidado por proyecto]] · [[Programacion semanal]]
