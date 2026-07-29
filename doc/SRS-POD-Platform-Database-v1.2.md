# Software Requirements Specification (SRS)
## Print-on-Demand (POD) Platform — Database & Core Domain Layer
**Version:** 1.2
**Companion to:** `PRD-POD-Platform-Database-v1.2.md`
**Reference schema:** final DBML (`doc/database.dbdiagram.txt`) and SQL DDL (`doc/POD 1.2.sql`) — 17 tables, status fields as inline varchar enums.

---

## 1. Introduction

### 1.1 Purpose
This SRS specifies the functional and non-functional requirements for the data layer of the POD platform, translating the product decisions in the v1.2 PRD into precise, testable requirements against the finalized v1.2 database schema (17 tables).

### 1.2 Scope
Covers: authentication with simple role enum, design & product-template catalog, designer-to-template mapping with snapshot pricing, persistent cart, orders with shipping-address snapshot, per-item fulfillment, multi-shipment per order, payments with media-attached proof, polymorphic media and notifications, application settings.

Does NOT cover: custom order requests, sub-orders as separate entities, full RBAC, refund workflow, dynamic shipping pricing logic, UI/UX, infrastructure/deployment.

### 1.3 Definitions
- **PSP** — Print Service Provider, a registered fulfillment business.
- **Product Template** — a printer-owned printable item (mug, shirt, poster, etc.) with optional variants.
- **Design–Product Mapping** — a designer's offer of one design on one product template, with a customer-facing price.
- **Persistent Cart** — long-lived cart rows in `cart_items`, not a session-only construct.
- **Shipping Snapshot** — the copy of the shipping address captured on the `orders` row at checkout time.

---

## 2. Functional Requirements

### FR-1: Authentication & Role
- FR-1.1 The system SHALL store all login credentials in a single `users` table.
- FR-1.2 `users.role` SHALL be one of: `customer`, `designer`, `printer_provider`, `admin`. The set is fixed and closed for v1.2.
- FR-1.3 A user MAY have at most one `designer_profiles` row AND at most one `printer_provider_profiles` row; both are 1:1 to `users` via `user_id` FK.
- FR-1.4 No separate `customer` profile table exists. A customer is identified as a `users` row with `role='customer'`.
- FR-1.5 The v1.1 `role`, `permission`, `role_permission`, `user_role` tables are NOT used in v1.2; authorization in v1.2 is role-gating at the application layer.

### FR-2: Address Book
- FR-2.1 The system SHALL store user address books in `addresses`, referenced via `user_id`.
- FR-2.2 Multiple addresses per user are permitted.
- FR-2.3 `addresses` is the source of truth at the time of checkout ONLY. Once an order is created, the shipping fields on the order are immutable.

### FR-3: Design Catalog
- FR-3.1 A `design` SHALL belong to exactly one `designer_profiles.id` (`designer_id`).
- FR-3.2 A `design` SHALL reference exactly one `categories.id`. Categories are self-referencing via `parent_id` (nullable) and MAY form a tree.
- FR-3.3 `designs.status` SHALL be one of: `draft`, `published`, `archived`. The set is fixed and closed.
- FR-3.4 A design MAY be linked to zero or more `tags` via the `design_tag` junction. The `(design_id, tag_id)` pair SHALL be unique (enforced by `design_tag_pk` index).
- FR-3.5 `designs` supports soft-delete via `deleted_at`.

### FR-4: Product Catalog
- FR-4.1 A `product_template` SHALL belong to exactly one `printer_provider_profiles.id` (`printer_provider_id`).
- FR-4.2 `product_templates.type` is free-form text (printer-managed); the system SHALL NOT validate against a fixed list in v1.2.
- FR-4.3 `product_templates.specs` SHALL be a JSON column for printer-specific structured metadata (dimensions, materials supported, print method, etc.).
- FR-4.4 A `product_template` MAY have zero or more `product_variants`. Each variant stores `attributes` as JSON (e.g. size, color, material), a `price_delta`, an optional `sku`, and an `is_active` flag.
- FR-4.5 Variants that are inactive (`is_active=false`) SHALL NOT be selectable in cart or checkout.

### FR-5: Design–Product Mapping
- FR-5.1 A `design_product_mapping` SHALL reference exactly one `design_id` and one `product_template_id`.
- FR-5.2 The pair `(design_id, product_template_id)` SHALL be unique (enforced by `dpm_design_template_unique` index).
- FR-5.3 `final_price` SHALL be a non-negative decimal representing the customer-facing price for this design-on-this-template.
- FR-5.4 `preferred_printer_id` is informational in v1.2. It MAY reference any `printer_provider_profiles.id` (typically the printer that owns the referenced `product_template`, but the platform does not enforce this).
- FR-5.5 `created_at` / `updated_at` timestamps SHALL be maintained on the mapping.

