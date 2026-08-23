# POD Platform Build-Out — Design Spec

**Status:** Approved (2026-08-23)
**Branch:** `local-smart-shot` (work isolated from `main` until user merges)
**Companion docs:** `doc/PRD-POD-Platform-Database-v1.2.md`, `doc/SRS-POD-Platform-Database-v1.2.md`, `doc/ApiEndpoints-v1.2.html`, `doc/PermissionMatrix-v1.2.md`, `doc/Reports-v1.2.md`

---

## 0. Environment Strategy (3 environments)

Every Phase 1–4 deliverable is designed to work across three deployment targets. Each environment has its own `.env` template and config overrides. **All three share the same code; only `.env` and provisioning differ.**

| Aspect | Local | Dev / Staging | Production |
|---|---|---|---|
| **APP_ENV** | `local` | `staging` | `production` |
| **APP_DEBUG** | `true` | `true` | `false` |
| **APP_URL** | `http://localhost:8000` | `https://staging.pod.example.com` | `https://pod.example.com` |
| **Database** | SQLite (`database/database.sqlite`) | PostgreSQL 16 (managed) | PostgreSQL 16 (managed, read replicas for reports) |
| **Cache** | `file` driver | `redis` | `redis` (cluster) |
| **Queue** | `sync` | `database` (Laravel default) → upgradeable to Redis | `redis` |
| **Mail** | `log` driver (writes to `storage/logs/laravel.log`) | `mailtrap` or `mailgun` sandbox | `ses` / `postmark` |
| **Filesystem (default)** | `public` (`storage/app/public`) | `s3` (dev bucket) | `s3` (prod bucket, CloudFront in front) |
| **Session driver** | `file` | `database` | `database` |
| **Broadcast driver** | `null` | `null` (Phase 1–2); `reverb` (Phase 3+) | `reverb` |
| **Error tracking** | none | Sentry (`SENTRY_LARAVEL_DSN`) | Sentry + Laravel Telescope (admin only) |
| **Logging** | `single` channel, `storage/logs/laravel.log` | `daily` channel, central log drain (Papertrail / Cloudwatch) | `daily` + `slack` channel on `error` level |
| **Rate limits** | generous (development-friendly) | standard | standard |
| **CORS** | permissive | permissive (`staging.pod.example.com` only) | strict (`pod.example.com` only) |
| **Asset build** | `npm run dev` (Vite HMR) | `npm run build` + CDN | `npm run build` + CDN (immutable cache, hashed filenames) |
| **Backups** | none | daily DB snapshot, 7-day retention | daily DB snapshot, 30-day retention + S3 cross-region |
| **CI/CD** | local-only | GitHub Actions: lint → test → build → deploy to staging on push to `local-smart-shot` | manual promotion from staging to prod (tag-triggered deploy) |
| **Tests** | `php artisan test` (full suite on demand) | full suite on every PR + every push | smoke test on every deploy |
| **Seeders** | full demo data | full demo data | minimal admin only + curated catalog |

**Files affected:**
- `.env.example` (template covering all 3)
- `config/{app,database,cache,queue,filesystems,mail,services}.php` (env-aware defaults, no code changes between envs)
- `bootstrap/app.php` (debug, error handler — strict in prod, verbose in local)
- `docker-compose.yml` (optional — local dev convenience; staging/prod run on Laravel Cloud or Kubernetes)
- `deploy/` directory (per-env provisioning scripts, kept out of the app code)

**What stays the same across all 3:**
- Codebase
- Routes, controllers, actions, policies
- Database schema (migrations)
- API contract
- Filament admin
- Sand + Coral design system

**Environment-aware code patterns** (used sparingly, only where needed):
- `config('app.env')` checks inside actions for env-specific behavior (e.g., disabling rate limits in tests). Documented per usage; never inside business logic that affects data integrity.
- `App\Providers\AppServiceProvider::boot()` reads env and conditionally registers service providers (e.g., `SentryServiceProvider` only in staging/prod).
- All env-specific config is in `config/`, never hard-coded.

---

## 1. Visual Identity — Design 4: Sand + Coral

Locked theme combining RawBlock's border language (3-5px thick borders, 0px radius, sharp corners, no shadows) with a high-contrast warm palette.

### 1.1 Color Tokens

