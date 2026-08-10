# Permission Matrix — Print-on-Demand (POD) Platform
**Version:** 1.2
**Companion to:** `PRD-POD-Platform-Database-v1.2.md`, `SRS-POD-Platform-Database-v1.2.md`
**Scope:** every action a user can take against every public-facing resource, expressed against the four v1.2 roles.

---

## 1. Roles

The v1.2 platform uses **simple role-based auth** (PRD Decision 1, FR-1.2). A single `users.role` enum value drives every authorization decision in the application layer. There is **no fine-grained RBAC**, no permission table, no role-assignment table.

| Role | Who they are | Profile table |
|---|---|---|
| **Customer** | A registered buyer. The default role at signup. | _none_ — a customer is just a `users` row with `role='customer'` |
| **Designer** | A creator who uploads designs and maps them to product templates. | `designer_profiles` (1:1 to `users`) |
| **Printer / PSP** | A fulfillment business that owns product templates and ships goods. | `printer_provider_profiles` (1:1 to `users`) |
| **Admin** | Platform staff — manages users, confirms payments, oversees catalog & delivery companies. | _none_ — admin is a `users` row with `role='admin'` |

A user MAY have at most one `designer_profiles` row AND at most one `printer_provider_profiles` row (FR-1.3). Admin and customer have no profile row.

---

## 2. Scope of This Matrix

For each resource, the matrix lists **every action** a role can attempt, with the verdict:

- ✅ **Allow** — the action is part of normal product flow for this role.
- 👁️ **Read-only** — the role can view, but cannot mutate.
- 🔒 **Own only** — the role can act on records they own/created, but never on others'.
- ❌ **Deny** — the role must not perform this action (HTTP 403).
- ➖ **N/A** — the action is not part of the role's product surface (no UI, no endpoint).

"Own only" means: a Customer can read their own `orders`; a Designer can edit their own `designs`; a Printer can update `order_items` whose `printer_provider_id` matches their own profile. Cross-tenant access is always denied.

**Special IDs** — all 9 public-facing tables use UUID primary keys (FR-15). The matrix below never references an internal integer `id`; the UUID is the only identifier exposed externally. This means an attacker cannot enumerate records by guessing sequential IDs.

---

## 3. Resource-by-Resource Matrix

### 3.1 Users (`users`)

The `users` table itself is read-mostly through the API. Mutations are limited to self and admin.

| Action | Customer | Designer | Printer | Admin |
|---|---|---|---|---|
| Register (self-create) | ✅ | ✅ | ✅ | 🔒 (admin-created only) |
| Read own profile (`/me`) | ✅ | ✅ | ✅ | ✅ |
| Read other user's public profile (name, role, designer showcase) | ✅ | ✅ | ✅ | ✅ |
| Read other user's email / phone | ❌ | ❌ | ❌ | ✅ |
| Update own name / phone / password | ✅ | ✅ | ✅ | ✅ |
| Update own email | ✅ | ✅ | ✅ | ✅ |
| Update own role | ❌ | ❌ | ❌ | ✅ |
| Soft-delete self (account deletion) | ✅ | 🔒 block if designs with orders OR product templates still active | 🔒 block if product templates with active order items | ❌ (admin self-delete disabled) |
| Restore soft-deleted user | ❌ | ❌ | ❌ | ✅ |
| List all users (admin console) | ❌ | ❌ | ❌ | ✅ |
| Ban / suspend user | ❌ | ❌ | ❌ | ✅ |

**Notes**
- Email/phone are authorization-gated fields in `UserResource` — only self or admin sees them.
- A designer with placed orders cannot be soft-deleted (PRD Decision 21, `order_items.design_product_mapping_id` is `restrictOnDelete`). The application layer must block the request and explain why.
- A printer with active order items cannot be soft-deleted for the same reason.

---

### 3.2 Designer Profile (`designer_profiles`)

| Action | Customer | Designer | Printer | Admin |
|---|---|---|---|---|
| Create own designer profile | ❌ | ✅ (one per user) | ❌ | ✅ (on behalf) |
| Read designer profile (public showcase) | ✅ | ✅ | ✅ | ✅ |
| Update own designer bio / links | ❌ | ✅ | ❌ | ✅ |
| Update someone else's profile | ❌ | ❌ | ❌ | ✅ |
| Delete / soft-delete profile | ❌ | 🔒 block if active designs | ❌ | ✅ (cascade to designs) |
| List all designers (public catalog) | ✅ | ✅ | ✅ | ✅ |