### FR-6: Persistent Cart
- FR-6.1 `cart_items` rows SHALL belong to a single `user_id` (no anonymous carts).
- FR-6.2 Each cart item SHALL reference exactly one `design_product_mapping_id` and exactly one `user_id`.
- FR-6.3 `product_variant_id` is nullable — present only if the customer chose a specific variant.
- FR-6.4 `quantity` SHALL be a positive integer.
- FR-6.5 The same user MAY have multiple cart rows for the same `design_product_mapping` (e.g. different variants); each row stands alone.

### FR-7: Orders & Shipping Snapshot
- FR-7.1 An `order` SHALL reference exactly one `customer_id` (a `users.id` with `role='customer'`).
- FR-7.2 `orders.shipping_line1`, `shipping_city`, `shipping_country`, `shipping_phone` SHALL be captured at checkout time from the chosen `addresses` row (or fresh input) and SHALL NOT be updated thereafter. They are the source of truth post-checkout.
- FR-7.3 `shipping_address_id` is a reference for traceability to the original address row, not a live FK that drives display.
- FR-7.4 `orders.status` SHALL be one of: `pending`, `paid`, `processing`, `shipped`, `delivered`, `cancelled`. The set is fixed and closed.
- FR-7.5 `orders.total_amount` SHALL equal the sum of `order_items.unit_price * order_items.quantity` across all items in the order (enforced at the application layer).
- FR-7.6 `orders` supports soft-delete via `deleted_at`.

### FR-8: Order Items & Per-Item Fulfillment
- FR-8.1 Each `order_item` SHALL belong to exactly one `order_id`.
- FR-8.2 Each `order_item` SHALL reference exactly one `design_product_mapping_id` and MAY reference a `product_variant_id` (if chosen at order time).
- FR-8.3 Each `order_item` SHALL carry its own `printer_provider_id` identifying which printer fulfills that item, AND its own `status` enum.
- FR-8.4 `order_items.status` SHALL be one of: `pending`, `received`, `printing`, `printed`, `handed_off`, `cancelled`. The set is fixed and closed.
- FR-8.5 `unit_price` SHALL be snapshotted onto `order_items` at order creation time; subsequent edits to `design_product_mappings.final_price` SHALL NOT alter historical `order_items`.
- FR-8.6 A status change on one `order_item` SHALL NOT affect any other `order_item` in the same `order` (independence of fulfillment units).
- FR-8.7 The customer-facing overall `orders.status` MAY be derived at read-time from its `order_items.status` values; it is also stored explicitly on `orders` for query convenience.

### FR-9: Shipments & Delivery Companies
- FR-9.1 `delivery_companies` are first-class entities with `name`, `coverage_zones` (JSON), and `tracking_url_pattern` (string template for rendering tracking URLs).
- FR-9.2 A `shipment` SHALL reference exactly one `order_id`, one `printer_provider_id` (logical grouping), and one `delivery_company_id`.
- FR-9.3 An `order` MAY have multiple `shipment` rows (e.g., one per printer), each independent.
- FR-9.4 `shipments.status` SHALL be one of: `pending`, `shipped`, `delivered`, `returned`. The set is fixed and closed.
- FR-9.5 `shipped_at` and `delivered_at` SHALL be set when the corresponding status transitions occur.

### FR-10: Payments
- FR-10.1 A `payment` SHALL reference exactly one `order_id`. There is no polymorphic dual-FK in v1.2 (the v1.1 reference to `custom_order_request_id` is gone, since custom order requests no longer exist).
- FR-10.2 `payments.method` SHALL be one of: `cash_on_delivery`, `bank_transfer`, `card`. The set is fixed for v1.2.
- FR-10.3 `payments.status` SHALL be one of: `pending`, `confirmed`, `rejected`. The set is fixed and closed.
- FR-10.4 A confirmed payment SHALL record `confirmed_by_admin_id` (nullable FK to `users`) and `confirmed_at`.
- FR-10.5 Proof-of-payment images SHALL be attached via the `media` table using `model_type='Payment'`, `model_id=<payment.id>`, `collection_name='payment_proof'`.
- FR-10.6 `payments` supports soft-delete via `deleted_at`.

### FR-11: Media (polymorphic)
- FR-11.1 `media` rows SHALL carry `model_type` + `model_id` identifying the owner row, and a `collection_name`.
- FR-11.2 `collection_name` SHALL be one of: `mockup`, `print_file`, `payment_proof`, `attachment`. The set is fixed and closed.
- FR-11.3 The application layer SHALL ensure `(model_type, model_id)` points at an existing owner row.