| Token | Light | Dark | Use |
|---|---|---|---|
| `--sand` | `#F5EFE6` | `#1a1a1a` | Page background |
| `--ink` | `#1a1a1a` | `#F5EFE6` | Borders + text |
| `--coral` | `#FF6B4A` | `#FF6B4A` | Accent (CTAs, links, emphasis) |
| `--amber` | `#FFA500` | `#FFA500` | Secondary accent (highlights, warnings) |
| `--paper` | `#FFFFFF` | `#2a2a2a` | Card surface |
| `--abyss` | `#1a1a1a` | `#0a0a0a` | Inverse sections (footer, hero dark) |
| `--mute` | `#6B5E50` | `#A1A1AA` | Secondary text, captions |
| `--success` | `#008000` | `#9ECE6A` | Success states |
| `--error` | `#FF0000` | `#E40014` | Error states, destructive actions |

### 1.2 Typography

- **Headings (h1–h4):** Archivo Black, all-caps, letter-spacing -0.02 to -0.03em
  - h1: 56px / line-height 1.0
  - h2: 36px / line-height 1.05
  - h3: 24px / line-height 1.1
  - h4: 18px / line-height 1.2
- **Body:** Work Sans, 16px, line-height 1.6
  - Large body: 18px / 1.6
  - Small body: 14px / 1.5
- **Labels / metadata / nav:** Space Mono, 11–14px, uppercase, +0.06–0.1em tracking

Fonts loaded via Google Fonts CDN in `resources/views/layouts/app.blade.php` (already configured for Bunny CDN via vite.config.js — replace with Google Fonts link for production).

### 1.3 Component Rules

- **Borders:** 3px standard, 5px for hero/featured containers, **0px border-radius everywhere**
- **No shadows anywhere** — hierarchy via border weight, scale, contrast
- **Buttons + nav + labels:** uppercase + tracked
- **Coral accent** used sparingly — CTAs, links, key emphasis only

### 1.4 Logo

"POD/" wordmark with the `/` character rendered in coral. Used in nav, footer, login page, Filament sidebar.

### 1.5 Dark / Light Toggle

- Persists in cookie + localStorage
- Default: respects `prefers-color-scheme`
- Toggle button in nav (sun/moon icon)
- Implemented as Alpine.js component reading `<html data-theme="dark|light">`

---

## 2. Architecture Overview

Four phases, ordered by dependency. Each phase ends with a passing test suite + a `feat: phase-N done` checkpoint commit on `local-smart-shot`.

```
Phase 1 ──► Phase 2 ──► Phase 3 ──► Phase 4
Review &   API          Blade       Filament
Swagger    surface      views       admin
```

All four phases share the Sand + Coral design system. Filament's admin panel is themed to match.

---

## 3. Module Map After Phase 4

```
app/
├── Actions/                       ← thin invokable business logic
│   ├── Auth/{Register,Login,Logout,ResetPassword}
│   ├── Cart/{AddItem,UpdateQty,RemoveItem,ClearCart}
│   ├── Order/{CreateOrder,CancelOrder,UpdateItemStatus}
│   ├── Payment/{CreatePayment,ConfirmPayment,RejectPayment}
│   ├── Shipment/{CreateShipment,MarkShipped,MarkDelivered}
│   ├── Design/{Publish,Archive,Transfer}
│   └── Notification/{OrderPlaced,PaymentConfirmed,OrderShipped,OrderDelivered,OrderCancelled}
├── Http/
│   ├── Controllers/Api/           ← thin: FormRequest → Action → Resource
│   ├── Controllers/Web/           ← Blade view controllers (public + self-management)
│   ├── Requests/, Resources/      ← already exist
│   └── Middleware/                ← EnsureUserRole, EnsureAdmin
├── Policies/                      ← one per protected model
├── Notifications/                 ← 5 queued Notification classes
├── Filament/                      ← Resources, Pages, Widgets for admin
└── Models/, Providers/            ← already exist

resources/views/
├── layouts/app.blade.php          ← single Tailwind layout, dark/light toggle
├── home.blade.php, catalog/, designs/, auth/, me/
├── errors/{403,404,500}.blade.php
└── filament/                      ← Filament custom views (overrides)

resources/css/
├── app.css                        ← main Sand+Coral tokens, Tailwind 4 imports
└── filament-admin.css             ← Filament theme overrides matching Sand+Coral

resources/js/
├── app.js                         ← imports Alpine + components
├── csrf.js                        ← reads meta tag, sets X-CSRF-TOKEN
└── components/{cart,notifications,variants,modals,forms,filters}.js

routes/api.php, routes/web.php     ← complete surface
config/l5-swagger.php              ← Swagger config (generated)
tests/Feature/Api/, tests/Feature/Web/, tests/Feature/Filament/, tests/Unit/Architecture/
```

