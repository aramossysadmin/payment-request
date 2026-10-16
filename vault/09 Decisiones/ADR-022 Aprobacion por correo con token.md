---
tipo: decision
aliases:
  - ADR-022 Aprobación por correo con token
tags:
  - payment-request/decisiones
fuentes:
  - app/Http/Controllers/EmailApprovalController.php
  - routes/web.php
  - app/Services/ApprovalService.php
  - app/Services/InvestmentApprovalService.php
  - app/Http/Controllers/InvestmentPaymentBatchController.php
  - git 3878698
  - git 63ebfc6
estado: vigente
actualizado: 2026-10-15
---

# ADR-022 Aprobación por correo con token

Los aprobadores autorizan o rechazan directamente desde un enlace en el correo, autenticado por un token de un solo uso con caducidad, sin iniciar sesión en el panel.

## Contexto
Los autorizadores (CEO, directores) aprobaban desde el panel administrativo, lo que exigía iniciar sesión y navegar hasta cada solicitud; las aprobaciones se demoraban.

## Decisión
- `EmailApprovalController` con pantallas de ver / aprobar / rechazar en `/approval/{token}` (`routes/web.php`, grupo `throttle:10,1`); el token se guarda en la aprobación con `approval_token_expires_at`.
- El correo usa la URL de aprobación como acción principal y el enlace al panel como secundario.
- El patrón se reutilizó en otros flujos:

| Flujo | Ruta | Vigencia |
|---|---|---|
| Solicitudes de pago legacy | `/approval/{token}` | 48 h (`app/Services/ApprovalService.php`) |
| Solicitudes de inversión | `/approval/{token}` | 48 h (`app/Services/InvestmentApprovalService.php`) |
| Lote de pagos de inversión (CEO) | `/investment-batch-approval/{token}` | 96 h (`InvestmentPaymentBatchController`, `ceo_approval_token_expires_at`) |

## Consecuencias
- Quien tenga el enlace puede actuar mientras el token sea válido: los correos deben tratarse como sensibles.
- Rotar la `APP_KEY` no invalida estos tokens (se guardan en BD), pero sí las URLs firmadas de documentos.

## Fecha
2026-03-14

## Fuentes
- Commits `3878698`, `63ebfc6`, `d09e39b`.

## Relacionado
- [[Indice de decisiones]] · [[Catalogo de correos]] · [[Aprobacion del CEO]] · [[Seguridad de la aplicacion]]
