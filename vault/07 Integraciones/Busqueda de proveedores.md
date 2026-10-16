---
tipo: integracion
aliases:
  - Búsqueda de proveedores
tags:
  - payment-request/integracion
fuentes:
  - app/Http/Controllers/ProviderSearchController.php
  - routes/web.php
  - resources/js/components/provider-autocomplete.tsx
  - resources/js/pages/payment-requests/create.tsx
  - resources/js/pages/payment-requests/edit.tsx
  - resources/js/pages/investment-sheets/create.tsx
  - resources/js/pages/investment-sheets/edit.tsx
  - resources/js/pages/investment-sheets/consolidated.tsx
actualizado: 2026-10-15
---

# Búsqueda de proveedores

Autocompletado de **razón social** y **RFC** en los formularios de captura. No hay catálogo de proveedores ni conexión con SAP: las sugerencias salen de los proveedores ya capturados en solicitudes de pago del módulo legacy.

## Endpoint

| Ruta | Método | Nombre | Middleware | Controller |
|---|---|---|---|---|
| `/providers/search` | GET | `providers.search` | `auth`, `verified`, `throttle:30,1` | `ProviderSearchController` (invocable) |

### Parámetros y respuesta

| Parámetro | Valores | Comportamiento |
|---|---|---|
| `q` | texto | Se recorta (`trim`). Si tiene **menos de 2 caracteres** → `[]`. |
| `field` | `provider` (default) o `rfc` | Columna sobre la que se busca. Cualquier otro valor → `[]` (lista blanca, evita inyectar nombres de columna). |

Consulta (`ProviderSearchController::__invoke()`):

```php
PaymentRequest::query()
    ->select('provider', 'rfc')
    ->where($field, 'like', "%{$query}%")
    ->whereNotNull('provider')
    ->where('provider', '!=', '')
    ->groupBy('provider', 'rfc')
    ->orderBy('provider')
    ->limit(10)
    ->get();
```

Respuesta JSON: arreglo de hasta 10 objetos `{ "provider": "...", "rfc": "..." | null }`, únicos por par proveedor + RFC y ordenados alfabéticamente.

## De dónde salen los proveedores

| Fuente | ¿Alimenta el autocompletado? |
|---|---|
| `payment_requests.provider` / `rfc` (módulo legacy, ver [[Modulo legacy Payment Requests]]) | **Sí**, es la única fuente. Excluye solicitudes con soft delete (ver nota). |
| `investment_requests.provider` / `rfc` ([[Solicitudes de inversion]]) | No |
| `investment_payment_requests.provider` / `rfc` ([[Solicitudes de pago de inversion]]) | No |
| Catálogo de proveedores / SAP B1 | No existe catálogo; SAP no se consulta (ver [[SAP Business One]]) |

> [!note] Soft delete
> [[PaymentRequest]] usa `SoftDeletes`, así que `PaymentRequest::query()` aplica el `SoftDeletingScope`: los proveedores que solo existan en solicitudes eliminadas no aparecen.

> [!question] Por confirmar: fuente incompleta
> El componente se usa en los formularios de inversión (`investment-sheets/create`, `edit` y la captura de pagos en `consolidated`), pero solo busca en la tabla legacy. Los proveedores capturados únicamente en solicitudes o pagos de inversión nunca se sugieren. Confirmar si se quiere ampliar la búsqueda a `investment_requests` e `investment_payment_requests` (con `union`) o crear un catálogo de proveedores.

## Componente `ProviderAutocomplete`

`resources/js/components/provider-autocomplete.tsx`:

- Props: `value`, `onChange`, `onSelect`, `field` (`provider` | `rfc`), `placeholder`, `maxLength`, `id`.
- Con `field = 'rfc'` convierte lo tecleado a mayúsculas.
- Debounce de **300 ms**; no consulta con menos de 2 caracteres.
- Llama a `fetch('/providers/search?q=...&field=...')` con **URL literal** (no usa Wayfinder).
- Navegación con teclado: flechas para moverse, `Enter` para elegir, `Escape` para cerrar; cierra al hacer clic fuera.
- Al elegir una sugerencia, las páginas rellenan **ambos** campos: `provider` y `rfc` (si la sugerencia trae RFC; si no, conservan el capturado).

| Página | Campos con autocompletado |
|---|---|
| `payment-requests/create`, `payment-requests/edit` | Proveedor y RFC |
| `investment-sheets/create`, `investment-sheets/edit` | Proveedor y RFC |
| `investment-sheets/consolidated` (alta de pago) | Proveedor y RFC |

## Consideraciones

> [!warning] Alcance de los datos
> La búsqueda no filtra por usuario, departamento ni proyecto: cualquier usuario autenticado puede obtener proveedor + RFC de cualquier solicitud legacy escribiendo 2 letras. Ver [[Acceso por proyecto]] y [[Seguridad de la aplicacion]].

- Los comodines de `LIKE` (`%`, `_`) que escriba el usuario no se escapan; solo amplían la búsqueda, no representan riesgo de inyección (el valor va como binding).
- Los modelos normalizan `provider` al guardar (`PaymentRequest::setProviderAttribute`, `InvestmentPaymentRequest::setProviderAttribute` en mayúsculas), así que las sugerencias aparecen normalizadas.
- El throttle de 30 peticiones por minuto, combinado con el debounce, alcanza para escritura normal.

## Relacionado
- [[Modulo legacy Payment Requests]] · [[Solicitudes de inversion]] · [[Solicitudes de pago de inversion]] · [[Frontend Inertia React]] · [[Rutas]]