---

## 4. Phase 1 — Review + Swagger

### 4.1 Goals

Verify the existing foundation has no hidden defects. Produce a live, browsable API contract at `/api/docs`.

### 4.2 Steps

1. **Foundation audit** — `php artisan migrate:fresh --seed`; verify all factories produce valid rows; smoke-test models in tinker; run `composer audit`; run `vendor/bin/pint --test`. Fix anything broken. Commit `chore: phase-1 foundation audit`.
2. **Install L5-Swagger** — `composer require darkaonline/l5-swagger`. Generate `config/l5-swagger.php`. Mount UI at `/api/docs`. Mount JSON at `/api/docs.json`.
3. **Annotate every existing FormRequest** with `@OA\Schema(...)` and every Resource with response shape annotations.
4. **Annotation strategy** — Phase 1 places `@OA\Get`, `@OA\Post`, `@OA\Patch`, `@OA\Delete` on a **dedicated docblock class** `app/Http/Controllers/Api/OpenApi.php` (aggregator). Phase 2 moves these annotations onto the real controllers and deletes the aggregator.
5. **Validation** — `composer test:api-docs` runs `php artisan l5-swagger:generate` and asserts no errors.

### 4.3 Deliverables

- Passing test suite + clean audit
- Live Swagger UI at `/api/docs` covering all ~85 endpoints
- OpenAPI JSON downloadable for client codegen
- All three environments (local/dev/prod) serve `/api/docs` identically; in prod it's gated behind `auth:admin` middleware

---

## 5. Phase 2 — API Surface (controllers + actions + policies + tests)

### 5.1 Goals

Every endpoint in the API doc works, behind the right policy, with exhaustive feature tests.

### 5.2 Architecture per Route Group

```
HTTP request
   ↓
Route::middleware(['auth:sanctum', 'throttle:api-write'])  ← bootstrap/app.php
   ↓
Api\{Domain}Controller@method   ← 1–10 lines: validate, call Action, return Resource
   ↓
App\Actions\{Verb}{Thing}        ← public function __invoke(...): Model  (one class)
   ↓
Eloquent (model observers fire Notification classes)
   ↓
JsonResource::toArray()         ← response shape
```

### 5.3 Domain Controllers (~15 files)

- `AuthController` (register, login, logout, forgot/reset)
- `MeController` (current user, profile, addresses)
- `UserController` (admin user CRUD + restore)
- `DesignerProfileController`, `PrinterProfileController`
- `CatalogController` (categories, tags, designs, templates, variants, mappings — public reads; admin/owner writes)
- `CartController`
- `OrderController`, `OrderItemController`
- `ShipmentController`, `DeliveryCompanyController`
- `PaymentController`
- `MediaController` (polymorphic uploads)
- `NotificationController`
- `SettingController`
- `AdminController` (dashboard, integrity checks, audit)
- `ReportController` (15 reports, one method each)

### 5.4 Action Classes (~25)

One `__invoke` per action. Stateless. Constructor-injects nothing they don't need.

### 5.5 Policies (~9)

One per protected model. Laravel 11+ auto-discovery via `app/Policies/`. Each implements `view`, `create`, `update`, `delete`, `restore`, `forceDelete`.

- "Own" checks: `return $user->id === $model->customer_id` etc.
- Admin override: early `return true` if `$user->isAdmin()`
- Designers can edit own designs, printers can edit own templates, etc.

### 5.6 Notification Triggers (5 events)

All queued (`ShouldQueue`). Channels: `database` (polymorphic `notifications` table) + `mail` (env-gated, on in staging/prod).

| Event | Fires from | Recipients |
|---|---|---|
| `OrderPlaced` | `CreateOrder` | customer + each designer of items + each printer of items |
| `PaymentConfirmed` | `ConfirmPayment` | customer |
| `OrderShipped` | `MarkShipped` | customer |
| `OrderDelivered` | `MarkDelivered` | customer + designers of items |
| `OrderCancelled` | `CancelOrder` / item cancel | customer + designers + printers of cancelled items |