### FR-12: Notifications (polymorphic)
- FR-12.1 `notifications` rows SHALL carry `notifiable_type` + `notifiable_id` identifying the recipient.
- FR-12.2 `type` is intentionally NOT a closed enum — new notification types SHALL be addable as features ship.
- FR-12.3 `data` (JSON) carries the notification payload; `read_at` SHALL be set when the user reads the notification.

### FR-13: Settings
- FR-13.1 `settings` is a generic key/value store (`key`, `value`) for application-level configuration (feature flags, default commission rates, etc.).
- FR-13.2 The system SHALL treat `settings.key` as a logical identifier. Uniqueness is NOT currently enforced at the schema level; the application layer SHOULD treat duplicate keys as undefined behavior.

### FR-14: Status Field Conventions
- FR-14.1 All closed-set status / category / method fields SHALL be `varchar` columns documented per the schema with a comment listing the valid set.
- FR-14.2 Adding a new valid value SHALL be a deliberate code change (not a data-only update), since no lookup-table fallback exists in v1.2.
- FR-14.3 `notifications.type` is the one exception — explicitly open-ended (FR-12.2).

---

## 3. Non-Functional Requirements

### NFR-1: Data Integrity
- All natural-key pair tables enforce uniqueness: `design_tag(design_id, tag_id)`, `design_product_mappings(design_id, product_template_id)`. An index on `categories(parent_id)` is provided for tree-traversal performance.
- Foreign keys are used throughout in preference to soft references, so referential integrity is enforced by the database. The polymorphic `media` and `notifications` tables are the explicit exceptions — they rely on application-layer owner validation.
- `users`, `designs`, `orders`, and `payments` support soft-delete via `deleted_at`. Profile tables (`designer_profiles`, `printer_provider_profiles`) and `addresses` do NOT independently soft-delete — deletion is cascaded from `users` at the application layer.

### NFR-2: Historical Accuracy / Auditability
- `order_items.unit_price` and `orders.shipping_*` fields are snapshotted at order time and SHALL remain accurate regardless of later edits to `design_product_mappings.final_price`, `addresses`, `product_variants.price_delta`, or any other upstream table.

### NFR-3: Independence of Fulfillment Units
- The system must guarantee, at the data-model level, that `order_items` under the same `order` have no structural dependency on one another's status. There is no shared `sub_order` row; each item's `status` is independent. A failure at one printer affects only its own `order_items` and the `shipment` rows that group them.

### NFR-4: Extensibility
- Polymorphic `media` and `notifications` tables allow new owner types without schema changes (only new `model_type` / `notifiable_type` strings, validated by the application layer).
- `product_templates.specs` (JSON) and `product_templates.type` (free text) absorb printer-specific metadata without schema changes.
- `design_product_mappings.preferred_printer_id` is the hook for future multi-printer offers on the same template.
- `notifications.type` is the open-ended extension point for new notification kinds.

---

## 4. Explicit Constraints Not Enforceable at Schema Level

The following business rules require application-layer enforcement, since they cannot be expressed as pure SQL constraints:

1. **Printer fulfilability** (FR-8.3): an `order_items.printer_provider_id` SHOULD equal the printer that owns the referenced `design_product_mapping.product_template_id`. The schema permits other values; the application validates.
2. **Variant activeness** (FR-4.4): `cart_items.product_variant_id` and `order_items.product_variant_id`, when set, MUST reference a variant with `is_active=true`.
3. **Cart/Order consistency** (FR-6, FR-7.5): referenced `design_product_mappings` and `product_variants` MUST exist; quantities MUST be positive; the sum of `order_items.unit_price * quantity` MUST equal `orders.total_amount`.
4. **Polymorphic owner validity** (FR-11.3, FR-12.1): `(media.model_type, media.model_id)` and `(notifications.notifiable_type, notifications.notifiable_id)` MUST reference an existing row.
5. **Preferred-printer consistency** (FR-5.4): in v1.2, `design_product_mappings.preferred_printer_id` is informational; the application MAY later enforce that the preferred printer equals the printer that owns the referenced `product_template_id`.
6. **Shipping snapshot immutability** (FR-7.2): once an `order` is created, its `shipping_line1` / `shipping_city` / `shipping_country` / `shipping_phone` fields SHALL NOT be rewritten by the application.
7. **Settings key uniqueness** (FR-13.2): the application layer SHOULD prevent duplicate keys; the schema does not enforce it in v1.2.

---

## 5. Traceability Note

Every requirement in Section 2 maps directly to a decision in PRD v1.2 Section 3. The v1.2 schema drops the v1.1 constructs for sub-orders, custom-order requests, full RBAC, capability/agreement tables, and saved-filter/column-preference tables; the FRs above cover only what remains in v1.2. Anything not listed here (refunds, single-designer restrictions, AI mockups, custom-order messaging, multi-shipment-per-batch) is deliberately excluded per PRD v1.2 Section 4.