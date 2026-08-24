# POD Platform — User Guide

The current build (Phase 4 complete, branch `local-smart-shot`) ships **45 customer/designer/printer web pages** + **109 JSON API endpoints** + a **Filament admin panel** at `/admin/*` (13 resources, KPI dashboard, 8 report pages). This is the fast handful: what each role can reach, and where.

**Theme:** Sand + Coral brutalist — 3-5px borders, no shadows, no rounded corners, Archivo Black + Work Sans + Space Mono.

---

## Roles at a glance

| Role | How to get one | Web prefix | API prefix |
|------|---------------|------------|------------|
| **Anonymous** | Just visit | (no auth) | n/a |
| **Customer** | Register at `/register`, role = `customer` | `/account/*` | `auth:sanctum` |
| **Designer** | Register with role = `designer` | `/designer/*` + `/account/*` | `auth:sanctum` |
| **Printer Provider** | Register with role = `printer_provider` | `/printer/*` + `/account/*` | `auth:sanctum` |
| **Admin** | Filament panel — seeded `ops@pod.local` (password from `SEED_ADMIN_PASSWORD` env, default `ChangeMe!InProd2026`) | `/admin/*` | `auth:sanctum` + role gate |

Roles are **not exclusive**: a designer can still browse, buy, and view their account dashboard. Designer/printer roles just unlock extra pages.

---

## 🔑 Seeded test accounts

After `php artisan migrate:fresh --seed`, the following accounts are created. **All accounts use the password `password`** unless noted — change them before any non-local deployment.

### Admin accounts (login at `/admin/login`)

| Email | Password | Notes |
|-------|----------|-------|
| `ops@pod.local` | value of `SEED_ADMIN_PASSWORD` env var (default `ChangeMe!InProd2026`) | Production-style admin. **Rotate on first deploy** — see `deploy/PRODUCTION.md`. |
| `admin@podmarketplace.test` | `password` | Dev-only admin from Phase 1-3 seeders. Kept alongside `ops@pod.local` so existing test logins remain valid. |

### Designer accounts (login at `/login` → role lands on `/designer`)

| Email | Name | Public profile |
|-------|------|----------------|
| `lana@designstudio.test` | Lana Rivera | `/designers/{id}` |
| `kenji@inkcraft.test` | Kenji Tanaka | `/designers/{id}` |
| `maya@artisan.test` | Maya Hassan | `/designers/{id}` |

### Printer accounts (login at `/login` → role lands on `/printer`)

| Email | Company | Public profile |
|-------|---------|----------------|
| `owner@printhub.test` | PrintHub Co. | `/printers/{id}` |
| `press@artisanpress.test` | Artisan Press Ltd. | `/printers/{id}` |
| `workshop@pixel.test` | Pixel Workshop Inc. | `/printers/{id}` |

### Customer accounts (login at `/login` → role lands on `/account`)

`alice@example.test`, `bob@example.test`, `carla@example.test`, `diego@example.test`, `emma@example.test`, `felix@example.test`, `grace@example.test`, `hiro@example.test`, `ines@example.test`, `jonas@example.test` — all with password `password`.

---

## 🟡 Anonymous (no login)

| URL | Page | Purpose |
|-----|------|---------|
| `/` | Home | Marketing landing |
| `/browse/designs` | Browse designs | Grid of published designs; filters: category, tag, search |
| `/browse/categories` | Browse categories | Top-level categories with design counts |
| `/browse/designers` | Browse designers | Designers with published design counts |
| `/designs/{id}` | Design detail | Gallery + variant picker + add-to-cart |
| `/designers/{id}` | Public designer profile | Bio + their published designs |
| `/printers/{id}` | Public printer profile | Company name + their product templates |
| `/login` | Login | Email + password form |
| `/register` | Register | Name + email + password + role selector |
| `/forgot-password` | Forgot password | Email submission; sends reset link |
| `/reset-password/{token}` | Reset password | Set new password |
| `/legal/terms` | Terms of service | v1.2 platform terms (counsel review pending) |
| `/legal/privacy` | Privacy policy | v1.2 privacy policy (counsel review pending) |
| **any unknown URL** | 404 | Themed "Page not found" |