---

### 3.3 Printer Provider Profile (`printer_provider_profiles`)

| Action | Customer | Designer | Printer | Admin |
|---|---|---|---|---|
| Create own printer profile | ❌ | ❌ | ✅ (one per user) | ✅ (on behalf) |
| Read printer profile (public) | ✅ | ✅ | ✅ | ✅ |
| Update own company info / capabilities | ❌ | ❌ | ✅ | ✅ |
| Update someone else's profile | ❌ | ❌ | ❌ | ✅ |
| Delete / soft-delete profile | ❌ | ❌ | 🔒 block if active product templates | ✅ |
| List all printers | ✅ | ✅ | ✅ | ✅ |

---

### 3.4 Addresses (`addresses`)

| Action | Customer | Designer | Printer | Admin |
|---|---|---|---|---|
| Create own address | ✅ | ✅ | ✅ | ✅ |
| Read own address list | ✅ | ✅ | ✅ | ✅ |
| Read someone else's address | ❌ | ❌ | ❌ | ✅ |
| Update own address | ✅ | ✅ | ✅ | ✅ |
| Delete own address | ✅ | ✅ | ✅ | ✅ |
| Choose address at checkout | ✅ | ➖ | ➖ | ➖ |

**Notes**
- A snapshot of the chosen address is captured onto `orders.shipping_*` at checkout (FR-7.2). The original `addresses` row remains editable, but the snapshot is immutable.

---

### 3.5 Categories (`categories`)

Categories are platform-managed reference data. Designers and customers pick from existing categories; only admins create/edit them.

| Action | Customer | Designer | Printer | Admin |
|---|---|---|---|---|
| List categories (browse tree) | ✅ | ✅ | ✅ | ✅ |
| Read category detail | ✅ | ✅ | ✅ | ✅ |
| Create category | ❌ | ❌ | ❌ | ✅ |
| Update category name / parent | ❌ | ❌ | ❌ | ✅ |
| Delete category | ❌ | ❌ | ❌ | ✅ (block if designs attached) |
| Move category in tree | ❌ | ❌ | ❌ | ✅ |

---

### 3.6 Tags (`tags`)

Tags are open vocabulary — designers can apply existing tags when uploading designs, and admins curate the catalog.

| Action | Customer | Designer | Printer | Admin |
|---|---|---|---|---|
| List tags (autocomplete) | ✅ | ✅ | ✅ | ✅ |
| Read tag detail | ✅ | ✅ | ✅ | ✅ |
| Apply tag to own design | ➖ | ✅ (existing tags) | ➖ | ✅ |
| Create new tag | ❌ | 🔒 request only (suggested for review) | ❌ | ✅ |
| Update tag name | ❌ | ❌ | ❌ | ✅ |
| Delete tag | ❌ | ❌ | ❌ | ✅ (block if designs attached) |

**Note** — designer-created tags flow: a designer enters a new tag, the application stores it as `pending_review`, and an admin promotes it to the public catalog. v1.2 keeps this simple; the curation step is optional.

---

### 3.7 Designs (`designs`)

Designs are owned by designers; the catalog is publicly browsable.

| Action | Customer | Designer | Printer | Admin |
|---|---|---|---|---|
| Browse published designs | ✅ | ✅ | ✅ | ✅ |
| Read design detail (published) | ✅ | ✅ | ✅ | ✅ |
| Read design detail (draft / archived) | 🔒 only if designer is self | ✅ own only | ❌ | ✅ |
| Create new design | ❌ | ✅ | ❌ | ✅ |
| Update own design (title, category, tags) | ❌ | ✅ own only | ❌ | ✅ |
| Change own design status (draft → published → archived) | ❌ | ✅ own only | ❌ | ✅ |
| Upload mockup images (media) | ❌ | ✅ own only | ❌ | ✅ |
| Upload print file (media) | ❌ | ✅ own only | ❌ | ✅ |
| Soft-delete own design | ❌ | ✅ own only (block if order_items reference it) | ❌ | ✅ |
| Restore soft-deleted design | ❌ | ✅ own only | ❌ | ✅ |
| Change designer ownership | ❌ | ❌ | ❌ | ✅ (transfer, with audit log) |

