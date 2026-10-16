---
tipo: datos
aliases:
  - Modelo de datos
  - Diagrama entidad-relación
tags:
  - payment-request/datos
fuentes:
  - app/Models/
  - database/migrations/
actualizado: 2026-10-15
---

# Modelo de datos

Mapa de las tablas principales del portal y cómo se relacionan. El detalle de cada tabla (columnas, casts, métodos) está en su nota dentro de `04 Datos/Modelos`.

## Dominios

| Dominio | Tablas |
|---|---|
| Organización y catálogos | `societies`, `branches`, `currencies`, `departments`, `positions`, `expense_concepts`, `payment_types`, `investment_expense_categories`, `investment_expense_concepts`, `strategies` |
| Usuarios y acceso | `users`, `department_user`, `project_user`, `roles`, `permissions`, `model_has_roles`, `role_has_permissions` |
| Proyectos e inversión | `projects`, `investment_requests`, `investment_request_approvals` |
| Pagos de inversión | `investment_payment_batches`, `investment_payment_requests`, `investment_payment_approvals` (legacy) |
| Programación y banco | `weekly_payment_schedules`, `weekly_payment_schedule_items`, `weekly_payment_schedule_approvals` |
| Política de ventanas | `payment_request_policies`, `payment_capture_attempt_logs` |
| Legacy pagos | `payment_requests`, `payment_request_approvals` |
| Infraestructura | `activity_log`, `notifications`, `jobs`, `job_batches`, `failed_jobs`, `cache`, `sessions`, `imports`, `exports`, `failed_import_rows` |

## Diagrama ER: inversión y pagos

```mermaid
erDiagram
    SOCIETIES ||--o{ BRANCHES : "tiene"
    BRANCHES ||--o{ PROJECTS : "aloja"
    CURRENCIES ||--o{ PROJECTS : "moneda base"
    PROJECTS ||--o{ INVESTMENT_REQUESTS : "agrupa"
    PROJECTS ||--o{ INVESTMENT_PAYMENT_BATCHES : "agrupa"
    PROJECTS }o--o{ USERS : "project_user"
    DEPARTMENTS }o--o{ USERS : "department_user"
    DEPARTMENTS }o--o{ INVESTMENT_EXPENSE_CATEGORIES : "pivote"
    INVESTMENT_EXPENSE_CATEGORIES ||--o{ INVESTMENT_EXPENSE_CONCEPTS : "contiene"
    INVESTMENT_EXPENSE_CONCEPTS ||--o{ INVESTMENT_REQUESTS : "concepto"
    DEPARTMENTS ||--o{ INVESTMENT_REQUESTS : "solicita"
    USERS ||--o{ INVESTMENT_REQUESTS : "captura"
    INVESTMENT_REQUESTS ||--o{ INVESTMENT_REQUEST_APPROVALS : "aprobaciones"
    INVESTMENT_REQUESTS ||--o{ INVESTMENT_PAYMENT_REQUESTS : "pagos"
    INVESTMENT_PAYMENT_BATCHES ||--o{ INVESTMENT_PAYMENT_REQUESTS : "batch_id"
    DEPARTMENTS ||--o{ INVESTMENT_PAYMENT_BATCHES : "lote del dpto"
    STRATEGIES ||--o{ INVESTMENT_PAYMENT_REQUESTS : "estrategia"
    CURRENCIES ||--o{ INVESTMENT_PAYMENT_REQUESTS : "moneda"
    INVESTMENT_PAYMENT_REQUESTS ||--o{ INVESTMENT_PAYMENT_APPROVALS : "legacy"
    WEEKLY_PAYMENT_SCHEDULES ||--o{ WEEKLY_PAYMENT_SCHEDULE_ITEMS : "partidas"
    INVESTMENT_PAYMENT_REQUESTS ||--o{ WEEKLY_PAYMENT_SCHEDULE_ITEMS : "programado en"
    WEEKLY_PAYMENT_SCHEDULES ||--o{ WEEKLY_PAYMENT_SCHEDULE_APPROVALS : "aprobaciones"
    USERS ||--o{ WEEKLY_PAYMENT_SCHEDULES : "created_by"

    INVESTMENT_REQUESTS {
        bigint id PK
        uuid uuid
        int folio_number
        bigint project_id FK
        bigint department_id FK
        bigint investment_expense_concept_id FK
        string status
        decimal total
        bool is_addendum
        bool is_deductive
    }
    INVESTMENT_PAYMENT_REQUESTS {
        bigint id PK
        uuid uuid
        bigint folio_number
        bigint investment_request_id FK
        bigint batch_id FK
        bigint strategy_id FK
        string payment_type
        string status
        decimal total
        decimal approved_amount
        date payment_provision_date
    }
    INVESTMENT_PAYMENT_BATCHES {
        bigint id PK
        uuid uuid
        bigint department_id FK
        bigint project_id FK
        int week_number
        int year
        string status
        uuid ceo_approval_token
    }
    PROJECTS {
        bigint id PK
        string name
        bool is_active
        timestamp closed_at
        bool requires_pm_approval
        decimal authorized_budget
    }
```

