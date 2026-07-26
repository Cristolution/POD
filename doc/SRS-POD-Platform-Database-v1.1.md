# Software Requirements Specification (SRS)
## Print-on-Demand (POD) Platform — Database & Core Domain Layer
**Version:** 1.0
**Companion to:** PRD-POD-Platform-Database-v1.md
**Reference schema:** final DBML (lookup-table version, no enums)

---

## 1. Introduction

### 1.1 Purpose
This SRS specifies the functional and non-functional requirements for the data layer of the POD platform, translating the product decisions in the PRD into precise, testable requirements against the finalized database schema.

### 1.2 Scope
Covers: authentication/RBAC, design & material catalog, PSP capability & designer-provider relationships, order & fulfillment (including sub-order splitting), payments, and custom order requests with messaging. Does not cover UI/UX, front-end behavior, or infrastructure/deployment.

### 1.3 Definitions
- **PSP** — Print Service Provider, a registered fulfillment business.
- **Sub-order** — an independent per-provider fulfillment unit within an Order.
- **Custom Order Request (COR)** — a negotiated, non-catalog transaction between a Customer and Designer.

---

## 2. Functional Requirements

### FR-1: Authentication & Authorization
- FR-1.1 The system SHALL store all login credentials in a single `user` table, independent of business role.
- FR-1.2 The system SHALL support many-to-many assignment of roles to users via `user_role`, and permissions to roles via `role_permission`.
- FR-1.3 The system SHALL prevent duplicate `(user_id, role_id)` and `(role_id, permission_id)` pairs via unique constraints.
- FR-1.4 `customer`, `designer`, and `print_provider` profile records SHALL each reference exactly one `user` record (1:1).

### FR-2: Design Catalog
- FR-2.1 A Designer SHALL be able to create a `design` with exactly one base file and a `file_format` reference.
- FR-2.2 A `design` SHALL be linkable to one or more `material` records via `design_material`, each with an independent price.
- FR-2.3 The system SHALL enforce at most one active `design_material` row per unique `(design_id, material_id)` pair.
- FR-2.4 A `design_material` row MAY have no assigned provider (nullable `assigned_provider_id`) until the Designer selects one.

### FR-3: Provider Capability & Relationships
- FR-3.1 A `print_provider` SHALL declare supported materials via `printer_capability`, with uniqueness enforced on `(provider_id, material_id)`.
- FR-3.2 A `designer_provider_agreement` SHALL represent the commercial relationship between exactly one Designer and one PSP, including a status and commission rate, unique per `(designer_id, provider_id)`.
- FR-3.3 The application layer SHALL reject assignment of a PSP to a `design_material` row unless: (a) an active `designer_provider_agreement` exists between that Designer and PSP, AND (b) a corresponding `printer_capability` row exists and is supported.
- FR-3.4 A Designer SHALL be able to change the `assigned_provider_id` on a `design_material` row at any time; this change SHALL NOT retroactively affect already-placed orders.

### FR-4: Order Placement
- FR-4.1 An `order` SHALL reference exactly one `customer` and exactly one `designer`.
- FR-4.2 The application layer SHALL reject adding an item to an order if that item's design does not belong to the order's `designer_id`.
- FR-4.3 Each `order` SHALL be decomposed into one or more `sub_order` records, grouped by the `provider_id` responsible for fulfilling each item.
- FR-4.4 Each `order_item` SHALL belong to exactly one `sub_order` and reference exactly one `design_material`.
- FR-4.5 At order placement time, the system SHALL snapshot `unit_designer_price`, `unit_platform_fee`, and `unit_customer_price` onto each `order_item`; these values SHALL NOT be recalculated from live `design_material.price` after creation.

### FR-5: Fulfillment & Sub-Order Independence
- FR-5.1 Each `sub_order` SHALL carry its own status (via `sub_order_status` lookup) independent of sibling sub-orders under the same order.
- FR-5.2 A failure, delay, or status change in one `sub_order` SHALL NOT alter the status or processing of any other `sub_order` under the same `order`.
- FR-5.3 The overall status presented for an `order` SHALL be derived at read-time from the aggregate of its `sub_order` statuses, and SHALL NOT be persisted as a separate stored field on `order`.
- FR-5.4 Each `sub_order` SHALL have at most one `shipment` (1:1) in v1.
- FR-5.5 Each `shipment` MAY have many `tracking_event` records, ordered by `occurred_at`.

