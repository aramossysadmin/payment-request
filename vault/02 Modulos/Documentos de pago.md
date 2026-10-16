---
tipo: modulo
aliases:
  - Documentos de pago
  - Carga de documentos del pago
tags:
  - payment-request/modulos
  - payment-request/inversion
fuentes:
  - app/Http/Controllers/InvestmentPaymentDocumentController.php
  - app/Http/Requests/UploadInvestmentPaymentDocumentsRequest.php
  - app/Http/Controllers/DocumentViewController.php
  - resources/js/pages/investment-sheets/consolidated.tsx
actualizado: 2026-10-15
---

# Documentos de pago

Después de la [[Revision del PM|aprobación final del PM]], el solicitante sube los documentos definitivos del pago (factura PDF + XML o un comprobante único). Al subirlos el pago pasa a `completed` y empieza a contar como **pagado** en el presupuesto.

## Ruta y pantalla

- `POST investment-payment-requests/{uuid}/upload-documents` → `InvestmentPaymentDocumentController::upload()`.
- Diálogo "Subir documentos del pago" del [[Consolidado por proyecto]], disponible desde la tarjeta **Pagos Pendientes de Documentos** y desde el detalle del historial. Si el pago ya tiene documentos, se muestran como "Documentos actuales" con aviso de que cargar nuevos los reemplaza.

## Quién y cuándo

| Condición | Regla |
|---|---|
| Usuario | El solicitante del pago o `super_admin` (403: "Solo el solicitante puede subir documentos.") |
| Proyecto | 403 si está cerrado |
| Estado permitido (solicitante) | `final_approved` |
| Estado permitido (super_admin) | `final_approved`, `approved` (legacy), `completed` (para reemplazar) |
| Otro estado | 409 "Este pago no está en estado de carga de documentos." |

## Validación por tipo

`UploadInvestmentPaymentDocumentsRequest`:

| Tipo elegido | Archivos | Reglas |
|---|---|---|
| `factura` | `pdf` y `xml` obligatorios | PDF con mime pdf y extensión `.pdf`; XML con mimetype `application/xml`, `text/xml` o `text/plain` y extensión `.xml`; 10 MB c/u |
| `reembolso`, `estrategia`, `anticipo`, `cotizacion`, `pagare`, `domiciliado`, `factura_espana` | `document` obligatorio | PDF, JPG, JPEG o PNG; 10 MB |

> [!info] XML de CFDI como texto plano
> Se acepta `text/plain` porque los XML de CFDI suelen detectarse así por `finfo` (no traen declaración XML ni BOM). La extensión `.xml` se valida aparte.

## Cambio de tipo al subir documentos

El diálogo permite elegir de nuevo el **tipo de pago** (preseleccionado con el tipo actual) y el controlador lo guarda:

```php
$payment->update([
    'advance_documents' => $documents,
    'payment_type' => $paymentType,
    'status' => 'completed',
]);
```

Esto permite, por ejemplo, que un anticipo se cierre con factura. Si el pago era de **estrategia**, el desglose se conserva porque `hasStrategy()` se basa en el snapshot (`strategy_amount` + `strategy_factor_snapshot`), no en `payment_type` (ver [[Estrategias]]).

## Almacenamiento

- Disco `local`, directorio `investment-payment-documents/AAAA/MM/{folio}`, nombre de archivo UUID con su extensión.
- La columna `advance_documents` se **reemplaza** por el nuevo conjunto (PDF + XML, o un documento). Los archivos anteriores no se borran del disco.
- Los documentos se consultan con URLs firmadas temporales (48 h) a la ruta `documents.view` (`DocumentViewController`).

## Resultado

- Estado `completed` → el pago entra a `PAID_STATUSES` (ver [[Presupuesto por concepto]]).
- Aparece en la [[Programacion semanal]] y se le puede adjuntar el [[Comprobantes de pago|comprobante de pago]].
- Mensaje: "Documentos cargados exitosamente. El pago está completado."

> [!question] Por confirmar
> El estado `documents_pending` existe en `COMMITTED_STATUSES` y en los mapas de etiquetas ("Esperando docs"), pero ningún código actual lo asigna: hoy el pago espera documentos en `final_approved`.

## Relacionado
- [[Revision del PM]] · [[Programacion semanal]] · [[Comprobantes de pago]] · [[Flujo de pago de inversion]]
- [[Solicitudes de pago de inversion]] · [[Consolidado por proyecto]] · [[Estrategias]]
- [[InvestmentPaymentRequest]]
