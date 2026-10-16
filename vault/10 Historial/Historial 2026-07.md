---
tipo: historial
aliases:
  - Historial julio 2026
tags:
  - payment-request/historial
fuentes:
  - git log
actualizado: 2026-10-15
---

# Historial julio 2026

Comprobantes de pago y exportación de la programación semanal, eliminación de la aprobación final del CEO, candado de autorización del PM por proyecto y reportes ejecutivos. 13 commits.

## Programación semanal y comprobantes
| Fecha | Commit | Cambio |
|---|---|---|
| 2026-07-02 | `a08d8d8` | Semanas programadas en solo lectura, comprobante de pago (`receipt_attached`) y botón "Guardar Programación" deshabilitado ([[ADR-015 Boton Guardar Programacion deshabilitado]]) |
| 2026-07-02 | `7f4caec` | Exportar la programación a Excel (una semana o todas) |

## Pagos de inversión
| Fecha | Commit | Cambio |
|---|---|---|
| 2026-07-02 | `5bc519a` | Tasa de IVA 10% en todos los módulos |
| 2026-07-09 | `c87d4d9` | Tipo de pago Factura España (un PDF o imagen) |
| 2026-07-14 | `84a109b` | Se elimina la aprobación final del CEO; el PM cierra ([[ADR-016 El PM da la aprobacion final]]) |
| 2026-07-20 | `b7ea864` | Falso rechazo por comparar `float` al cubrir el saldo exacto ([[ADR-017 Comparacion de montos en centavos]]) |

## Solicitudes de Inversión y catálogos
| Fecha | Commit | Cambio |
|---|---|---|
| 2026-07-09 | `89e2e31` | Sociedades con RFC y Constancia de Situación Fiscal, visibles en Hojas de Inversión |
| 2026-07-20 | `eabe2ca` | Sucursales con Centro de Costos (CeCo) |
| 2026-07-20 | `e8de649` | Razón Social y CeCo en el consolidado y en el encabezado del detalle |
| 2026-07-21 | `d09e39b` | Candado por proyecto para requerir autorización del PM y estado Rechazado ([[ADR-018 Candado de autorizacion del PM por proyecto]]) |

## Reportes
| Fecha | Commit | Cambio |
|---|---|---|
| 2026-07-21 | `6b50bd2` | Reporte PDF ejecutivo por proyecto; corrección del conteo de conceptos |
| 2026-07-24 | `146efd2` | Descarga Excel de reportes consolidados y PDFs en la moneda seleccionada |

## Repositorio
| Fecha | Commit | Cambio |
|---|---|---|
| 2026-07-21 | `83ba444` | Dejar de versionar `tests/` por completo ([[ADR-002 Tests fuera de git]]) |

## Relacionado
- [[Indice del historial]] · [[Historial 2026-06]] · [[Historial 2026-08]] · [[Programacion semanal]] · [[Comprobantes de pago]] · [[Revision del PM]]