### 5.7 Error Handling

Single global JSON handler in `bootstrap/app.php`:

- 401 → `{message: "Unauthenticated."}`
- 403 → `{message: "Forbidden."}`
- 404 → `{message: "Resource not found."}`
- 409 → `{message: "Conflict.", reason: "..."}` (custom `ConflictException` thrown by actions when delete-blocked by reference)
- 422 → standard Laravel validation `{message, errors: {field: [..]}}`

### 5.8 Rate Limiting

Defined in `bootstrap/app.php` (Laravel 11+ pattern) using `RateLimiter::for(...)`:

- `auth` group: 10/min per IP
- `api-write`: 60/min per token
- `api-read`: 300/min per token

### 5.9 File Storage

`public` disk → `storage/app/public` (symlinked). All media uploads. Configurable via `.env` (`FILESYSTEM_DISK=public|s3`). S3 driver added but not required for v1.2.

### 5.10 Cart Consumption on Order

`CreateOrder` runs in `DB::transaction`: snapshots address, creates order + items + initial `Payment(status=pending)`, then `CartItem::where('user_id', $user->id)->delete()`. All-or-nothing.

### 5.11 Tests (~300, exhaustive)

For every endpoint:

- `test_unauthenticated_redirects_or_401`
- `test_wrong_role_returns_403`
- `test_validation_returns_422`
- `test_happy_path_returns_2xx_with_resource_shape`
- `test_soft_delete_returns_404`
- `test_cascade_block_returns_409_when_referenced` (where applicable)

Plus:

- Factories used to set up state; no manual `Model::create()` in tests
- Tests grouped by domain under `tests/Feature/Api/{Domain}/`
- Single `ArchitectureTest` enforces: controllers extend nothing extra, action classes have exactly one public method, all Models in `App\Models` use `HasFactory` if they have a factory, policies exist for each protected model

### 5.12 Env-Specific Notes

- **Local:** SQLite, sync queue, log mail, no rate limit issues.
- **Dev:** Postgres, database queue, mailtrap, standard rate limits, Sentry enabled.
- **Prod:** Postgres + Redis, redis queue, SES/Postmark mail, standard rate limits, Sentry + Telescope (admin only).

---

## 6. Phase 3 — Blade Views (public + self-management only — NO admin)

### 6.1 Goals

Server-rendered pages with Alpine.js for interactivity. No SPA. No build complications beyond standard Vite + Tailwind 4.

### 6.2 Layout

Single `resources/views/layouts/app.blade.php` with Instrument Sans fallback (already wired via Bunny CDN) + Sand + Coral tokens. Top nav: Catalog, Designers, Cart (badge), Notifications (badge), Account dropdown, dark/light toggle.

### 6.3 Pages (~25)

| Route group | Pages |
|---|---|
| `/` | Home, About |
| `/catalog`, `/designs/{uuid}` | Catalog index with filters, design detail with variants selector + Add to cart |
| `/designers`, `/designers/{uuid}` | Designer directory, designer profile |
| `/login`, `/register`, `/forgot-password`, `/reset-password/{token}` | Auth |
| `/me` | Account dashboard (role-aware) |
| `/me/addresses`, `/me/orders`, `/me/cart`, `/me/notifications`, `/me/designer-profile`, `/me/printer-profile` | Self-management |
| `/me/orders/{uuid}` | Order detail with items, payments, shipments, tracking links |
| `/designs/manage` (designer) | Design CRUD |
| `/designs/{uuid}/mappings` (designer) | Mapping management |
| `/templates/manage` (printer) | Template + variant CRUD |

### 6.4 Alpine.js Components (~6 in `resources/js/components/`)

- `cart.js` — add/update/remove via `fetch('/api/...')`, badge count, optimistic UI
- `notifications.js` — poll unread count, dropdown list, mark-read
- `variants.js` — variant selector on design detail
- `modals.js` — generic confirm modal
- `forms.js` — fetch-based form submission + Laravel error rendering
- `filters.js` — catalog filter sidebar (debounced search, chip filters)

### 6.5 Web Controllers

