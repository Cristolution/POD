# Production runbook — POD Platform

This runbook is the canonical reference for promoting a build of the POD platform to a production environment. It is intentionally written as a flat checklist so the on-call engineer can execute each step without re-reading the surrounding context.

## Pre-deploy

1. Confirm the deploy branch. Phase 4 build-out work currently lives on the `local-smart-shot` branch; deployments read from `main`. Merge `local-smart-shot` into `main` only after the suite is green and the change has been signed off.
2. Set `APP_ENV=production` and `APP_DEBUG=false`.
3. Generate `APP_KEY` (`php artisan key:generate`).
4. Set the database credentials in `.env`:
   - `DB_CONNECTION=pgsql`
   - `DB_HOST=<managed-postgres-host>`
   - `DB_DATABASE=pod_prod`
   - `DB_USERNAME=pod_app`
   - `DB_PASSWORD=<from secrets manager>`
5. Set Redis: `REDIS_HOST=<managed-redis-host>`.
6. Set S3: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`.
7. Set mail: `MAIL_MAILER=ses` (or `postmark`), `MAIL_FROM_ADDRESS=noreply@pod.example.com`.
8. Set Sentry: `SENTRY_LARAVEL_DSN=<dsn>`.
9. Set `SEED_ADMIN_PASSWORD=<generated-strong-password>` (store in 1Password). The default fallback is `ChangeMe!InProd2026` and must be rotated on first deploy.

## Deploy

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --class=UserSeeder   # creates ops@pod.local with role=admin
php artisan config:cache route:cache view:cache
php artisan storage:link
php artisan queue:restart
```

`UserSeeder` is the sole seeder responsible for the production-style admin account (`ops@pod.local`). It honours `SEED_ADMIN_PASSWORD` from `.env`; if the variable is unset it falls back to `ChangeMe!InProd2026`, which **must** be rotated before the service is exposed to anyone other than the deployer.

## Post-deploy

1. Rotate `SEED_ADMIN_PASSWORD` once a single admin account has been verified.
2. Enforce MFA on the admin account.
3. Document an IP allowlist (recommended, not enforced in code per spec).
4. Verify `/admin/login` renders with the Sand+Coral theme.
5. Verify `/admin` (not `/admin/login`) renders the Sand+Coral KPI dashboard. Filament v4 mounts the panel directly at `/admin`; the dashboard widget grid lives there, while the login form is at `/admin/login`.
6. Verify `/api/docs` requires admin auth in non-local environments. L5-Swagger v11 serves OpenAPI documentation at `/api/docs`, and the route is gated behind `auth + admin` middleware outside the `local` environment (wired in Phase 1).

### Reports

The admin panel currently ships 8 report pages, each mounted under the "Reports" navigation group:

- `Overview` — platform-wide totals and recent activity.
- `RevenueByDay` — daily revenue time series.
- `RevenueByDesigner` — revenue attribution per designer.
- `RevenueByPrinter` — revenue attribution per printer provider.
- `TopDesigns` — best-selling designs by units and revenue.
- `CustomerLtv` — customer lifetime value distribution.
- `OrderStatusDistribution` — order counts bucketed by status.
- `RefundRate` — refund rate over a rolling window.

Each report supports CSV export.

Seven additional reports are deferred: three to Phase 4.5 and four to Phase 5. They will be documented here as they ship.

## Backups

- Postgres: nightly managed backup, 30-day retention.
- S3 media: versioning enabled, lifecycle rule moves objects >90 days to Glacier.