---

## 🟢 Customer (authenticated, role = customer)

All anonymous pages, **plus**:

### Shopping
| URL | Page | Purpose |
|-----|------|---------|
| `/cart` | Cart | List cart items, update quantity, remove, clear |
| `POST /cart/items` | (form) | Add item to cart |
| `PATCH /cart/items/{item}` | (form) | Update quantity |
| `DELETE /cart/items/{item}` | (form) | Remove item |
| `DELETE /cart` | (form) | Clear entire cart |
| `/checkout` | Checkout | Address picker + payment method + place order |
| `POST /checkout` | (form) | Place order |
| `/orders/{order}` | Order confirmation | Receipt page after checkout |

### Account (`/account/*`)
| URL | Page | Purpose |
|-----|------|---------|
| `/account` | Dashboard | Greeting, recent orders, notification count |
| `/account/addresses` | Addresses | CRUD shipping/billing addresses |
| `/account/addresses/new` | New address form | Add a new address |
| `/account/addresses/{id}/edit` | Edit address form | Update address |
| `/account/orders` | Order history | Paginated order list |
| `/account/notifications` | Notifications | Paginated in-app notification list |
| `PATCH /account/notifications/{id}` | (form) | Mark one notification read |
| `DELETE /account/notifications/{id}` | (form) | Delete one notification |
| `PATCH /account/profile` | (form) | Update name, email, phone |
| `PATCH /account/password` | (form) | Change password |
| `POST /logout` | (form) | Log out (any role) |

---

## 🔵 Designer (authenticated, role = designer)

All customer pages, **plus**:

| URL | Page | Purpose |
|-----|------|---------|
| `/designer` | Designer dashboard | Designs count, published count, mappings count, recent designs, 30-day revenue |
| `/designer/edit` | Edit profile | Update bio (the only editable field — schema is minimal in v1.2) |
| `PATCH /designer` | (form) | Save bio update |

**Notes:**
- Your own **drafts** are visible at `/designs/{id}` to you only (anonymous → 404).
- Designing/upload happens via API endpoints (`POST /api/me/designs`, etc.) — Phase 3 web layer is read-only for design assets.
- Public profile at `/designers/{your-id}` is generated automatically from your designer profile.

---

## 🟣 Printer Provider (authenticated, role = printer_provider)

All customer pages, **plus**:

| URL | Page | Purpose |
|-----|------|---------|
| `/printer` | Printer dashboard | Templates count, variants count, recent order items assigned to you |
| `/printer/edit` | Edit profile | Update company_name |
| `PATCH /printer` | (form) | Save company name |

**Notes:**
- Public profile at `/printers/{your-id}` shows company name + templates you offer.
- Order item claiming happens via API (`PATCH /api/order-items/{id}/status`) — Phase 3 web layer is read-only for fulfillment.

---

## 🔴 Admin (role = admin)

The Filament v4 admin panel mounts at `/admin` and is themed with the same Sand + Coral brutalist palette as the rest of the app. Login at `/admin/login`. Anonymous users hitting any `/admin/*` route are redirected to `/admin/login`; non-admin roles get HTTP 403.

**Seeded admin credentials:** see the 🔑 Seeded test accounts section above for full table — short version: `ops@pod.local` (password from `SEED_ADMIN_PASSWORD` env, default `ChangeMe!InProd2026`) or `admin@podmarketplace.test` (`password`). Rotate `ops@pod.local` immediately after the first deploy — see the production runbook for the rotation policy.

### Dashboard
| URL | Purpose |
|-----|---------|
| `/admin` | KPI dashboard — 4 stat widgets (customers, revenue today, pending orders, abandoned carts) + revenue chart + recent orders table |

### Resources (CRUD)
13 resources, each with list / create / view / edit (some read-only):