`app/Http/Controllers/Web/` hydrate initial data server-side from Eloquent, pass to Blade. Subsequent interactions go through Alpine.js → `/api/*`. Web controllers reuse actions.

### 6.6 Tests

`tests/Feature/Web/` — each page renders 200 for the right role, contains expected sections, form posts succeed + redirect.

### 6.7 Env-Specific Notes

- **Local:** Vite HMR via `npm run dev`.
- **Dev:** `npm run build` → static assets served from CDN.
- **Prod:** `npm run build` (hashed filenames, immutable cache) → CDN (CloudFront / Cloudflare).

---

## 7. Phase 4 — Filament Admin (v3, custom Sand + Coral theme)

### 7.1 Mount

`/admin` (single panel, single guard). Admin role gated — only seeders create admins. All three environments serve `/admin`; in local/dev seeded with full demo admin, in prod seeded with a single ops admin and password rotation policy documented in deploy runbook.

### 7.2 Filament Resources (13)

Users, DesignerProfiles, PrinterProfiles, Categories, Tags, Designs, ProductTemplates, ProductVariants, DesignProductMappings, DeliveryCompanies, Payments, Settings, Orders (read-only view + status update Actions).

### 7.3 Filament Custom Pages (15 reports)

Admin Overview (KPI dashboard), Revenue by Day, Work Queue (printer), Designer Dashboard, Revenue by Designer, Revenue by Printer, Stuck Payments, Customer LTV, Designer Payout, Printer Payout, Cart Abandonment, Stuck Shipments, My Orders (alias), My Shipments (customer), Top Designs.

### 7.4 Custom Theme

`AdminPanelProvider::panel()->viteTheme('resources/css/filament-admin.css')` overrides Filament's default purple/indigo to match Sand + Coral. Tailwind config has a `filament` block with the same tokens. Borders 3-5px, sharp corners, no shadows — consistent with public site.

### 7.5 Tests

`tests/Feature/Filament/` — admin can/cannot access each Resource, custom theme loads, dashboard widgets render.

### 7.6 Env-Specific Notes

- **Local:** Filament runs at `/admin`, debug bar visible.
- **Dev:** Filament runs at `/admin`, Sentry captures admin errors.
- **Prod:** Filament runs at `/admin`, additional IP allowlist middleware recommended (documented in deploy runbook, not enforced in code).

---

## 8. Cross-Cutting Concerns

| Concern | Decision |
|---|---|
| Auth (API) | Sanctum bearer tokens via `/api/auth/login`. Token returned in `UserResource` envelope. |
| Auth (Web) | Laravel session auth via `/login`. On success, also issue Sanctum token to HttpOnly cookie for Alpine fetch calls. |
| CSRF (Web) | Standard `@csrf` on forms. |
| CSRF (Alpine) | `resources/js/csrf.js` reads `<meta name="csrf-token">`, sends as `X-CSRF-TOKEN` for `/api/*` write routes. |
| Error pages | Blade `errors/{403,404,500}.blade.php` with Sand + Coral. |
| Logging | Default Laravel log; action classes log entry/exit at `info` level. |
| Timezones | `APP_TIMEZONE=UTC` in `.env.example`. Blade uses `isoFormat` via `nesbot/carbon`. |
| Localization | English only. |
| API versioning | All endpoints under `/api/*` (no version prefix in URL — matches the existing `doc/ApiEndpoints-v1.2.html`). Versioning deferred to a future major version bump when the contract breaks; client codegen uses the OpenAPI spec from `/api/docs.json` for type safety. |

---

## 9. Git Workflow (on `local-smart-shot`)

- **Conventional Commits** (`feat:`, `fix:`, `doc:`, `chore:`, `test:`, `refactor:`)
- **Multiple checkpoint commits per phase** (audit, install, scaffold, each domain controller, etc.)
- **`feat: phase-N done`** summary commit at end of each phase
- **Never merge to `main`** without explicit user instruction
- `.superpowers/` and `.env` already in `.gitignore`

Branch names:

- `local-smart-shot` (main build-out branch, persistent)
- Phase-specific feature branches off `local-smart-shot` for risky work (e.g., `feat/phase-2-payments`, `feat/phase-3-catalog`), merged back into `local-smart-shot` after PR review
- User reviews PRs at their discretion before merge

---

## 10. Risks & Mitigations