**Notes**
- Only `status='published'` designs appear in the public catalog.
- A design referenced by `order_items.design_product_mapping_id` (which is `restrictOnDelete`) cannot be soft-deleted — historical order integrity wins.

---

### 3.8 Product Templates (`product_templates`)

Product templates are owned by printers; designers don't mutate them.

| Action | Customer | Designer | Printer | Admin |
|---|---|---|---|---|
| Browse all active templates | ✅ | ✅ | ✅ | ✅ |
| Read template detail | ✅ | ✅ | ✅ | ✅ |
| Create template | ❌ | ❌ | ✅ own only | ✅ |
| Update template (name, type, base_cost, specs) | ❌ | ❌ | ✅ own only | ✅ |
| Soft-delete own template | ❌ | ❌ | ✅ own only (block if order_items reference it) | ✅ |
| Restore soft-deleted template | ❌ | ❌ | ✅ own only | ✅ |
| Create variant on template | ❌ | ❌ | ✅ own only | ✅ |
| Update variant on template | ❌ | ❌ | ✅ own only | ✅ |
| Deactivate variant (`is_active=false`) | ❌ | ❌ | ✅ own only | ✅ |
| Soft-delete variant | ❌ | ❌ | ✅ own only (block if order_items reference it) | ✅ |

**Notes**
- Only `is_active=true` variants are selectable in cart and checkout (FR-4.5).
- A variant or template referenced by `order_items` is `restrictOnDelete` — the printer cannot delete it; they must keep it active.

---

### 3.9 Design–Product Mappings (`design_product_mappings`)

Mappings tie a design to a product template with a customer-facing price. They are owned by the designer (the design side) but reference a printer's template.

| Action | Customer | Designer | Printer | Admin |
|---|---|---|---|---|
| Read mapping (visible when design is published) | ✅ | ✅ | ✅ | ✅ |
| Create mapping (offer design on template) | ❌ | ✅ for own design, on any active template | ❌ | ✅ |
| Update own mapping (final_price) | ❌ | ✅ own only | ❌ | ✅ |
| Update someone else's final_price | ❌ | ❌ | ❌ | ✅ |
| Change preferred_printer_id | ❌ | ✅ own only | ❌ | ✅ |
| Soft-delete own mapping | ❌ | ✅ own only (synced with design soft-delete) | ❌ | ✅ |
| Validate mapping to active template | ❌ | ✅ (only `is_active=true`) | n/a | ✅ |

**Notes**
- Uniqueness is enforced on `(design_id, product_template_id)` — a designer cannot double-map the same design to the same template.
- Edits to `final_price` only affect FUTURE cart-items / orders. Past `order_items.unit_price` is immutable (FR-8.5, NFR-2).

---

### 3.10 Cart (`cart_items`)

The cart is owned by the customer. No anonymous carts (FR-6.1).

| Action | Customer | Designer | Printer | Admin |
|---|---|---|---|---|
| View own cart | ✅ | ✅ | ✅ | ✅ |
| Add item to own cart | ✅ | ✅ | ✅ | ✅ |
| Update own cart item quantity | ✅ own only | ✅ own only | ✅ own only | ✅ (any) |
| Remove own cart item | ✅ own only | ✅ own only | ✅ own only | ✅ (any) |
| Add to someone else's cart | ❌ | ❌ | ❌ | ❌ (cart is private) |
| Merge quantity on duplicate line | ✅ (automatic) | ✅ (automatic) | ✅ (automatic) | ✅ |
| Cart persists across sessions | ✅ | ✅ | ✅ | ✅ |

**Notes**
- A duplicate `(user_id, design_product_mapping_id, product_variant_id)` triple is collapsed via the `cart_items_unique_line` index (FR-6.5), and the application layer upserts quantity on conflict (FR-6.6).
- A user can only add a mapping whose design is published (`status='published'`) and whose variant (if chosen) is `is_active=true`.

---

### 3.11 Orders (`orders`)

Orders are owned by the customer; printers and admins fulfill/administer.