| Slug | Resource | Notes |
|------|----------|-------|
| `/admin/users` | Users | Role-locked self-edit guard prevents admins from demoting themselves |
| `/admin/addresses` | Addresses | All customer addresses |
| `/admin/categories` | Categories | Design taxonomy |
| `/admin/designs` | Designs | Soft-deletable (restore action) |
| `/admin/design-product-mappings` | Design ↔ Product mappings | |
| `/admin/delivery-companies` | Delivery companies | |
| `/admin/media` | Media library | |
| `/admin/order-items` | Order items | Read-only |
| `/admin/orders` | Orders | Read-only; cancel / mark-delivered actions on the view page |
| `/admin/payments` | Payments | |
| `/admin/product-templates` | Product templates | |
| `/admin/product-variants` | Product variants | |
| `/admin/tags` | Tags | Design tags |

### Reports (8 pages)
| URL | Purpose |
|-----|---------|
| `/admin/overview-report` | Platform overview aggregates |
| `/admin/revenue-by-day-report` | Daily revenue time-series |
| `/admin/revenue-by-designer-report` | Revenue per designer |
| `/admin/revenue-by-printer-report` | Revenue per printer |
| `/admin/top-designs-report` | Top designs by revenue/units |
| `/admin/customer-ltv-report` | Customer lifetime value |
| `/admin/order-status-distribution-report` | Order status breakdown |
| `/admin/refund-rate-report` | Refund rate metrics |

Each report page has a **Download CSV** button that streams a CSV export with the same columns as the on-screen table.

### Authentication
| URL | Purpose |
|-----|---------|
| `/admin/login` | Filament login form |
| `POST /admin/logout` | Log out |

### Deferred (not built in Phase 4)
Some report pages referenced in earlier plans were deferred because their backing Phase 2 Report classes were never built:

- **Phase 4.5 deferred** (3 Ops + 1 funnel): `CartAbandonmentReport`, `StuckPaymentsReport`, `StuckShipmentsReport`, `ConversionFunnelReport`.
- **Phase 5 deferred** (printer/designer scope, not admin scope): `PrinterPayoutReport`, `WorkQueueReport`, `DesignerPayoutReport` — these belong on the per-role dashboards, not in the admin panel.

If you need any of these, you can still pull the data via the corresponding `/api/admin/...` or `/api/reports/{reportKey}` endpoints.

### Audit log
Every admin write (create / update / delete / restore) on a Filament resource is forwarded to a dedicated `audit` log channel. Admins performing the action and the affected entity are both recorded. See `docs/deploy/production-runbook.md` for log rotation policy.

---

## 📚 Developer surface

| URL | Purpose |
|-----|---------|
| `/api/docs` | L5-Swagger UI (interactive API playground) |
| `/api/docs.json` | OpenAPI 3 spec as JSON |

In production these are gated behind `auth + admin`. In `local`/`testing` they are public.

---

## API at a glance (for clients / SPAs / mobile)

All API routes are under `/api/*` and require a **Sanctum bearer token** (issued by `POST /api/auth/login` or via the `pod_token` cookie set on web login).

### Public catalog (no auth)
- `GET /api/designs` — list (filter by category, tag, q)
- `GET /api/designs/{id}`
- `GET /api/categories`, `GET /api/categories/{id}`
- `GET /api/designers`, `GET /api/designers/{id}`
- `GET /api/printers`, `GET /api/printers/{id}`
- `GET /api/templates`, `GET /api/templates/{id}`
- `GET /api/templates/{id}/variants`
- `GET /api/mappings`, `GET /api/mappings/{id}`
- `GET /api/tags`, `GET /api/tags/{slug}`
- `GET /api/delivery-companies`, `GET /api/delivery-companies/{id}`
- `GET /api/settings`, `GET /api/settings/{key}`