## Diagrama ER: catálogos y legacy

```mermaid
erDiagram
    USERS }o--|| DEPARTMENTS : "department_id principal"
    USERS }o--|| POSITIONS : "position_id"
    DEPARTMENTS }o--o| USERS : "authorizer_level_1_id y 2"
    USERS }o--o{ ROLES : "model_has_roles"
    ROLES }o--o{ PERMISSIONS : "role_has_permissions"
    PAYMENT_TYPES ||--o{ PAYMENT_REQUESTS : "tipo"
    PAYMENT_TYPES ||--o{ INVESTMENT_REQUESTS : "tipo"
    EXPENSE_CONCEPTS ||--o{ PAYMENT_REQUESTS : "concepto"
    BRANCHES ||--o{ PAYMENT_REQUESTS : "sucursal"
    CURRENCIES ||--o{ PAYMENT_REQUESTS : "moneda"
    DEPARTMENTS ||--o{ PAYMENT_REQUESTS : "solicita"
    PAYMENT_REQUESTS ||--o{ PAYMENT_REQUEST_APPROVALS : "etapas y niveles"
    USERS ||--o{ PAYMENT_CAPTURE_ATTEMPT_LOGS : "intentos"
```

## Resumen de tablas principales

| Tabla | Modelo | Soft deletes | Activity log | Llave de ruta |
|---|---|---|---|---|
| `users` | [[User]] | Sí | Sí | id |
| `roles` | [[Role]] | No | Sí | id |
| `departments` | [[Department]] | Sí | Sí | id |
| `positions` | [[Position]] | Sí | Sí | id |
| `societies` | [[Society]] | Sí | Sí | id |
| `branches` | [[Branch]] | Sí | Sí (sin `sap_password`) | id |
| `currencies` | [[Currency]] | Sí | Sí | id |
| `expense_concepts` | [[ExpenseConcept]] | Sí | Sí | id |
| `payment_types` | [[PaymentType]] | Sí | Sí | id |
| `investment_expense_categories` | [[InvestmentExpenseCategory]] | Sí | Sí | id |
| `investment_expense_concepts` | [[InvestmentExpenseConcept]] | Sí | Sí | id |
| `strategies` | [[Strategy]] | Sí (sin borrado definitivo) | Sí | id |
| `projects` | [[Project]] | Sí | Sí | id |
| `investment_requests` | [[InvestmentRequest]] | Sí | Sí | uuid |
| `investment_request_approvals` | [[InvestmentRequestApproval]] | No | Sí | id |
| `investment_payment_batches` | [[InvestmentPaymentBatch]] | No | Sí | uuid |
| `investment_payment_requests` | [[InvestmentPaymentRequest]] | Sí | Sí | uuid |
| `investment_payment_approvals` | [[InvestmentPaymentApproval]] | No | Sí | id |
| `weekly_payment_schedules` | [[WeeklyPaymentSchedule]] | Sí | Sí | uuid |
| `weekly_payment_schedule_items` | [[WeeklyPaymentScheduleItem]] | No | Sí | id |
| `weekly_payment_schedule_approvals` | [[WeeklyPaymentScheduleApproval]] | No | Sí | id |
| `payment_requests` | [[PaymentRequest]] | Sí | Sí | uuid |
| `payment_request_approvals` | [[PaymentRequestApproval]] | No | Sí | id |
| `payment_request_policies` | [[PaymentRequestPolicy (modelo)]] | No | No | — |
| `payment_capture_attempt_logs` | [[PaymentCaptureAttemptLog]] | No | No | — |

## Convenciones del modelo

- **Folios**: `folio_number = max(folio_number incluidos borrados) + 1` en `creating` (solicitudes y pagos).
- **UUID como llave de ruta** en entidades expuestas por URL o correo.
- **Normalización de texto** con mutators: nombres y proveedores en MAYÚSCULAS, correos en minúsculas.
- **Montos** en `decimal(12|14,2)`; tipo de cambio `decimal(12,4)`; sumas a MXN con `total × exchange_rate`. Ver [[Multimoneda]].
- **Borrado**: casi todo usa `SoftDeletes`; la regla del proyecto prohíbe borrar registros. Ver [[Proteccion de la base de datos]].
- **Estados**: dos modelos usan spatie/model-states; los pagos de inversión, lotes y programación usan strings. Ver [[Estados]].

## Relacionado
- [[Estados]] · [[Enums]] · [[Migraciones]] · [[Auditoria y activity log]] · [[Backend Laravel]]
