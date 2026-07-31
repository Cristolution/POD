# Product Requirements Document (PRD)
## Print-on-Demand (POD) Platform — Database & Core Domain Layer
**Version:** 1.2
**Scope of this document:** the data layer underpinning the platform (schema-driving decisions), not UI/UX.
**Companion to:** `SRS-POD-Platform-Database-v1.2.md`
**Reference schema:** `doc/database.dbdiagram.txt` (DBML) and `doc/POD 1.2.sql` — 20 platform tables (plus Laravel auth/cache/queue defaults).

---

## 1. Purpose & Vision

Build a designer-centric Print-on-Demand marketplace. A Designer uploads a design and offers it across one or more physical Product Templates — mugs, shirts, posters, etc. — that are owned and fulfilled by Print Service Providers (PSPs). At checkout, the platform routes each item to a PSP that supports the chosen product; the Customer experiences a single storefront and a single checkout, unaware of (or only lightly exposed to) the backend routing complexity.

The platform takes a fee on each order. The v1.2 schema records a single `unit_price` snapshot per `order_item`; the application layer is responsible for deriving designer payouts and the platform's cut from that snapshotted value. Historical order data remains reconstructable for accounting because `unit_price` is snapshotted, not recalculated.

---

## 2. Actors

| Actor | Description |
|---|---|
| **Customer** | Browses designs, maintains a persistent cart and address book, places orders. |
| **Designer** | Uploads designs, assigns categories/tags, maps designs to product templates, sets the customer-facing price. |
| **Print Service Provider (PSP)** | A registered business that owns product templates (with variants) and physically produces/ships goods. |
| **Admin / Platform Staff** | Manages users, confirms payments, oversees catalog and delivery-company setup. |

---

## 3. Core Product Decisions (locked, v1.2)

These explicitly shape the v1.2 schema. Decisions 1–15 from v1.1 are either kept, evolved, or removed; the change is noted where relevant.

