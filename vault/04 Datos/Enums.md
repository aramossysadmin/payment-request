---
tipo: datos
aliases:
  - Enums
  - Enumeraciones
tags:
  - payment-request/datos
fuentes:
  - app/Enums/DocumentMode.php
  - app/Enums/InvestmentPaymentType.php
  - app/Enums/IvaRate.php
  - app/Enums/PaymentRequestStatus.php
  - app/Enums/PaymentType.php
  - app/Enums/PaymentTypeCategory.php
actualizado: 2026-10-15
---

# Enums

Enumeraciones PHP respaldadas por string (`app/Enums`). Todas exponen `label()` con el texto en español que se muestra en la UI.

## Resumen

| Enum | Casos | Se guarda en | En uso |
|---|---|---|---|
| `DocumentMode` | 3 | `payment_types.invoice_documents_mode`, `additional_documents_mode` | Sí |
| `InvestmentPaymentType` | 8 | `investment_payment_requests.payment_type` | Sí |
| `IvaRate` | 6 | `iva_rate` de solicitudes y pagos | Sí |
| `PaymentTypeCategory` | 2 | `payment_types.category` | Sí |
| `PaymentRequestStatus` | 4 | — | Sin uso en PHP |
| `PaymentType` (enum) | 3 | — | Sin uso en PHP |

## DocumentMode

| Caso | Valor | Etiqueta |
|---|---|---|
| `Disabled` | `disabled` | Deshabilitado |
| `Optional` | `optional` | Opcional |
| `Required` | `required` | Obligatorio |

Uso: casts de [[PaymentType]]; validación de documentos en `StorePaymentRequestRequest` y `UpdatePaymentRequestRequest`; selector en `PaymentTypeResource`.

## InvestmentPaymentType

| Caso | Valor | Etiqueta | Documentos al cargar |
|---|---|---|---|
| `Factura` | `factura` | Factura | PDF + XML obligatorios |
| `Reembolso` | `reembolso` | Reembolso | 1 archivo PDF/JPG/PNG |
| `Estrategia` | `estrategia` | Estrategia | 1 archivo; exige `strategy_id` y `strategy_amount` |
| `Anticipo` | `anticipo` | Anticipo | 1 archivo |
| `Cotizacion` | `cotizacion` | Cotización | 1 archivo |
| `Pagare` | `pagare` | Pagaré | 1 archivo |
| `Domiciliado` | `domiciliado` | Domiciliado | 1 archivo |
| `FacturaEspana` | `factura_espana` | Factura España | 1 archivo |

- `labelFor(?string $value)` devuelve la etiqueta o `—` si el valor no existe.
- Uso: validación `Rule::enum` en `StoreInvestmentPaymentRequest`, `UpdateInvestmentPaymentRequest`, `UploadInvestmentPaymentDocumentsRequest`; reglas de estrategia en `Concerns/ResolvesInvestmentStrategy`; etiquetas en `InvestmentPaymentRequestResource` y `InvestmentPaymentHistoryExcelController`.
- El frontend tiene su propio mapa en `resources/js/lib/payment-type-labels.ts` y `WeeklyPaymentScheduleExport` otro en PHP: al agregar un caso hay que actualizar esos mapas.

Ver [[Documentos de pago]] y [[Estrategias]].

## IvaRate

| Caso | Valor | Etiqueta | `rate()` |
|---|---|---|---|
| `Zero` | `0.00` | IVA 0% | 0.0 |
| `Four` | `0.04` | IVA 4% | 0.04 |
| `Eight` | `0.08` | IVA 8% | 0.08 |
| `Ten` | `0.10` | IVA 10% | 0.10 |
| `Sixteen` | `0.16` | IVA 16% | 0.16 |
| `TwentyOne` | `0.21` | IVA 21% | 0.21 |

Uso: cast `iva_rate` en [[PaymentRequest]], [[InvestmentRequest]] e [[InvestmentPaymentRequest]]; validación `Rule::enum` en los Form Requests; `Strategy::amountsFor()` usa `rate()` para separar subtotal e IVA. 10 % y 21 % cubren tasas de España.

## PaymentTypeCategory

| Caso | Valor | Etiqueta |
|---|---|---|
| `Payment` | `payment` | Solicitud de Pago |
| `Investment` | `investment` | Concepto de Inversión |

Uso: cast `category` de [[PaymentType]] y scopes `forPayments()` / `forInvestments()`.

## PaymentRequestStatus

| Caso | Valor | Etiqueta |
|---|---|---|
| `Pending` | `pending` | Pendiente |
| `Approved` | `approved` | Aprobada |
| `Rejected` | `rejected` | Rechazada |
| `Paid` | `paid` | Pagada |

No se referencia en código PHP. Corresponde a los estados originales de `payment_requests`, que se migraron a los estados spatie en febrero 2026. Existe un tipo TypeScript homónimo en `resources/js/types/payment-request.ts`. Ver [[Estados]].

## PaymentType (enum)

| Caso | Valor | Etiqueta |
|---|---|---|
| `Invoice` | `invoice` | Pago con Factura |
| `Advance` | `advance` | Anticipo |
| `Investment` | `investment` | Inversiones |

No se referencia en código PHP. Son los slugs con los que la migración `2026_03_15_032152` creó los registros iniciales del catálogo [[PaymentType]] cuando la columna string pasó a llave foránea.

> [!question] Por confirmar
> Confirmar si `PaymentRequestStatus` y `App\Enums\PaymentType` pueden retirarse o se conservan como referencia histórica.

## Relacionado
- [[Estados]] · [[Modelo de datos]] · [[Multimoneda]]
