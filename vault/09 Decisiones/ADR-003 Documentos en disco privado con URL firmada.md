---
tipo: decision
aliases:
  - ADR-003 Documentos en disco privado con URL firmada
tags:
  - payment-request/decisiones
  - payment-request/seguridad
fuentes:
  - config/filesystems.php
  - routes/web.php
  - git 8a803b5
  - git 3d39185
  - git f5e575c
estado: vigente
actualizado: 2026-10-15
---

# ADR-003 Documentos en disco privado con URL firmada

Facturas, XML, cotizaciones y comprobantes se guardan en el disco local privado y solo se sirven mediante rutas autenticadas o URLs firmadas temporales.

## Contexto
Inicialmente los documentos se guardaban en el disco público, accesibles por URL directa, y con el nombre de archivo que mandaba el cliente.

## Decisión
- Mover el almacenamiento de documentos del disco público al disco `local` privado (`storage/app/private`).
- Generar nombres de archivo UUID en lugar de usar el nombre del cliente (ej. `storeAs($directory, Str::uuid().'.pdf', 'local')` en `app/Http/Controllers/InvestmentPaymentDocumentController.php`).
- Servir los archivos por la ruta `documents.view`, que valida firma y bloquea path traversal; los enlaces se generan con `URL::temporarySignedRoute` (48 h), también en correos.

## Consecuencias
- Un enlace filtrado caduca; no hay acceso por adivinar rutas.
- Cualquier vista nueva que muestre documentos debe usar el patrón de URL firmada; usar `/storage/{path}` da 403 (fue el error corregido en `3d39185`).
- Rotar la `APP_KEY` invalida las URLs firmadas vigentes.

## Fecha
2026-04-15 (endurecimiento) · 2026-05-20 y 2026-06-01 (correcciones de vistas)

## Fuentes
- Commits `8a803b5`, `f5e575c`, `3d39185`.

## Relacionado
- [[Indice de decisiones]] · [[Seguridad de la aplicacion]] · [[Documentos de pago]] · [[ADR-004 Validacion de archivos por contenido]]