### Auth
- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`

### Personal (`auth:sanctum`)
- `GET /api/me` — your profile
- `PATCH /api/me` — update name/email/phone
- `DELETE /api/me` — delete account
- `PATCH /api/me/password` — change password
- `GET|POST|PATCH|DELETE /api/me/addresses[/{id}]` — your addresses
- `GET|POST|PATCH|DELETE /api/me/cart[/items/{id}]` — your cart
- `GET|POST|PATCH|DELETE /api/me/notifications[/{id}]`
- `PATCH /api/me/notifications/{id}/read`
- `POST /api/me/notifications/mark-all-read`
- `GET /api/me/notifications/unread-count`
- `GET /api/me/payments` — your payment history

### Designer-only (`auth + role:designer`)
- `POST /api/me/designer-profile`
- `PATCH /api/me/designer-profile`
- `DELETE /api/me/designer-profile`
- `GET|POST|PATCH|DELETE /api/me/designs[/{id}]`

### Printer-only (`auth + role:printer_provider`)
- `POST /api/me/printer-profile`
- `PATCH /api/me/printer-profile`
- `DELETE /api/me/printer-profile`
- `GET|POST|PATCH|DELETE /api/me/templates[/{id}]`
- `POST|PATCH|DELETE /api/me/templates/{id}/variants[/{id}]`
- `GET|POST|PATCH|DELETE /api/me/mappings[/{id}]`

### Shared fulfillment (designer + printer)
- `GET /api/orders`, `POST /api/orders` — place an order
- `GET /api/orders/{id}` — your order
- `POST /api/orders/{id}/cancel`
- `POST /api/orders/{id}/payments` — attach payment
- `GET /api/orders/{id}/shipments`
- `POST /api/shipments`, `GET /api/shipments/{id}`
- `PATCH /api/shipments/{id}/mark-shipped`
- `PATCH /api/shipments/{id}/mark-delivered`
- `PATCH /api/shipments/{id}/tracking`
- `GET /api/order-items/{id}`
- `PATCH /api/order-items/{id}/status`
- `GET /api/payments`, `GET /api/payments/{id}`
- `PATCH /api/payments/{id}/confirm|reject`

### Media (any authenticated)
- `POST /api/media` — upload (rate-limited `throttle:uploads`)
- `GET /api/media/{id}`
- `DELETE /api/media/{id}`

### Admin (Phase 4 web UI ships at `/admin/*` — these endpoints remain available for scripts / mobile clients)
- `/api/admin/dashboard`
- `/api/admin/users[/{id}]` (index, show, update, destroy, restore)
- `/api/admin/orders/{id}` (destroy, restore, reassign printer)
- `/api/admin/categories[/{id}]`
- `/api/admin/tags[/{id}]`
- `/api/admin/delivery-companies[/{id}]`
- `/api/admin/settings[/{id}]`
- `/api/admin/payments/{id}`
- `/api/admin/notifications`
- `/api/admin/designs/{id}/transfer`
- `/api/admin/audit/deleted`
- `/api/admin/integrity/{orphaned-media,stuck-cart-items,items-without-shipment,profile-mismatches}`
- `GET /api/reports/{reportKey}`

---

## Conventions

- All state-changing web routes require `@csrf`.
- All API writes require `Authorization: Bearer {token}`.
- Login web flow also queues an HttpOnly `pod_token` cookie so the same browser can call the API.
- Rate limits: API 120/min (auth) or 30/min (anon), auth pages 10/min/IP, uploads 30/min (auth) or 5/min (anon).
- Error responses: web renders `errors/404.blade.php` etc.; API returns JSON `{message, errors?}`.

---

## Where to look next

- **Phase 4 complete**: Filament admin panel at `/admin/*` ships with this build (13 resources + KPI dashboard + 8 report pages). The `/api/admin/*` endpoints remain for scripts and mobile clients.
- **Production deployment**: counsel review of `/legal/terms` + `/legal/privacy` is the only blocker before public launch. Rotate the seeded `ops@pod.local` admin password on first deploy.
- **Phase 5+**: customer-facing design upload UI, payment-provider integration (currently `cash_on_delivery` only).