| Risk | Mitigation |
|---|---|
| L5-Swagger annotations drift from real controllers | `composer test:api-docs` runs on every Phase 2 PR; fails if annotations unreachable |
| Action class explosion (~25 files) | Grouped in `app/Actions/{Domain}/`. README documents convention. |
| Notification spam | All queued; `QUEUE_CONNECTION=sync` in tests, `redis` in prod |
| Soft-delete cascade surprises | Exhaustive tests cover FR-8 + FR-15 + PRD Decision 21 |
| Alpine.js + Sanctum CSRF | Single helper, all components share it |
| Filament theme drift | Theme lives in `resources/css/filament-admin.css`, versioned with the rest |
| Env drift (local ≠ staging ≠ prod) | Single `.env.example` template; CI deploys verify schema matches across envs; smoke tests in prod post-deploy |
| Hardcoded `localhost` URLs in seeders | Use `config('app.url')` everywhere; `.env` overrides |
| File storage changing between envs (local public vs S3) | All upload paths use `Storage::disk(config('filesystems.default'))->url(...)` — never hardcode `/storage/` |
| Lighthouse / Core Web Vitals regression | Run Lighthouse CI in Phase 3 + 4; budget LCP < 2.5s, CLS < 0.1 |
| Future tech debt from 4 phases in one PR | Each phase ends with a checkpoint commit and PR; user reviews before next phase |

---

## 11. Skills Applied Per Phase

| Phase | Skills |
|---|---|
| 1 | `laravel-best-practices`, `superpowers:test-driven-development` (for the swagger-test loop) |
| 2 | `laravel-best-practices`, `superpowers:test-driven-development`, `superpowers:requesting-code-review` (after each module) |
| 3 | `frontend-design`, `tailwindcss-development` |
| 4 | `frontend-design`, `laravel-best-practices` |
| All phases | `superpowers:verification-before-completion` before declaring a phase done |

---

## 12. Out of Scope (deliberately deferred)

- Custom order requests / in-thread messaging
- Sub-orders as separate batch entities (per-item fulfillment is the model)
- Full RBAC (role enum is sufficient for v1.2)
- Refunds workflow (cancelled status exists; no separate accounting)
- Multi-shipment-per-batch
- AI mockup generation
- Multi-source-file per design
- Saved filters / column preferences / activity log
- Internationalization beyond English
- Mobile native apps (web-first)
- Real-time features (websockets) beyond Phase 3+

---

## 13. Open Questions / Future Considerations

These are not blockers; tracked for future phases.

1. **Storage adapter for product files** — current design uses one file per design via polymorphic media. Future: per-design ZIP archive for multi-file products.
2. **Designer analytics dashboard depth** — current Phase 4 dashboards show KPIs only. Future: time-series charts via Filament Widgets + ApexCharts.
3. **Royalty / payout automation** — current schema snapshots unit_price; payouts are derived manually. Future: scheduled job computes and triggers Stripe Connect transfers.
4. **Email templates** — notifications use default Laravel mail templates. Future: branded MJML templates for transactional emails.
5. **Search infrastructure** — current catalog uses simple Eloquent queries with WHERE filters. Future: Laravel Scout + Meilisearch for full-text design search.
6. **Image processing** — current uploads store originals only. Future: queued image variants (thumbnail/medium/full) via spatie/laravel-image-optimizer or AWS Lambda.
7. **Audit log table** — design transfers + admin actions are audit-logged in admin memory only. Future: dedicated `audit_logs` table with retention policy.
8. **API versioning** — current spec has no URL version prefix. When v2 of the contract breaks compatibility, mount new routes at `/api/v2/*` and deprecate `/api/*` with a 12-month sunset.

---

## 14. References

- Schema: `doc/POD 1.2.sql`, `doc/database.dbdiagram.txt`
- Functional requirements: `doc/SRS-POD-Platform-Database-v1.2.md`
- Product decisions: `doc/PRD-POD-Platform-Database-v1.2.md`
- API surface: `doc/ApiEndpoints-v1.2.html`
- Authorization matrix: `doc/PermissionMatrix-v1.2.md`
- Reports spec: `doc/Reports-v1.2.md`
- Theme inspiration: RawBlock design system (border language), Aether color tokens (high-contrast mono), Solutions color tokens (trust palette)
