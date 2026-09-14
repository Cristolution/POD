# POD Platform

A multi-vendor **print-on-demand marketplace** built on Laravel 13.

Independent **designers** upload artwork. Independent **printers** (fulfillers) produce and ship the physical product. **Customers** browse the catalogue, pick a design + product template, and order. **Admins** keep the whole thing honest.

No inventory, no upfront costs for designers — every order routes to the printer assigned by the designer for that product mapping. Each role ships with its own dashboard (Filament for admins, custom Livewire pages for designers/printers/customers).

## Highlights

- **Roles:** customer · designer · printer · admin — each with its own dashboard, permissions, and onboarding.
- **Catalogue:** designers + categories + tags; designs paired to product templates via designer-managed **mappings** with variants, mockups, and per-product print files.
- **Commerce:** cart, checkout, order lifecycle (`received → printed → shipped → delivered`), payments, shipments, delivery-company tracking URLs.
- **Reviews & wishlist** on every design.
- **Self-delete** flow with email confirmation and a `data/users/me` soft-delete.
- **Admin approval hub:** pending orders, pending payments, designer verifications, KPI dashboard, 8 CSV-exportable reports.
- **Multilingual** public site (en / ar / tr) with full RTL flip via a dedicated stylesheet.
- **JSON API** + Swagger UI gated behind admin in production.
- **Theme:** sand + coral brutalist (3–5 px borders, Archivo Black + Work Sans + Space Mono).

## Tech

PHP 8.5 · Laravel 13 · Filament 4 · Livewire 3 · Tailwind 4 · Alpine 3 · Sanctum 4 · L5-Swagger 11 · PHPUnit 12.

## Install & run

```bash
git clone https://github.com/Cristolution/POD.git && cd POD
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite   # or point .env at MySQL
php artisan migrate --seed
npm install && npm run build     # or: npm run dev
php artisan serve                # http://localhost:8000
php artisan test --compact       # run the test suite
```

Seeded test accounts are listed in [`doc/USER_GUIDE.md`](doc/USER_GUIDE.md) (admin + one of each role).

## Repo layout

```
app/Actions/        Single-purpose business logic (Auth, Cart, Catalog, Orders, Admin, User, …)
app/Filament/       Admin panel — Resources, Pages, Widgets, Concerns
app/Http/Controllers/{Web,Api}/   Thin controllers dispatching to Actions + FormRequests
app/Models/         22 Eloquent models
resources/views/    Blade components + brutalist Tailwind 4 layout
tests/Feature/      Feature tests per domain; per-feature PHPUnit filters
doc/                Specs, permission matrix, API endpoints, deploy runbook
```