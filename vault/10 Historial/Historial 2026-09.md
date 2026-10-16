---
tipo: historial
aliases:
  - Historial septiembre 2026
tags:
  - payment-request/historial
fuentes:
  - git log
actualizado: 2026-10-15
---

# Historial septiembre 2026

Control de proyectos (cierre operativo y acceso por usuario) y catálogo de Estrategias. 3 commits.

## Proyectos y acceso
| Fecha | Commit | Cambio |
|---|---|---|
| 2026-09-10 | `c361b5e` | Estado ABIERTO/CERRADO con bloqueo de captura y UI de solo lectura ([[ADR-019 Cierre operativo de proyectos]]) |
| 2026-09-10 | `f959f5e` | Acceso por proyecto con backfill automático y guards por URL directa ([[ADR-020 Acceso por proyecto con backfill]]) |

## Pagos de inversión
| Fecha | Commit | Cambio |
|---|---|---|
| 2026-09-23 | `a7d521e` | Catálogo de Estrategias con cálculo de monto y visibilidad en correos, PDFs y reportes ([[ADR-021 Snapshot inmutable de estrategia]]) |

## Relacionado
- [[Indice del historial]] · [[Historial 2026-08]] · [[Proyectos]] · [[Acceso por proyecto]] · [[Estrategias]]