### FR-6: Payments
- FR-6.1 A `payment` record SHALL reference either an `order` or a `custom_order_request`, but not both simultaneously and not neither.
- FR-6.2 `payment.amount` for an order-linked payment SHALL equal the sum of `unit_customer_price × quantity` across all `order_item` rows under that order's sub-orders.
- FR-6.3 The system SHALL support a `refunded` status value in `payment_status` as a placeholder; no refund workflow logic is required in v1.

### FR-7: Custom Order Requests
- FR-7.1 A `custom_order_request` SHALL reference exactly one `customer` and one `designer`, and MAY optionally reference a base `design` and/or `material`.
- FR-7.2 A `custom_order_request` SHALL carry its own status lifecycle (via `custom_request_status`) independent of the standard `order`/`sub_order` status model.
- FR-7.3 A `custom_order_request` SHALL NOT be convertible into, or merged with, an `order` record at any point in its lifecycle.
- FR-7.4 The system SHALL store a full message thread per `custom_order_request` via `custom_request_message`, each message referencing the sending `user` directly.
- FR-7.5 Messages SHALL be retrievable in chronological order via `sent_at`.
- FR-7.6 A `custom_order_request` SHALL carry its own `carrier` and `tracking_number` fields directly (not via the `shipment` table), reflecting its single-provider, non-split nature.

### FR-8: Admin/List View Preferences
- FR-8.1 The system SHALL support saving arbitrary filter configurations per user per named view via `saved_filter` (`filter_json`).
- FR-8.2 The system SHALL support saving column visibility preferences per user per named view via `table_column_preference`.
- FR-8.3 Both SHALL be generic enough to apply to any current or future list view without schema changes (identified only by `view_name` string).

### FR-9: Status/Category Integrity
- FR-9.1 All status and category fields (provider status, agreement status, sub-order status, payment status, custom request status, print method, file format) SHALL be backed by dedicated lookup tables referenced via foreign key, not free-text or in-schema enumerations.
- FR-9.2 Adding a new valid status/category value SHALL require only a new row in the relevant lookup table, not a schema migration.

---

## 3. Non-Functional Requirements

### NFR-1: Data Integrity
- All join/lookup tables enforce uniqueness constraints on their natural keys (`design_material`, `printer_capability`, `designer_provider_agreement`, `role_permission`, `user_role`, all `*_status`/`print_method`/`file_format` `code` columns).
- Foreign keys are used throughout in preference to soft references, so referential integrity is enforced by the database, not solely by application code.

### NFR-2: Historical Accuracy / Auditability
- Order-level financial data (`order_item` price snapshots) must remain accurate regardless of later changes to `design_material.price`, designer commission rates, or PSP assignments.

### NFR-3: Extensibility
- `file_format` and `print_method` are lookup tables specifically so v2 features (SVG/AI/3D source files, new print methods) require only new rows, not schema changes.
- The `payment` polymorphic reference pattern (nullable dual FK) is designed so future transaction types (e.g. subscriptions, add-on services) could follow the same pattern without restructuring `payment`.

### NFR-4: Independence of Fulfillment Units
- The system must guarantee, at the data-model level, that `sub_order` records under the same `order` have no structural dependency on one another's status — this is a hard non-functional constraint driven by the explicit "if it fails, it fails independently" product decision.

---

## 4. Explicit Constraints Not Enforceable at Schema Level

The following business rules are noted as requiring application-layer enforcement, since they cannot be expressed as pure DBML/SQL constraints:

1. Single-designer-per-order rule (FR-4.2).
2. PSP assignment validity against active agreements and declared capabilities (FR-3.3).
3. Exactly-one-of `order_id` / `custom_order_request_id` on `payment` (FR-6.1).

---

## 5. Traceability Note

Every requirement in Section 2 maps directly to a decision explicitly made during schema design (see PRD Section 3, items 1–15). No requirement in this document introduces new scope beyond what was agreed; anything not listed here (refunds, delivery-company entities, multi-designer carts, AI generation, cart/session modeling) is deliberately excluded per PRD Section 4.
