# Cambios Pendientes

Archivo de registro de cambios pendientes por trabajar.
Claude gestiona este archivo: agrega pendientes detectados y elimina los completados.

---

## Cómo usar este archivo

- **Agregar:** Claude detecta un pendiente durante una sesión y lo registra aquí.
- **Trabajar:** El usuario revisa, decide qué abordar y autoriza a Claude a proceder.
- **Eliminar:** Al completar un cambio, Claude elimina la entrada correspondiente.

---

## Pendientes

### [LIMPIEZA] Eliminar `expense_concept_id` de InvestmentRequest
**Prioridad:** Baja  
**Riesgo:** Bajo (Nivel 1) / Medio (Nivel 2)  
**Detectado:** 2026-05-14

**Contexto:**  
La columna `expense_concept_id` existe en `investment_requests` pero nunca ha tenido datos (0/130 registros). Fue reemplazada por `investment_expense_concept_id` desde la migración `2026_04_15_160043`. La UI ya fue corregida para usar el campo correcto.

**Nivel 1 — Limpieza de código PHP (sin migración):**
- Quitar `expense_concept_id` del `$fillable` en `app/Models/InvestmentRequest.php`
- Quitar la relación `expenseConcept()` del mismo modelo
- Quitar regla de validación en `app/Http/Requests/StoreInvestmentRequestRequest.php`
- Quitar regla de validación en `app/Http/Requests/UpdateInvestmentRequestRequest.php`
- Quitar serialización en `app/Http/Resources/InvestmentRequestResource.php`

**Nivel 2 — Eliminar columna de BD (requiere migración):**
- Crear migración que elimine `expense_concept_id` de la tabla `investment_requests`
- Ejecutar después del Nivel 1

**Archivos involucrados:**
- `app/Models/InvestmentRequest.php`
- `app/Http/Requests/StoreInvestmentRequestRequest.php`
- `app/Http/Requests/UpdateInvestmentRequestRequest.php`
- `app/Http/Resources/InvestmentRequestResource.php`
- Nueva migración (Nivel 2)