| Action | Customer | Designer | Printer | Admin |
|---|---|---|---|---|
| Place order from cart | ✅ | ✅ | ✅ | ➖ (admin can place on behalf) |
| View own order | ✅ own only | ❌ | ❌ | ✅ |
| View order containing own design's items | ❌ | ✅ (only ship-to lines, no customer PII) | ❌ | ✅ |
| View order containing own printer's items | ❌ | ❌ | ✅ own only | ✅ |
| View all orders (admin) | ❌ | ❌ | ❌ | ✅ |
| Update order status (overall) | ❌ | ❌ | ❌ | ✅ |
| Cancel own order (before `processing`) | ✅ own only | ❌ | ❌ | ✅ |
| Cancel order mid-fulfillment | ❌ | ❌ | ❌ | ✅ |
| Edit shipping fields after checkout | ❌ | ❌ | ❌ | ❌ (FR-7.2 snapshot is immutable) |
| Soft-delete order | ❌ | ❌ | ❌ | ✅ |
| Restore soft-deleted order | ❌ | ❌ | ❌ | ✅ |

**Notes**
- A printer sees ONLY the orders that contain at least one `order_item` with `printer_provider_id = self`. The `orders` resource is loaded with `whereHas('items', fn ($q) => $q->where('printer_provider_id', self))` — full customer record is not exposed.
- A designer sees ONLY the orders that contain at least one `order_item` whose design they own. Customer PII (name, email, phone) is redacted; only the shipping snapshot is shown.
- The customer-facing overall `orders.status` is derived from `order_items.status` values (FR-8.7); the explicit column is for query convenience.

---

### 3.12 Order Items (`order_items`)

Order items are the unit of fulfillment. Status transitions are owned by the printer; the customer can only request cancellation.

| Action | Customer | Designer | Printer | Admin |
|---|---|---|---|---|
| View own order items | ✅ (via order) | ❌ | ❌ | ✅ |
| View order items for own designs | ❌ | ✅ (without customer PII) | ❌ | ✅ |
| View order items for own printer | ❌ | ❌ | ✅ own only | ✅ |
| Update order item status: `pending` → `received` | ❌ | ❌ | ✅ own only | ✅ |
| Update order item status: `received` → `printing` | ❌ | ❌ | ✅ own only | ✅ |
| Update order item status: `printing` → `printed` | ❌ | ❌ | ✅ own only | ✅ |
| Update order item status: `printed` → `handed_off` | ❌ | ❌ | ✅ own only | ✅ |
| Cancel order item before `printed` | ✅ own only (full order) | ❌ | ✅ own only | ✅ |
| Cancel order item after `handed_off` | ❌ | ❌ | ❌ | ✅ |
| Edit quantity / unit_price after order | ❌ | ❌ | ❌ | ❌ (snapshot is immutable) |
| Edit printer_provider_id after order | ❌ | ❌ | ❌ | ✅ (only if `pending`) |
| Soft-delete order item | ❌ | ❌ | ❌ | ✅ (with order) |

**Notes**
- Status transitions are unidirectional. Skipping states is allowed (admin) but tracked in audit logs.
- Once `unit_price` is snapshotted, it stays — even if the designer changes `final_price` afterwards.

---

### 3.13 Delivery Companies (`delivery_companies`)

Delivery companies are platform-managed reference data; only admins mutate them.

| Action | Customer | Designer | Printer | Admin |
|---|---|---|---|---|
| List delivery companies | 👁️ (visible on checkout / shipments) | 👁️ | ✅ (for fulfillment) | ✅ |
| Read delivery company detail | 👁️ | 👁️ | ✅ | ✅ |
| Create delivery company | ❌ | ❌ | ❌ | ✅ |
| Update coverage_zones / tracking_url_pattern | ❌ | ❌ | ❌ | ✅ |
| Soft-delete delivery company | ❌ | ❌ | ❌ | ✅ (block if shipments reference) |

---

### 3.14 Shipments (`shipments`)

Shipments are created by printers when they hand off items to a delivery company.

| Action | Customer | Designer | Printer | Admin |
|---|---|---|---|---|
| View own shipments | ✅ (via order) | ❌ | ✅ own only | ✅ |
| View shipments for own design's items | ❌ | ✅ (no customer PII) | ❌ | ✅ |
| Create shipment | ❌ | ❌ | ✅ own only (for own order_items) | ✅ |
| Update shipment status: `pending` → `shipped` | ❌ | ❌ | ✅ own only | ✅ |
| Update shipment status: `shipped` → `delivered` | ❌ | ❌ | ✅ own only | ✅ |
| Update shipment status: `delivered` → `returned` | ❌ | ❌ | ✅ own only | ✅ |
| Edit tracking_number | ❌ | ❌ | ✅ own only (before `shipped`) | ✅ |
| Set `shipped_at` / `delivered_at` | ➖ (auto) | ➖ | ✅ (auto on status change) | ✅ |
| Delete shipment | ❌ | ❌ | 🔒 block if `delivered` | ✅ |

