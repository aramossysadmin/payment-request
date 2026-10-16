---
tipo: flujo
aliases:
  - Presupuesto por concepto
  - Saldo disponible del concepto
tags:
  - payment-request/flujos
  - payment-request/inversion
  - payment-request/presupuesto
fuentes:
  - app/Models/InvestmentRequest.php
  - app/Models/InvestmentPaymentRequest.php
  - app/Http/Requests/StoreInvestmentPaymentRequest.php
  - app/Http/Requests/UpdateInvestmentPaymentRequest.php
  - app/Http/Controllers/InvestmentPaymentRequestController.php
  - app/Http/Controllers/InvestmentSheetConsolidatedController.php
  - app/Http/Controllers/InvestmentPaymentReviewController.php
  - app/Services/InvestmentDepartmentBreakdownService.php
actualizado: 2026-10-15
---

# Presupuesto por concepto

Cómo se calcula el **saldo disponible** contra el que se capturan los pagos de inversión. La fuente única es `InvestmentRequest::budgetBreakdown()`, normalizada a MXN.

## Grupo presupuestal

El presupuesto no es por solicitud individual sino por **grupo**:

```text
grupo = mismo project_id + mismo investment_expense_concept_id + mismo department_id
        y solo solicitudes en estado completed
```

Así la solicitud inicial y sus aditivas autorizadas forman un solo saldo. Si la solicitud no tiene concepto o proyecto, el alcance cae a la propia solicitud (`scope = 'request'`).

## Fórmula (`budgetBreakdown(?int $excludePaymentId = null)`)

| Componente | Cálculo | Moneda |
|---|---|---|
| `budget` | Σ `investment_requests.total × currencies.exchange_rate` del grupo | MXN |
| `committed` | Σ `investment_payment_requests.total × exchange_rate` de pagos del grupo en `COMMITTED_STATUSES` | MXN |
| `paid` | Igual, en `PAID_STATUSES` | MXN |
| `available` | `budget − committed − paid` | MXN |
| `scope` | `concept` o `request` | — |

`$excludePaymentId` quita un pago concreto de comprometido y pagado (se usa al **editar** un borrador para no contarlo contra sí mismo).

`exchange_rate` significa "1 unidad de la moneda = X MXN" (ver [[Multimoneda]]).

## Estados que consumen

`InvestmentPaymentRequest`:

```php
COMMITTED_STATUSES = ['draft', 'submitted', 'ceo_approved', 'projectmanager_review',
                      'projectmanager_approved', 'final_approved', 'documents_pending'];

PAID_STATUSES = ['pending_approval', 'approved', 'completed',
                 'scheduled_for_bank', 'receipt_attached'];
```

- **Comprometido** reserva saldo desde el borrador hasta la aprobación final.
- **Pagado** inicia al cargar documentos (`completed`) e incluye los estados legacy `approved` y `pending_approval`.
- Cualquier otro estado (`ceo_rejected`, `projectmanager_rejected`, `auto_cancelled`, `rejected`, `final_rejected`) **libera** el saldo. Los pagos con soft delete tampoco cuentan (el query de Eloquent los excluye).

## Dónde se valida al capturar

| Momento | Archivo | Regla |
|---|---|---|
| Crear pago | `StoreInvestmentPaymentRequest::withValidator()` | `total` capturado × `exchange_rate` de la moneda elegida, comparado en centavos contra `budgetBreakdown()['available']`. Error: "El total ($X MXN) excede el saldo disponible del presupuesto/concepto ($Y MXN, ya descontando lo comprometido y pagado)." |
| Editar borrador | `UpdateInvestmentPaymentRequest::withValidator()` | Igual, con `budgetBreakdown(excludePaymentId: $payment->id)` |
| Modal (aviso visual) | `consolidated.tsx` | Muestra "El total excede el saldo disponible" y deshabilita el botón si `total > group_remaining` |
| Botón "Solicitar Pago" | `PaymentsDrawer` en `consolidated.tsx` | Solo aparece si el saldo del grupo es > 0 y el concepto es de un departamento del usuario |

## Dónde se muestra

| Pantalla / salida | Origen del número |
|---|---|
| Panel de pagos por concepto (JSON `investment-payment-requests/{id}`) | `budgetBreakdown()` |
| Borradores del consolidado (`concept_effective_remaining`) | `budgetBreakdown(excludePaymentId)` |
| Revisión del PM (presupuesto, comprometido, pagado, disponible por pago) | `budgetBreakdown()`, una vez por grupo |
| Tabla de solicitudes del consolidado (`group_budget`, `group_committed`, `group_paid`, `group_remaining`) | Cálculo inline en `InvestmentSheetConsolidatedController` con la misma lógica y las mismas constantes |
| Dashboard del proyecto e "Inversión por Departamento" | Agregados por proyecto/departamento con las mismas constantes (`InvestmentSheetConsolidatedController`, `InvestmentDepartmentBreakdownService`) |
| Accesor `remaining_balance` | `availableBudget()` → `budgetBreakdown()['available']` |

## Ejemplo ficticio

Concepto "Mobiliario" del departamento A en el proyecto X: solicitud inicial $100,000.00 MXN + aditiva autorizada de USD 1,000.00 con tipo de cambio 18.00 → `budget` = $118,000.00. Pagos: un borrador de $20,000.00 (comprometido) y uno `completed` de $30,000.00 (pagado) → `available` = $68,000.00. Un nuevo pago de USD 4,000.00 (= $72,000.00 MXN) sería rechazado.

> [!question] Por confirmar
> - Los montos consumidos usan `total`, no `approved_amount`: si el PM reduce el monto, la diferencia no se libera en el saldo.
> - `budgetBreakdown()` no resta solicitudes deductivas (`is_deductive`), mientras el dashboard del proyecto sí lo hace.
> - `StoreInvestmentPaymentRequest` valida que la solicitud de inversión exista, pero no que esté `completed`; si se envía el id de una solicitud pendiente, el grupo presupuestal se calcula con las `completed` del mismo concepto (o con la propia solicitud si no tiene concepto/proyecto).

## Relacionado
- [[Solicitudes de inversion]] · [[Solicitudes de pago de inversion]] · [[Consolidado por proyecto]] · [[Revision del PM]]
- [[Flujo de pago de inversion]] · [[Multimoneda]] · [[InvestmentRequest]] · [[InvestmentPaymentRequest]]
