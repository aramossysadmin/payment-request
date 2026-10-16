---
tipo: modulo
aliases:
  - Comprobantes de pago
  - Comprobante adjunto
tags:
  - payment-request/modulos
  - payment-request/inversion
fuentes:
  - app/Http/Controllers/WeeklyPaymentReceiptController.php
  - app/Http/Requests/UploadPaymentReceiptRequest.php
  - app/Models/InvestmentPaymentRequest.php
  - resources/js/pages/weekly-payment-schedule/index.tsx
  - resources/js/pages/investment-sheets/consolidated.tsx
actualizado: 2026-10-15
---

# Comprobantes de pago

Último paso del flujo de pago de inversión: se adjunta el comprobante bancario del pago y el pago queda en `receipt_attached` ("Comprobante adjunto"), que marca el **fin del proceso**.

## Ruta y pantalla

- `POST weekly-payment-schedule/{uuid}/receipt` → `WeeklyPaymentReceiptController::store()`.
- Botón **Comprobante** / **Reemplazar** en la columna "Comprobante de pago" de la [[Programacion semanal]], tanto en la vista de pagos pendientes de programar como en semanas ya programadas.
- En el [[Consolidado por proyecto]] el historial muestra los comprobantes (sección "Comprobante de Pago" del detalle) con URL firmada de 48 h.

## Reglas

| Regla | Detalle |
|---|---|
| Acceso | El proyecto del pago debe ser visible para el usuario (`InvestmentRequest::visibleTo()`); si no, 403 "No tienes acceso al proyecto de este pago." |
| Proyecto cerrado | 403 |
| Estados permitidos | `approved` (legacy), `completed`, `scheduled_for_bank`, `receipt_attached` (para reemplazar); otro estado → 409 |
| Archivos | `receipt_documents`: 1 a 5 archivos, PDF/XML/JPG/JPEG/PNG, 10 MB c/u (`UploadPaymentReceiptRequest`) |

No se exige un rol específico: cualquier usuario autenticado con visibilidad del proyecto puede adjuntar.

## Qué guarda

```php
$payment->update([
    'payment_receipt_documents' => $stored,
    'receipt_uploaded_at' => now(),
    'receipt_uploaded_by' => $user->id,
    'status' => 'receipt_attached',
]);
```

- Directorio `investment-payment-receipts/AAAA/MM/{folio}` en disco `local`, nombres UUID.
- Cada carga **reemplaza** la referencia anterior; los archivos viejos se conservan en disco por auditoría.
- `receipt_attached` está en `PAID_STATUSES`: sigue contando como pagado (ver [[Presupuesto por concepto]]).
- En la programación semanal, los pagos `receipt_attached` se muestran pero ya no son programables.

> [!info] Atajo operativo actual
> Como "Guardar Programación" está deshabilitado, el comprobante se adjunta normalmente a pagos `completed` (sin pasar por `scheduled_for_bank`).

> [!question] Por confirmar
> El mensaje del commit que introdujo la función describía que la carga se **anexaba** a los comprobantes previos; el código actual **reemplaza** la lista. Confirmar que reemplazar es el comportamiento deseado.

## Relacionado
- [[Programacion semanal]] · [[Documentos de pago]] · [[Flujo de pago de inversion]]
- [[InvestmentPaymentRequest]] · [[Acceso por proyecto]]