1. **Auth is simple role-based, not full RBAC.** A single `users` table carries a `role` enum (`customer`, `designer`, `printer_provider`, `admin`). Designer and Printer Provider profiles are 1:1 to a `users` row via separate profile tables; there is no `customer` profile table — a customer is just a `users` row with `role='customer'`. The v1.1 `role` / `permission` / `role_permission` / `user_role` tables are gone.
2. **Designers upload designs** with a category (self-referencing tree via `categories.parent_id`) and zero-or-more `tags` (via `design_tag`). Designs carry a lifecycle status (`draft`, `published`, `archived`) and support soft-delete — see Decision 21 for the full soft-delete policy.
3. **Product Templates are owned by Printers, not Designers.** Each `product_template` belongs to one `printer_provider_profile` and represents a printable item with `type` (free-form text, printer-managed), `base_cost`, and arbitrary `specs` (JSON). There is no shared "materials" catalog in v1.2.
4. **Product Variants** describe the choices within a template (size, color, material, etc.) as JSON `attributes` plus a `price_delta`, with an `is_active` flag and optional `sku`.
5. **Designs are mapped to Product Templates** via `design_product_mappings`. Each mapping carries a `final_price` (the customer-facing price for that design-on-that-template) and a `preferred_printer_id` (informational hint — typically the printer that owns the referenced template). Uniqueness is enforced on `(design_id, product_template_id)`. This replaces the v1.1 `design_material` concept.
6. **Carts are persistent** (`cart_items`). A user has many cart rows over time, each referencing a `design_product_mapping` and (optionally) a `product_variant` plus a `quantity`. There is no "one-designer-per-cart" rule.
7. **Shipping address is snapshotted on the order.** At checkout, the order captures `shipping_line1`, `shipping_city`, `shipping_country`, `shipping_phone` directly. The original `addresses` row remains editable, but is not the source of truth post-checkout. `orders.shipping_address_id` is retained only as a traceability reference.
8. **No sub-orders.** Each `order_item` carries its own `printer_provider_id` and `status` (per-item fulfillment: `pending`, `received`, `printing`, `printed`, `handed_off`, `cancelled`). Items that share a printer are a logical grouping only — there is no enforced batch entity. The v1.1 `sub_order` (and any `fulfillment_log`) is gone.
9. **One order can have multiple shipments.** A `shipment` references the parent `order_id`, a `printer_provider_id` (logical grouping), a registered `delivery_company`, a `tracking_number`, and its own status (`pending`, `shipped`, `delivered`, `returned`) plus `shipped_at` / `delivered_at` timestamps.
10. **Delivery companies are first-class entities** (`delivery_companies`) with `coverage_zones` (JSON) and `tracking_url_pattern` (string template for rendering tracking URLs). This was out of scope in v1.1.
11. **Payments are simple and order-scoped.** A `payment` row references exactly one `order_id` (no polymorphic dual-FK to a custom-order-request, which no longer exists), with a `method` enum (`cash_on_delivery`, `bank_transfer`, `card`) and a `status` enum (`pending`, `confirmed`, `rejected`). Admin confirmation is recorded via `confirmed_by_admin_id` and `confirmed_at`. Proof-of-payment images attach via the polymorphic `media` table.
12. **Media is polymorphic** — `media` rows carry `model_type` + `model_id` + `collection_name` (enum: `mockup`, `print_file`, `payment_proof`, `attachment`). Any current or future entity can own files without schema changes.
13. **Notifications are polymorphic** — `notifiable_type` + `notifiable_id` + `type` + JSON `data` + `read_at`. The `type` is intentionally NOT a closed enum — new notification types ship with new features.
14. **Status fields are inline varchar enums** (documented per column), not lookup tables. New values require a code change, not just a new row. The v1.1 `*_status` lookup tables are gone.
15. **Custom Order Requests / negotiated messaging are NOT in v1.2.** Explicitly cut from v1.1 — flagged as a future feature. There is no `custom_order_request`, no `custom_order_variant`, no `custom_request_message` / thread log.
16. **Designer–PSP agreement tracking is NOT in v1.2.** v1.1's `designer_provider_agreement` is gone. Relationships are implicit (the printer that owns the product template is the fulfiller; `design_product_mappings.preferred_printer_id` records routing preference).
17. **Printer capability tables are NOT in v1.2.** v1.1's `printer_capability` is gone. Any printer offering a given product_template can fulfil items mapped to it.
18. **No saved filters / column preferences / activity log.** The v1.1 admin-list-view tables are gone — not a v1.2 concern.
19. **Settings is a generic key/value** table for application-level configuration (feature flags, default commission rates, etc.).
20. **Historical price integrity** — `unit_price` is snapshotted onto `order_items` at order time. Subsequent edits to `design_product_mappings.final_price` never rewrite historical orders.
21. **Soft-delete is applied to lifecycle-bearing business entities.** Tables with `deleted_at`: `users`, `designs`, `product_templates`, `product_variants`, `design_product_mappings`, `orders`, `order_items`, `payments`. Ephemeral/derived/immutable tables (`cart_items`, `shipments`, `addresses`, `designer_profiles`, `printer_provider_profiles`, `tags`, `categories`, `delivery_companies`, junction tables, polymorphic tables) do NOT soft-delete — they cascade from a parent, hard-delete, or are immutable history.
22. **The 9 public-facing tables use UUID as their primary key (Pattern A).** Sequential `bigint` IDs leak enumeration vectors and business intelligence. The 9 public-facing tables — `users`, `designer_profiles`, `printer_provider_profiles`, `designs`, `design_product_mappings`, `product_templates`, `product_variants`, `orders`, `payments` — use `CHAR(36)` UUIDs as their `id` (no separate `uuid` column). Cascade: every FK column referencing one of these tables MUST also be `uuid` type. Small / internal tables (`tags`, `categories`, `design_tag`, `delivery_companies`, `addresses`, `cart_items`, `order_items`, `shipments`, `media`, `settings`, `notifications`) keep plain `bigint` `id` PKs; their FK columns to UUID tables are `uuid` type. Eloquent's `HasUuids` trait populates `id` on create (overriding `uniqueIds()` to return `['id']`).
23. **`cart_items` uses a STORED generated column to enforce unique lines.** SQLite and MySQL treat `NULL` as distinct in unique indexes, which silently allows duplicate `(user, mapping, NULL variant)` rows. A STORED generated column `variant_key = COALESCE(product_variant_id, 0)` provides the dedup invariant at the DB level. Application layer upserts/merges quantities on conflict rather than relying on insert-fail.
24. **Explicit indexes on FK and query-hot columns are required for cross-DB performance.** SQLite does not auto-index FK columns (MySQL InnoDB does). For consistent performance, every FK column that drives a list query gets an explicit `$table->index('column')` declaration after the FK. Status enums and `deleted_at` columns are indexed for admin/dashboard filtering.

