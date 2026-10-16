---
tipo: decision
aliases:
  - ADR-012 Credenciales SAP cifradas por sucursal
tags:
  - payment-request/decisiones
  - payment-request/seguridad
fuentes:
  - app/Models/Branch.php
  - config/sap.php
  - app/Filament/Resources/BranchResource.php
  - git 046baf1
  - git 496410a
estado: vigente
actualizado: 2026-10-15
---

# ADR-012 Credenciales SAP cifradas por sucursal

Cada sucursal guarda sus propias credenciales de SAP Business One; la contraseña se almacena con cifrado simétrico dependiente de la `APP_KEY`.

## Contexto
Para integrar el portal con el Service Layer de SAP B1, cada sucursal se conecta con su base, usuario y contraseña. SAP necesita la contraseña en claro al iniciar sesión, así que no puede guardarse como hash.

## Decisión
- Columnas en `branches`: `sap_database`, `sap_branch_id`, `sap_user`, `sap_password` (texto) e `is_active`; todas opcionales en BD para configurar sucursal por sucursal.
- `sap_password` con cast `encrypted`; excluida del activity log (`logExcept(['sap_password'])`). Helper `Branch::isSapConfigured()`.
- URL del Service Layer, timeout y verificación SSL globales en `config/sap.php`.
- En Filament, la contraseña vacía en edición conserva la actual.
- Las sucursales inactivas no aparecen en selectores pero conservan su histórico.

## Consecuencias
- Si la `APP_KEY` cambia o se pierde, las contraseñas son irrecuperables; se documentó el procedimiento de rotación en `CLAUDE.md` (ver [[APP_KEY y datos cifrados]]).
- El envío real de pagos a SAP y el botón de "probar conexión" quedaron fuera de esa iteración.

> [!question] Por confirmar
> Estado actual de la integración: si ya se envían pagos a SAP o solo están las credenciales. Ver [[SAP Business One]].

## Fecha
2026-06-22

## Fuentes
- Commits `046baf1` y `496410a`.

## Relacionado
- [[Indice de decisiones]] · [[SAP Business One]] · [[Branch]] · [[APP_KEY y datos cifrados]]