**Notes**
- A shipment is a logical grouping: one per printer per order. A printer may have multiple shipments across many orders but never two for the same order.
- Tracking URLs are rendered from `delivery_companies.tracking_url_pattern` (e.g. `https://dhl.com/track/{tracking_number}`).

---

### 3.15 Payments (`payments`)

Payments are order-scoped. Customers submit; admins confirm.

| Action | Customer | Designer | Printer | Admin |
|---|---|---|---|---|
| View own payment status | ✅ own only | ❌ | ❌ | ✅ |
| View payments for own printer's orders | ❌ | ❌ | ✅ own only (status only, no method details) | ✅ |
| Submit payment for own order | ✅ own only | ➖ | ➖ | ✅ (on behalf) |
| Upload proof-of-payment (media) | ✅ own only | ➖ | ➖ | ✅ |
| Confirm payment (`pending` → `confirmed`) | ❌ | ❌ | ❌ | ✅ |
| Reject payment (`pending` → `rejected`) | ❌ | ❌ | ❌ | ✅ |
| Edit payment method after submission | ❌ | ❌ | ❌ | ✅ (only if `pending`) |
| Soft-delete payment | ❌ | ❌ | ❌ | ✅ |

**Notes**
- `confirmed_by_admin_id` and `confirmed_at` are populated by the admin confirmation endpoint.
- Proof-of-payment images live in the polymorphic `media` table with `collection_name='payment_proof'`.

---

### 3.16 Media (`media`)

Polymorphic. The owner of the media row controls its lifecycle.

| Action | Customer | Designer | Printer | Admin |
|---|---|---|---|---|
| Read media attached to a published design | ✅ | ✅ | ✅ | ✅ |
| Read media attached to a non-published design | ❌ | ✅ own only | ❌ | ✅ |
| Read payment_proof on own payment | ✅ own only | ❌ | ❌ | ✅ |
| Upload mockup image to design | ❌ | ✅ own only | ❌ | ✅ |
| Upload print_file to design | ❌ | ✅ own only | ❌ | ✅ |
| Upload payment_proof to payment | ✅ own only | ➖ | ➖ | ✅ |
| Upload attachment to any owner | ❌ | ❌ | ❌ | ✅ |
| Delete own media | ❌ | ✅ own only | ✅ own only | ✅ |
| Delete other people's media | ❌ | ❌ | ❌ | ✅ |

**Notes**
- `collection_name` is a closed enum: `mockup`, `print_file`, `payment_proof`, `attachment`.
- A `mockup` is shown to customers; a `print_file` is private to the printer fulfilling the item.

---

### 3.17 Notifications (`notifications`)

Polymorphic. Users see their own.

| Action | Customer | Designer | Printer | Admin |
|---|---|---|---|---|
| View own notifications list | ✅ | ✅ | ✅ | ✅ |
| Mark own notification as read | ✅ | ✅ | ✅ | ✅ |
| Mark all own as read | ✅ | ✅ | ✅ | ✅ |
| View other people's notifications | ❌ | ❌ | ❌ | ✅ |
| Send notification to a user | ❌ | ❌ | ❌ | ✅ (system-emitted) |
| Send notification to a group (broadcast) | ❌ | ❌ | ❌ | ✅ |
| Delete own notification | ✅ | ✅ | ✅ | ✅ |

**Notes**
- `type` is intentionally an open-ended string (FR-12.2) — new notification types ship with new features.
- `data` (JSON) carries the payload (`{order_id, message, action_url, ...}`).

---

### 3.18 Settings (`settings`)

Platform-managed key/value configuration. Only admins write; everyone reads.

| Action | Customer | Designer | Printer | Admin |
|---|---|---|---|---|
| Read public setting (e.g. `platform.commission_rate`) | 👁️ | 👁️ | 👁️ | ✅ |
| Read internal setting (e.g. `feature_flags.beta_search`) | ➖ | ➖ | ➖ | ✅ |
| Create setting | ❌ | ❌ | ❌ | ✅ |
| Update setting value | ❌ | ❌ | ❌ | ✅ |
| Delete setting | ❌ | ❌ | ❌ | ✅ |