---

## 4. Out of Scope (v1.2)

- Custom Order Requests, in-thread messaging between customer and designer
- Sub-orders as separate batch entities (per-item fulfillment is the model)
- Single-designer-per-order / single-designer cart restrictions
- Designer–PSP formal agreement tracking
- Printer capability tables (any printer owning a template can fulfil it)
- Refunds workflow (a `cancelled` status exists on order_items and orders, but no separate refund accounting)
- Fine-grained RBAC with permissions, role assignments, or permission assignments
- Admin list-view preferences (`saved_filter`, `table_column_preference`)
- AI-generated designs or automatic mockup generation
- Multiple source-file formats per design as user-uploaded files (the platform accepts one base file per design; the polymorphic `media` table with `collection_name='print_file'` accommodates future file-format expansion without schema changes)
- Multi-shipment-per-batch (one shipment per logical printer grouping; items may ship in boxes but the schema doesn't model sub-shipments)

---

## 5. Success Criteria

- A Designer can upload a design, tag and categorize it, and map it to one or more product templates with independent pricing.
- A Customer can add items (potentially from multiple designers) to a persistent cart and check out in one order.
- The shipping address captured at checkout remains on the order even if the user later edits or deletes their address-book entry.
- An order containing items from multiple printers splits into independent shipments — one per printer — each with its own tracking. A failure or delay at one printer does not block the others.
- `unit_price` on historical `order_items` remains stable even after designers change their listed prices.
- Proof-of-payment images attach to payments through the polymorphic `media` table; tracking URLs render from `delivery_companies.tracking_url_pattern`.
- Public-facing resources (users, designs, product templates, variants, mappings, orders, payments) are addressable by UUID in URLs and external API responses; sequential integer `id` values are never exposed externally.
- A customer adding the same design-to-product mapping (with or without a chosen variant) twice does not create duplicate cart rows — the generated `variant_key` column enforces one-line-per-mapping+variant.

---

## 6. Key Assumptions

- A printer can fulfil any `order_item` whose `printer_provider_id` matches the printer's profile AND whose `design_product_mapping.product_template_id` belongs to that same printer (enforced at the application layer — there is no DB constraint tying `order_items.printer_provider_id` to the product_template's owning printer).
- `product_templates.type` is free-form text managed by the printer; the platform does not validate against a fixed list of product types in v1.2.
- Platform fee and designer payout are derived from the snapshotted `unit_price` by the application layer; the v1.2 schema does not decompose a unit_price into sub-amounts.
- `preferred_printer_id` on `design_product_mappings` is informational in v1.2 (it MAY equal the printer that owns the template, or be NULL); the platform does not enforce "preferred printer must own the template."
- A `design_product_mapping` may reference a `product_template` owned by any printer — the application layer is responsible for ensuring the mapping's `preferred_printer_id` is consistent with the template's owning printer if/when that rule is introduced.
- Soft-deleting a parent business entity (e.g. a designer) cascades to soft-deletable children (`designs`, `design_product_mappings`, `product_templates`, `product_variants`) where FKs are declared `cascadeOnDelete`. Tables with `restrictOnDelete` (notably `order_items.design_product_mapping_id`) block the cascade, preserving historical order references — a designer with placed orders cannot be soft-deleted.
- The application layer populates the `id` (UUID) on create via Laravel's `HasUuids` trait with `uniqueIds()` overridden to return `['id']`. The DB-level primary key check will reject collisions / duplicates.