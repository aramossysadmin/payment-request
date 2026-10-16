---
tipo: decision
aliases:
  - ADR-004 Validación de archivos por contenido
tags:
  - payment-request/decisiones
  - payment-request/seguridad
fuentes:
  - app/Http/Requests/UploadInvestmentPaymentDocumentsRequest.php
  - app/Http/Requests/StoreInvestmentPaymentRequest.php
  - app/Http/Requests/UploadPaymentReceiptRequest.php
  - git 8a803b5
  - git ec42bc1
estado: vigente
actualizado: 2026-10-15
---

# ADR-004 Validación de archivos por contenido

Los archivos subidos se validan por su tipo MIME real (detectado del contenido) y, donde importa, también por extensión.

## Contexto
Validar solo la extensión permite subir cualquier archivo renombrado. Pero validar solo el contenido tuvo un efecto inesperado con los XML del SAT: los CFDI empiezan directamente con `<cfdi:Comprobante` sin declaración XML, `finfo` los detecta como `text/plain` y la regla `mimes:xml` los rechazaba (422) sin que el usuario viera el error.

## Decisión
- Todas las form requests de carga usan reglas `mimes:` (que Laravel resuelve con `finfo`, por contenido) y límite de 10 MB (`max:10240`).
- Para el XML del CFDI se usa `mimetypes:application/xml,text/xml,text/plain` y, en `withValidator()`, se exige además extensión `.xml` (y `.pdf` para el PDF; PDF/imagen para el documento único de otros tipos).
- El frontend muestra los errores de validación de cada archivo (antes se tragaban en silencio).

## Consecuencias
- Defensa en dos capas: contenido + extensión.
- Al agregar un tipo de documento nuevo hay que replicar ambas capas en `UploadInvestmentPaymentDocumentsRequest::withValidator()`.
- Aceptar `text/plain` en el campo XML es una concesión necesaria; la salvaguarda es la extensión.

## Fecha
2026-04-15 (validación MIME) · 2026-06-23 (ajuste para CFDI)

## Fuentes
- Commits `8a803b5` y `ec42bc1`.
- `app/Http/Requests/UploadInvestmentPaymentDocumentsRequest.php`

## Relacionado
- [[Indice de decisiones]] · [[Documentos de pago]] · [[Seguridad de la aplicacion]] · [[ADR-003 Documentos en disco privado con URL firmada]]