**Notes**
- `settings.key` is logical — application layer is responsible for treating duplicate keys as a bug (FR-13.2).
- Some settings are read-only at runtime via the `Setting::get()` helper but only admin endpoints can mutate.

---

## 4. Cross-Cutting Policies

These rules apply across every resource and are enforced in policies or middleware, not in any single resource's matrix above.

### 4.1 Soft-Delete Policy (PRD Decision 21, FR-3.5, FR-4.8, FR-5.7, FR-7.6, FR-8.8, FR-10.6)

| Lifecycle-bearing (soft-delete) | Ephemeral/derived (hard-delete or cascade) |
|---|---|
| `users`, `designs`, `product_templates`, `product_variants`, `design_product_mappings`, `orders`, `order_items`, `payments` | `cart_items`, `shipments`, `addresses`, `designer_profiles`, `printer_provider_profiles`, `tags`, `categories`, `delivery_companies`, junction tables, polymorphic tables |

**Cascade rule** — soft-deleting a parent business entity cascades to soft-deletable children where FKs are declared `cascadeOnDelete`. Tables with `restrictOnDelete` block the cascade and preserve historical references.

**Practical consequence** — a designer with placed orders cannot be soft-deleted; a printer with active order items cannot be soft-deleted. The application must block the delete and surface the reason.

### 4.2 Authorization Layer

- **Role-gating at the application layer** (FR-1.5) — implemented as Laravel Policies (`app/Policies/{Model}Policy.php`) wired in `AuthServiceProvider`.
- **Cross-tenant access denied** — every cross-tenant query routes through `BelongsToTenant` scope or a policy `view()` check.
- **Public ID exposure** — the 9 public-facing tables expose their UUID as `id` in every API response. Internal integer IDs never appear.

### 4.3 Audit & Event Log

| Event | Who emits | Who sees |
|---|---|---|
| Order placed | system | customer, admin |
| Payment confirmed | admin | customer, admin |
| Order item status transitioned | printer | customer (their own), admin |
| Shipment dispatched | printer | customer (their own), admin |
| Shipment delivered | printer | customer (their own), admin |
| Design published | designer | public |
| Account deleted | user | admin |

All events also fire a `Notification` row to the relevant user, with `type` matching the event class name.

### 4.4 API Endpoint Surface (informational)

| Verb | Path pattern | Allowed roles |
|---|---|---|
| `POST /api/auth/register` | public | anyone |
| `POST /api/auth/login` | public | anyone |
| `GET /api/me` | authenticated | any role |
| `PATCH /api/me` | authenticated | any role |
| `GET /api/designs` | public | anyone |
| `POST /api/designs` | designer | designer, admin |
| `PATCH /api/designs/{uuid}` | designer | designer (own), admin |
| `DELETE /api/designs/{uuid}` | designer | designer (own), admin |
| `GET /api/cart` | customer | any role |
| `POST /api/cart/items` | customer | any role |
| `PATCH /api/cart/items/{id}` | customer | self, admin |
| `DELETE /api/cart/items/{id}` | customer | self, admin |
| `POST /api/orders` | customer | any role |
| `GET /api/orders` | customer | self, admin |
| `GET /api/orders/{uuid}` | customer | self, designer (own designs), printer (own items), admin |
| `PATCH /api/orders/{uuid}/items/{id}/status` | printer | printer (own), admin |
| `POST /api/orders/{uuid}/payments` | customer | self, admin |
| `PATCH /api/payments/{uuid}/confirm` | admin | admin only |
| `PATCH /api/payments/{uuid}/reject` | admin | admin only |
| `POST /api/shipments` | printer | printer (own), admin |
| `PATCH /api/shipments/{id}` | printer | printer (own), admin |
| `GET /api/notifications` | authenticated | self |
| `PATCH /api/notifications/{id}/read` | authenticated | self |
| `GET /api/settings` | public | anyone (filtered) |
| `PATCH /api/settings/{id}` | admin | admin only |
| `GET /api/admin/users` | admin | admin only |
| `PATCH /api/admin/users/{uuid}` | admin | admin only |

---

## 5. Forbidden Actions (Always Deny)

These actions are forbidden regardless of role:

- **Reading another user's email, phone, or address** — except admin.
- **Editing `unit_price` on `order_items` after order creation** — even admin cannot rewrite history (NFR-2).
- **Editing `shipping_*` fields on `orders` after order creation** — the snapshot is immutable (FR-7.2).
- **Deleting a `design` with `order_items` referencing it** — `restrictOnDelete` blocks the cascade.
- **Deleting a `product_template` or `product_variant` with `order_items` referencing it** — same.
- **Soft-deleting a `users` row with active order_items** — application layer blocks.
- **Adding a `cart_item` with `design_product_mapping_id` referring to a `design` whose `status != 'published'`** — application layer blocks.
- **Adding a `cart_item` with `product_variant_id` referring to a variant with `is_active=false`** — FR-4.5.
- **Setting `preferred_printer_id` to a printer that doesn't own the referenced `product_template`** — informational in v1.2, but the application MAY enforce.
- **Bypassing `cart_items_unique_line` by inserting duplicate rows** — the index + generated column block at DB level.

---

## 6. Edge Cases & Workflow Examples

### 6.1 Place an order end-to-end

| Step | Actor | Action |
|---|---|---|
| 1 | Customer | `POST /api/cart/items` (mapping X, variant Y, qty 2) |
| 2 | Customer | `POST /api/cart/items` (mapping Z, variant null, qty 1) |
| 3 | Customer | `POST /api/orders` (with shipping address ID) |
| → system | — | Snapshots shipping fields, creates one `orders` row, two `order_items` rows (one printer per item), one `payment` (status `pending`) |
| 4 | Customer | uploads proof-of-payment via `media` (collection=`payment_proof`) |
| 5 | Admin | `PATCH /api/payments/{uuid}/confirm` |
| 6 | Printer A | `PATCH /api/orders/{uuid}/items/{id}/status` from `pending` → `received` → `printing` → `printed` → `handed_off` |
| 7 | Printer A | `POST /api/shipments` (order, printer A, delivery company, tracking number) |
| → system | — | Sets `shipped_at`, customer notified |
| 8 | Printer A | `PATCH /api/shipments/{id}` to `delivered` |
| → system | — | Sets `delivered_at`, customer notified, order derived status becomes `delivered` |

### 6.2 Designer attempts to delete a design with placed orders

| Step | Actor | Action | Result |
|---|---|---|---|
| 1 | Designer A | `DELETE /api/designs/{uuid}` | ❌ 403 — design has 3 order_items referencing mappings |
| → system | — | Returns: `"Design has placed orders and cannot be deleted. Soft-archive instead."` | |
| 2 | Designer A | `PATCH /api/designs/{uuid}` with `status=archived` | ✅ 200 — design moves to archived, no longer in catalog, but order_items retain their mappings |

### 6.3 Customer attempts to view another customer's order

| Step | Actor | Action | Result |
|---|---|---|---|
| 1 | Customer B | `GET /api/orders/{uuid-for-customer-A's-order}` | ❌ 403 — order does not belong to Customer B |
| → system | — | Returns: 403 with no leak | |

### 6.4 Printer attempts to view a non-own order

| Step | Actor | Action | Result |
|---|---|---|---|
| 1 | Printer X | `GET /api/orders/{uuid}` (order has only Printer Y's items) | ❌ 403 — no items reference Printer X |
| 2 | Printer X | `GET /api/orders?printer=me` | ✅ 200 — only orders with Printer X's items |

### 6.5 Designer attempts to view a customer's email via an order

| Step | Actor | Action | Result |
|---|---|---|---|
| 1 | Designer A | `GET /api/orders/{uuid}` for own design's customer | ✅ 200 — but `customer.email` is filtered out |
| → system | — | OrderResource returns customer name + shipping snapshot only; email/phone omitted | |

---

## 7. Versioning Notes

- **v1.2** — this matrix. Reflects 20 platform tables, 4 roles, UUID-as-PK on 9 public tables, simple role-based auth (no RBAC permissions table).
- **Backward-incompatible changes from v1.1** — RBAC, designer_provider_agreement, printer_capability, custom_order_request, sub_order, lookup-table statuses are all gone. Anything tied to those v1.1 tables (e.g. role-assignment UI) is removed.
- **Anticipated future changes** — refunds workflow, designer–PSP agreements, multi-shipment-per-batch, fine-grained RBAC. These would expand this matrix; current scope is deliberately limited to v1.2.
