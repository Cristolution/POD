# POD Platform — Phase 4 Implementation Plan (Filament Admin at /admin)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Stand up the Filament v3 admin panel at `/admin`, themed to match the Sand + Coral brutalist design system. Build 13 Resources (full CRUD) + 15 custom Report Pages + a KPI dashboard, gated exclusively to `role='admin'`.

**Architecture:** Filament v3 with a single panel (`AdminPanelProvider`). Resources and Pages query Eloquent directly through Filament's auto-generated table and form components. Reports are custom Filament Pages that instantiate a `Report` class from Phase 2 and render either a Table or a chart widget.

**Tech Stack:** Filament v3, Filament Spatie Media Library plugin (NOT used — we use the custom `Media` model), Filament Shield (NOT used — `User::isAdmin()` only), Filament Charts via `flowframe/laravel-trend`, custom Tailwind 4 theme overrides.

**Phase boundary:** This phase touches **no public Blade UI** (Phase 3) and **no API controllers** (Phase 2). It consumes both but modifies neither. The only overlap: `app/Providers/Filament/AdminPanelProvider.php` is a new file.

---

## File Map (Phase 4 output)

| Layer            | Path                                                  | Count | Purpose                                                              |
| ---------------- | ----------------------------------------------------- | ----- | -------------------------------------------------------------------- |
| Providers        | `app/Providers/Filament/AdminPanelProvider.php`       | 1     | Panel config + theme                                                  |
| Resources        | `app/Filament/Resources/{Name}Resource.php`           | 13    | Full CRUD for each main entity                                        |
| Resource pages   | `app/Filament/Resources/{Name}Resource/Pages/*.php`   | ~30   | List / Create / Edit / View per resource                              |
| Custom pages     | `app/Filament/Pages/{KpiDashboard,ReportPage}/*.php`  | 16    | KPI dashboard + 15 report pages                                       |
| Widgets          | `app/Filament/Widgets/{Kpi,Chart}Widget.php`          | ~5    | Stat widgets + chart widgets                                          |
| Theme CSS        | `resources/css/filament-admin.css`                    | 1     | Sand + Coral overrides                                                |
| Tests            | `tests/Feature/Filament/*Test.php`                    | ~15   | Admin access + resource render                                        |

Total: **~70 files**.

---

## Task 1: Install Filament v3

**Files:**
- Modify: `composer.json`
- Modify: `bootstrap/providers.php`
- Modify: `config/app.php` (timezone already set)

- [ ] **Step 1: Install Filament + Filament Forms + Filament Tables**

```bash
composer require filament/filament:"^3.2" --no-interaction
php artisan filament:install --panels --no-interaction
```

Expected: `app/Providers/Filament/AdminPanelProvider.php` is created. `bootstrap/providers.php` now includes `App\Providers\Filament\AdminPanelProvider::class`. `/admin/login` is reachable.

- [ ] **Step 2: Run migrations**

```bash
php artisan migrate
```

Expected: no schema changes (Filament uses existing tables).

- [ ] **Step 3: Smoke-test the admin panel loads**

```bash
php artisan serve &
curl -sI http://localhost:8000/admin/login
```

Expected: `200 OK` (or `302` redirect to login page if not authenticated).

- [ ] **Step 4: Commit**

```bash
git add composer.json composer.lock bootstrap/providers.php app/Providers/Filament/AdminPanelProvider.php
git commit -m "feat(admin): install Filament v3 admin panel"
```

---

## Task 2: Configure the admin panel — auth, theme, navigation

**Files:**
- Modify: `app/Providers/Filament/AdminPanelProvider.php`
- Create: `resources/css/filament-admin.css`
- Modify: `vite.config.js`

- [ ] **Step 1: Configure the panel**

Replace `app/Providers/Filament/AdminPanelProvider.php` with:

```php
<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Models\User;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('POD/ Admin')
            ->colors([
                'primary' => Color::hex('#FF6B4A'),     // coral
                'gray'    => Color::hex('#1a1a1a'),     // ink
                'success' => Color::hex('#0a7e3a'),
                'warning' => Color::hex('#FFA500'),
                'danger'  => Color::hex('#C84A2C'),
            ])
            ->font('Work Sans')
            ->viteTheme('resources/css/filament-admin.css')
            ->authGuard('web')
            ->authPasswordBroker('users')
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                NavigationGroup::label('Catalog')->icon('heroicon-o-squares-2x2'),
                NavigationGroup::label('Operations')->icon('heroicon-o-shopping-bag'),
                NavigationGroup::label('Reports')->icon('heroicon-o-chart-bar'),
                NavigationGroup::label('System')->icon('heroicon-o-cog-6-tooth'),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\EnsureAdmin::class,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ]);
    }
}
```

- [ ] **Step 2: Create the Sand + Coral Filament theme**

Create `resources/css/filament-admin.css`:

```css
@import "/vendor/filament/filament/resources/css/theme.css";

@theme {
    --color-primary-50:  #FFE9E2;
    --color-primary-100: #FFCAB8;
    --color-primary-200: #FFAB8E;
    --color-primary-300: #FF8C64;
    --color-primary-400: #FF7C56;
    --color-primary-500: #FF6B4A;
    --color-primary-600: #E85A39;
    --color-primary-700: #C84A2C;
    --color-primary-800: #A83A1F;
    --color-primary-900: #882A12;

    --color-gray-50:  #FBF8F3;
    --color-gray-100: #F5EFE6;
    --color-gray-200: #E8DECF;
    --color-gray-300: #D4C3A8;
    --color-gray-400: #A89B85;
    --color-gray-500: #7C7261;
    --color-gray-600: #5C5448;
    --color-gray-700: #3C352E;
    --color-gray-800: #1a1a1a;
    --color-gray-900: #0a0a0a;
}

.fi-body {
    background-color: var(--color-gray-100);
    color: var(--color-gray-800);
}

/* Sharper corners */
.fi-btn, .fi-input, .fi-select-input, .fi-modal-window, .fi-section, .fi-ta-ctn, .fi-no-notification, .fi-dropdown-panel, .fi-tabs-tab {
    border-radius: 0 !important;
    box-shadow: none !important;
}

/* Heavier borders (3px standard) */
.fi-section, .fi-ta-ctn, .fi-fo-tabs-tab, .fi-input-wrp, .fi-select-input {
    border-width: 3px !important;
}

/* Top nav and sidebar match Sand + Coral */
.fi-topbar {
    background-color: var(--color-gray-100) !important;
    border-bottom: 5px solid var(--color-gray-800);
}
.fi-sidebar {
    background-color: var(--color-gray-100) !important;
    border-right: 5px solid var(--color-gray-800);
}

/* Headings */
.fi-header-heading, h1.fi-header-heading {
    font-family: 'Archivo Black', sans-serif;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}

/* Force coral accents on links */
.fi-link {
    color: var(--color-primary-600) !important;
}
.fi-link:hover {
    color: var(--color-primary-700) !important;
}

/* Buttons: bolder, sharper */
.fi-btn-color-primary {
    background-color: var(--color-primary-500) !important;
    color: white !important;
    border: 3px solid var(--color-primary-700) !important;
}
.fi-btn-color-primary:hover {
    background-color: var(--color-primary-600) !important;
}
```

- [ ] **Step 3: Wire the new CSS into Vite**

`vite.config.js`:

```js
export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/filament-admin.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
});
```

- [ ] **Step 4: Build the assets**

```bash
npm run build
```

- [ ] **Step 5: Verify the admin panel loads with the theme**

```bash
php artisan serve &
curl -s http://localhost:8000/admin/login | grep -E '(pod-brand|coral)'
```

Expected: the brand "POD/ Admin" is in the HTML; coral-tinted CSS classes are emitted.

- [ ] **Step 6: Commit**

```bash
git add app/Providers/Filament/AdminPanelProvider.php \
  resources/css/filament-admin.css vite.config.js public/build
git commit -m "feat(admin): sand+coral Filament theme with sharp corners and 3px borders"
```

---

## Task 3: Restrict /admin/* to admin role

The middleware is already attached (Task 2, `EnsureAdmin`). But we should also ensure the **seeder** creates exactly one admin in production, and that admins can never be self-deleted.

**Files:**
- Modify: `database/seeders/DatabaseSeeder.php`
- Create: `tests/Feature/Filament/AdminGateTest.php`

- [ ] **Step 1: Write the failing gate test**

`tests/Feature/Filament/AdminGateTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_user_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_non_admin_cannot_access_admin_panel(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->get('/admin')->assertForbidden();
    }

    public function test_admin_can_access_admin_panel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/admin')->assertOk();
    }
}
```

- [ ] **Step 2: Run — should pass already (middleware is wired)**

```bash
php artisan test --compact tests/Feature/Filament/AdminGateTest.php
```

Expected: 3 passed.

- [ ] **Step 3: Update the DatabaseSeeder**

Modify `database/seeders/DatabaseSeeder.php` so the admin user is named `ops@pod.local` and has a clearly-marked email pattern for prod rotation:

```php
$admin = User::updateOrCreate(
    ['email' => 'ops@pod.local'],
    [
        'id'    => Str::uuid()->toString(),
        'name'  => 'Ops Admin',
        'role'  => 'admin',
        'password' => Hash::make(env('SEED_ADMIN_PASSWORD', 'ChangeMe!InProd2026')),
        'email_verified_at' => now(),
    ]
);
```

Document in `deploy/PRODUCTION.md` that `SEED_ADMIN_PASSWORD` must be rotated post-deploy.

- [ ] **Step 4: Commit**

```bash
git add database/seeders/DatabaseSeeder.php tests/Feature/Filament/AdminGateTest.php deploy/
git commit -m "feat(admin): ensure admin gate test + production admin seed"
```

---

## Task 4: First Resource — UserResource (full CRUD)

**Files:**
- Create: `app/Filament/Resources/UserResource.php`
- Create: `app/Filament/Resources/UserResource/Pages/{List,Create,Edit,View}Users.php`
- Test: `tests/Feature/Filament/UserResourceTest.php`

- [ ] **Step 1: Generate via Filament artisan**

```bash
php artisan make:filament-resource User --generate --no-interaction
```

Expected: 4 page classes + 1 resource class generated under `app/Filament/Resources/UserResource/`.

- [ ] **Step 2: Customize the form**

Open `app/Filament/Resources/UserResource.php` and replace the `form()` method with:

```php
public static function form(Form $form): Form
{
    return $form->schema([
        Forms\Components\Section::make()
            ->columns(2)
            ->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(120),
                Forms\Components\TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('phone')->tel()->maxLength(32),
                Forms\Components\Select::make('role')
                    ->required()
                    ->options([
                        'admin' => 'Admin',
                        'designer' => 'Designer',
                        'printer_provider' => 'Printer',
                        'customer' => 'Customer',
                    ])
                    ->disabled(fn ($record) => $record?->id === auth()->id()),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->minLength(8),
            ]),
    ]);
}
```

- [ ] **Step 3: Customize the table**

```php
public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('email')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('role')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'admin' => 'danger', 'designer' => 'warning',
                    'printer_provider' => 'info', default => 'gray',
                }),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            Tables\Columns\TextColumn::make('deleted_at')
                ->dateTime()
                ->placeholder('Active')
                ->toggleable(),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('role')
                ->options([
                    'admin' => 'Admin', 'designer' => 'Designer',
                    'printer_provider' => 'Printer', 'customer' => 'Customer',
                ]),
            Tables\Filters\TrashedFilter::make(),
        ])
        ->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
            Tables\Actions\RestoreAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
            ]),
        ]);
}
```

- [ ] **Step 4: Add the navigation config + label**

```php
protected static ?string $model = User::class;
protected static ?string $navigationIcon = 'heroicon-o-users';
protected static ?string $navigationGroup = 'System';
protected static ?int $navigationSort = 10;
```

- [ ] **Step 5: Disable self-edit of role + force password on create**

In `UserResource/Pages/EditUser.php`:

```php
protected function mutateFormDataBeforeSave(array $data): array
{
    if (auth()->id() === $this->record->id) {
        unset($data['role']);  // prevent self-demotion
    }
    return $data;
}
```

- [ ] **Step 6: Write the resource tests**

`tests/Feature/Filament/UserResourceTest.php`:

```php
public function test_admin_can_list_users(): void
{
    User::factory()->count(5)->create();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get('/admin/users')->assertOk();
}

public function test_admin_can_create_user(): void
{
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post('/admin/users', [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'SecretPass1!',
        'role' => 'customer',
    ])->assertRedirect();

    $this->assertDatabaseHas('users', ['email' => 'new@example.com', 'role' => 'customer']);
}

public function test_admin_cannot_demote_self(): void
{
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->put("/admin/users/{$admin->id}", ['role' => 'customer'])
        ->assertSessionHasNoErrors();

    $this->assertSame('admin', $admin->fresh()->role);
}
```

- [ ] **Step 7: Run + commit**

```bash
php artisan test --compact tests/Feature/Filament/UserResourceTest.php
git add app/Filament/Resources/UserResource.php tests/Feature/Filament/UserResourceTest.php
git commit -m "feat(admin): UserResource with role-locked self-edit guard"
```

---

## Task 5: The remaining 12 Resources (catalog + operations)

Generate them via `php artisan make:filament-resource` and customize each. The shape mirrors Task 4 — one resource + four page classes + tests.

| # | Resource                  | Navigation group | Notable fields                                                                  |
| - | ------------------------- | ---------------- | ------------------------------------------------------------------------------- |
| 5 | `CategoryResource`        | Catalog          | name, slug, parent_id (select with `relationship('parent', 'name')`)            |
| 6 | `TagResource`             | Catalog          | name, slug                                                                       |
| 7 | `DesignResource`          | Catalog          | designer (relation), category (relation), title, description, status, base_price, tags (multi-select), media (file upload to Media model via custom action) |
| 8 | `ProductTemplateResource` | Catalog          | printer (relation), name, type, base_cost, specs (key-value), is_active, variants (repeater) |
| 9 | `ProductVariantResource`  | Catalog          | template (relation), sku, attributes (key-value), price_delta, is_active         |
| 10 | `DesignProductMappingResource` | Catalog    | design, template, preferred_printer, final_price, is_active                     |
| 11 | `AddressResource`         | Operations       | user (searchable relation), full address fields, is_default                      |
| 12 | `OrderResource`           | Operations       | user, status, totals (readonly), placed_at, items (relation manager, read-only) |
| 13 | `OrderItemResource`       | Operations       | order, mapping, variant, printer, status, snapshot fields (readonly), unit_price, quantity |
| 14 | `DeliveryCompanyResource` | Operations       | name, coverage_zones (multi-textinput), tracking_url_pattern, is_active         |
| 15 | `ShipmentResource`        | Operations       | order, order_item, delivery_company, tracking_number, status, shipped_at, delivered_at |
| 16 | `PaymentResource`         | Operations       | order, method, status, amount, provider, provider_charge_id, confirmed_at       |
| 17 | `MediaResource`           | Operations       | mediable_type (select), mediable_id, collection, disk, path, mime_type, size_bytes |

- [ ] **Step 1: Generate all 12 in one pass**

```bash
for r in Category Tag Design ProductTemplate ProductVariant DesignProductMapping Address Order OrderItem DeliveryCompany Shipment Payment Media; do
    php artisan make:filament-resource "$r" --generate --no-interaction
done
```

- [ ] **Step 2: Customize forms + tables**

For each resource, follow the same `form()` + `table()` pattern as Task 4. Key principles:
- All UUID relations use `Select::make(...)->relationship('xxx', 'id')` (we display UUIDs in admin because the model uses `HasUuids`)
- All money fields use `->prefix('$')`
- All date fields use `->dateTime()`
- All enum fields use `select()` with the enum values
- All read-only fields (snapshots, totals, audit columns) use `->disabled()` or `->placeholder()`
- Orders and OrderItems are **read-only in admin** (no Edit page) — only View + status update Actions (per spec section 7.2)
- MediaResource has a `FileUpload` field that triggers `UploadMediaAction` from Phase 2

- [ ] **Step 3: Add navigation grouping per the table above**

- [ ] **Step 4: Order status update Action (custom)**

Create `app/Filament/Resources/OrderResource/Pages/ViewOrder.php` with a header action:

```php
protected function getHeaderActions(): array
{
    return [
        Actions\Action::make('cancel')
            ->label('Cancel order')
            ->color('danger')
            ->visible(fn () => $this->record->status !== 'cancelled' && $this->record->status !== 'delivered')
            ->requiresConfirmation()
            ->action(fn () => app(\App\Actions\Order\CancelOrderAction::class)->execute($this->record))
            ->after(fn () => $this->refresh()),

        Actions\Action::make('markDelivered')
            ->label('Mark delivered')
            ->color('success')
            ->visible(fn () => $this->record->status === 'shipped')
            ->action(fn () => $this->record->update(['status' => 'delivered']))
            ->after(fn () => $this->refresh()),
    ];
}
```

- [ ] **Step 5: Write one happy-path test per resource**

For each resource:
```php
public function test_admin_can_list_X(): void
{
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin)->get('/admin/xs')->assertOk();
}
```

Plus 2-3 detailed tests per resource for unique behaviors (e.g., DesignResource test that uploading media attaches a `Media` row; OrderResource test that `cancel` action calls the action class).

- [ ] **Step 6: Run + commit**

```bash
php artisan test --compact tests/Feature/Filament/
git add app/Filament/Resources tests/Feature/Filament/
git commit -m "feat(admin): 13 Filament resources with role-gated admin access"
```

---

## Task 6: KPI dashboard — platform overview widgets

**Files:**
- Create: `app/Filament/Pages/KpiDashboard.php` (replace the default `Pages\Dashboard`)
- Create: `app/Filament/Widgets/{TotalCustomersStat,RevenueTodayStat,PendingOrdersStat,AbandonedCartStat,RecentOrdersTable,RevenueChart}Widget.php`

- [ ] **Step 1: Create the stat widgets**

`app/Filament/Widgets/TotalCustomersStat.php`:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalCustomersStat extends BaseWidget
{
    protected static ?int $sort = 1;

    public function getStats(): array
    {
        return [
            Stat::make('Customers', User::where('role', 'customer')->whereNull('deleted_at')->count())
                ->description('Total active customer accounts')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
            Stat::make('Designers', User::where('role', 'designer')->whereNull('deleted_at')->count())
                ->descriptionIcon('heroicon-m-paint-brush')
                ->color('warning'),
            Stat::make('Printers', User::where('role', 'printer_provider')->whereNull('deleted_at')->count())
                ->descriptionIcon('heroicon-m-printer')
                ->color('info'),
        ];
    }
}
```

- [ ] **Step 2: Create the revenue chart widget**

`app/Filament/Widgets/RevenueChart.php`:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Revenue (last 30 days)';

    protected function getData(): array
    {
        $data = Trend::model(Payment::class)
            ->between(start: now()->subDays(30), end: now())
            ->perDay()
            ->sum('amount')
            ->where('status', 'confirmed');

        return [
            'datasets' => [['label' => 'Revenue', 'data' => $data->map(fn (TrendValue $v) => $v->aggregate)->toArray()]],
            'labels'   => $data->map(fn (TrendValue $v) => $v->date)->toArray(),
        ];
    }

    protected function getType(): string { return 'line'; }
}
```

- [ ] **Step 3: Install `flowframe/laravel-trend`**

```bash
composer require flowframe/laravel-trend --no-interaction
```

- [ ] **Step 4: Override the default Dashboard with KpiDashboard**

Create `app/Filament/Pages/KpiDashboard.php`:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\{AbandonedCartStat, RecentOrdersTable, RevenueChart, RevenueTodayStat, TotalCustomersStat};
use Filament\Pages\Dashboard;

class KpiDashboard extends Dashboard
{
    protected static string $routePath = '/';

    protected static ?string $title = 'POD Admin';

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected function getHeaderWidgets(): array
    {
        return [
            TotalCustomersStat::class,
            RevenueTodayStat::class,
            PendingOrdersStat::class,
            AbandonedCartStat::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            RevenueChart::class,
            RecentOrdersTable::class,
        ];
    }
}
```

(Implement `RevenueTodayStat`, `PendingOrdersStat`, `AbandonedCartStat` following the same shape as `TotalCustomersStat`; `RecentOrdersTable` extends `Filament\Widgets\TableWidget` and returns a table of the latest 10 orders.)

- [ ] **Step 5: Register the KpiDashboard + remove the default Dashboard**

In `AdminPanelProvider.php`, replace `->pages([Pages\Dashboard::class])` with `->pages([KpiDashboard::class])`.

- [ ] **Step 6: Tests + commit**

```php
public function test_dashboard_loads_for_admin(): void
{
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin)->get('/admin')->assertOk();
}

public function test_dashboard_shows_customer_count(): void
{
    User::factory()->count(7)->create(['role' => 'customer']);
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin)->get('/admin')->assertSee('7');
}
```

Commit with `feat(admin): KPI dashboard with 4 stat widgets + revenue chart + recent orders`.

---

## Task 7: 15 Report pages

**Files:**
- Create: `app/Filament/Pages/Reports/{Overview,RevenueByDay,RevenueByDesigner,RevenueByPrinter,TopDesigns,CustomerLtv,DesignerPayout,PrinterPayout,WorkQueue,CartAbandonment,StuckPayments,StuckShipments,ConversionFunnel,OrderStatusDistribution,RefundRate}Report.php` (15 files)

Each Report page is a Filament Page that wires a `Report` class (from Phase 2) into a Filament Table or chart widget.

- [ ] **Step 1: Create the base ReportPage abstract**

Create `app/Filament/Pages/Reports/BaseReportPage.php`:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use App\Reports\Contracts\Report;
use Filament\Pages\Page;
use Illuminate\Http\Request;

abstract class BaseReportPage extends Page
{
    protected static string $view = 'filament.pages.reports.base';

    /** Must be implemented by subclasses: the Phase 2 Report class. */
    abstract protected function reportClass(): string;

    /** Filters available in the header (date range, etc.). */
    public function getHeaderFiltersSchema(): array
    {
        return [
            \Filament\Forms\Components\DatePicker::make('from')->default(now()->subDays(30)),
            \Filament\Forms\Components\DatePicker::make('to')->default(now()),
            \Filament\Forms\Components\Select::make('format')
                ->options(['json' => 'On screen', 'csv' => 'Download CSV'])
                ->default('json'),
        ];
    }

    public function export()
    {
        $request = new Request([
            'from'   => $this->data['from'] ?? null,
            'to'     => $this->data['to'] ?? null,
            'format' => 'csv',
        ]);

        /** @var Report $report */
        $report = app($this->reportClass());

        return app(\App\Reports\CsvExporter::class)->stream(
            $report->csvHeaders(),
            collect($report->run($request))->toArray(),
            $this->getSlug() . '-' . now()->format('Ymd-His') . '.csv'
        );
    }

    public function getViewData(): array
    {
        $request = new Request([
            'from' => $this->data['from'] ?? null,
            'to'   => $this->data['to'] ?? null,
        ]);

        /** @var Report $report */
        $report = app($this->reportClass());
        $result = $report->run($request);

        return [
            'rows'   => $result,
            'csvUrl' => $this->exportUrl(),
        ];
    }

    protected function exportUrl(): string
    {
        return $this->getUrl() . '?export=csv';
    }
}
```

- [ ] **Step 2: Create the shared Blade view**

Create `resources/views/filament/pages/reports/base.blade.php`:

```blade
<x-filament-panels::page>
    {{ \Filament\Forms\Forms::renderHeader($this) }}

    <div class="flex justify-end mb-4">
        <x-filament::button color="gray" tag="a" :href="$csvUrl">
            Download CSV
        </x-filament::button>
    </div>

    <x-filament-widgets::widgets :widgets="\Filament\Support\Facades\FilamentView::getRenderHooks('footer')" :columns="\Filament\Support\Facades\FilamentView::getRenderHooks('footer.columns')" />

    <div class="overflow-x-auto border-3 border-gray-800 bg-white">
        <table class="fi-ta-table w-full">
            <thead>
                <tr>
                    @foreach (collect($rows)->first() ?? [] as $col => $_)
                        <th class="fi-ta-header-cell">{{ ucwords(str_replace('_', ' ', $col)) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td class="fi-ta-cell">{{ is_array($cell) ? json_encode($cell) : $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="100" class="text-center p-12 font-mono">No data for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
```

- [ ] **Step 3: Build the 15 concrete report pages**

Each is 8 lines — just binds the Phase 2 Report class:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

class OverviewReport extends BaseReportPage
{
    protected static ?string $navigationIcon  = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?string $navigationLabel = 'Overview';
    protected static ?string $title           = 'Platform overview';
    protected static ?int    $navigationSort  = 1;

    protected function reportClass(): string
    {
        return \App\Reports\Admin\PlatformOverviewReport::class;
    }
}
```

Repeat for all 15 (one Report class per page). The exact mapping:

| Page                          | Report class                                          |
| ----------------------------- | ----------------------------------------------------- |
| `OverviewReport`              | `PlatformOverviewReport`                              |
| `RevenueByDayReport`          | `RevenueByDayReport`                                  |
| `RevenueByDesignerReport`     | `RevenueByDesignerReport`                             |
| `RevenueByPrinterReport`      | `RevenueByPrinterReport`                              |
| `TopDesignsReport`            | `TopDesignsReport`                                    |
| `CustomerLtvReport`           | `CustomerLtvReport`                                   |
| `ConversionFunnelReport`      | `ConversionFunnelReport`                              |
| `OrderStatusDistributionReport` | `OrderStatusDistributionReport`                     |
| `RefundRateReport`            | `RefundCancellationRateReport`                        |
| `DesignerPayoutReport`        | `Designer\PayoutReport` (designer scope)              |
| `PrinterPayoutReport`         | `Printer\PayoutReport` (printer scope)                |
| `WorkQueueReport`             | `Printer\WorkQueueReport`                             |
| `CartAbandonmentReport`       | `Ops\CartAbandonmentReport`                           |
| `StuckPaymentsReport`         | `Ops\StuckPaymentsReport`                             |
| `StuckShipmentsReport`        | `Ops\StuckShipmentsReport`                            |

- [ ] **Step 4: Register the navigation group "Reports"**

Already declared in `AdminPanelProvider` (Task 2). Verify each report page has `navigationGroup = 'Reports'`.

- [ ] **Step 5: Write one happy-path test per report page**

```php
public function test_admin_can_view_overview_report(): void
{
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin)->get('/admin/reports/overview')->assertOk();
}

public function test_non_admin_cannot_view_reports(): void
{
    $customer = User::factory()->create(['role' => 'customer']);
    $this->actingAs($customer)->get('/admin/reports/overview')->assertForbidden();
}
```

- [ ] **Step 6: Run + commit**

```bash
php artisan test --compact tests/Feature/Filament/Reports/
git add app/Filament/Pages/Reports resources/views/filament/pages/reports tests/Feature/Filament/Reports
git commit -m "feat(admin): 15 report pages with CSV export + Sand+Coral table"
```

---

## Task 8: Production guardrails for /admin

**Files:**
- Modify: `app/Providers/Filament/AdminPanelProvider.php` (already done in Task 2 — verify)
- Create: `deploy/PRODUCTION.md` (runbook)

- [ ] **Step 1: Verify the admin gate**

Re-run `tests/Feature/Filament/AdminGateTest.php` (3 tests, all passing from Task 3).

- [ ] **Step 2: Document the production runbook**

Create `deploy/PRODUCTION.md`:

```markdown
# Production runbook — POD Platform

## Pre-deploy

1. Set `APP_ENV=production` and `APP_DEBUG=false`.
2. Generate `APP_KEY` (`php artisan key:generate`).
3. Set the database credentials in `.env`:
   - `DB_CONNECTION=pgsql`
   - `DB_HOST=<managed-postgres-host>`
   - `DB_DATABASE=pod_prod`
   - `DB_USERNAME=pod_app`
   - `DB_PASSWORD=<from secrets manager>`
4. Set Redis: `REDIS_HOST=<managed-redis-host>`.
5. Set S3: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`.
6. Set mail: `MAIL_MAILER=ses` (or `postmark`), `MAIL_FROM_ADDRESS=noreply@pod.example.com`.
7. Set Sentry: `SENTRY_LARAVEL_DSN=<dsn>`.
8. Set `SEED_ADMIN_PASSWORD=<generated-strong-password>` (store in 1Password).

## Deploy

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --class=AdminSeeder   # creates ops@pod.local
php artisan config:cache route:cache view:cache
php artisan storage:link
php artisan queue:restart
```

## Post-deploy

1. Rotate `SEED_ADMIN_PASSWORD` once a single admin account is verified.
2. Enforce MFA on the admin account.
3. Document an IP allowlist (recommended, not enforced in code per spec).
4. Verify `/admin/login` renders with the Sand+Coral theme.
5. Verify `/api/docs` requires admin auth (Production admin gate from Phase 1).

## Backups

- Postgres: nightly managed backup, 30-day retention.
- S3 media: versioning enabled, lifecycle rule moves objects >90 days to Glacier.
```

- [ ] **Step 3: Commit**

```bash
git add deploy/PRODUCTION.md
git commit -m "docs(deploy): production runbook with admin rotation policy"
```

---

## Task 9: Audit log for admin actions (in-memory + channel log)

The spec says audit logs for design transfers + admin actions are "admin memory only — Future: dedicated `audit_logs` table". For v1.2 we log to the `audit` log channel (configured in Phase 2).

**Files:**
- Modify: `app/Providers/Filament/AdminPanelProvider.php` (add an observer)
- Create: `app/Observers/AuditAdminActions.php`

- [ ] **Step 1: Create the audit observer**

```php
<?php

declare(strict_types=1);

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class AuditAdminActions
{
    public function created(Model $model): void
    {
        $this->log('created', $model);
    }
    public function updated(Model $model): void
    {
        $this->log('updated', $model, $model->getChanges());
    }
    public function deleted(Model $model): void
    {
        $this->log('deleted', $model);
    }
    public function restored(Model $model): void
    {
        $this->log('restored', $model);
    }

    private function log(string $verb, Model $model, array $changes = []): void
    {
        $user = auth()->user();
        if ($user === null || ! $user->isAdmin()) {
            return;  // only audit admin actions
        }

        Log::channel('audit')->info("admin.{$verb}", [
            'entity'    => $model::class,
            'id'        => $model->getKey(),
            'user_id'   => $user->id,
            'changes'   => $changes,
            'ip'        => request()?->ip(),
        ]);
    }
}
```

- [ ] **Step 2: Register the observer on the auditable models**

In `AppServiceProvider::boot()`:

```php
$auditableModels = [
    \App\Models\User::class,
    \App\Models\Design::class,
    \App\Models\Order::class,
    \App\Models\OrderItem::class,
    \App\Models\Payment::class,
    \App\Models\Shipment::class,
    \App\Models\Setting::class,
];

foreach ($auditableModels as $class) {
    $class::observe(\App\Observers\AuditAdminActions::class);
}
```

- [ ] **Step 3: Test that the audit channel records admin actions**

`tests/Feature/Filament/AuditLogTest.php`:

```php
public function test_admin_update_writes_to_audit_log(): void
{
    Log::channel('audit')->spy();
    $admin = User::factory()->create(['role' => 'admin']);
    $target = User::factory()->create(['role' => 'customer']);

    $this->actingAs($admin)->put("/admin/users/{$target->id}", [
        'name' => 'Updated Name',
        'email' => $target->email,
        'role' => 'customer',
    ]);

    Log::channel('audit')->shouldHaveReceived('info')
        ->withArgs(fn ($message) => str_contains($message, 'admin.updated'))
        ->atLeast()->once();
}

public function test_non_admin_update_does_not_write_audit(): void
{
    Log::channel('audit')->spy();
    $customer = User::factory()->create(['role' => 'customer']);
    $other = User::factory()->create(['role' => 'customer']);

    $this->actingAs($customer)->put("/admin/users/{$other->id}", [])->assertForbidden();

    Log::channel('audit')->shouldNotHaveReceived('info');
}
```

- [ ] **Step 4: Run + commit**

```bash
php artisan test --compact tests/Feature/Filament/AuditLogTest.php
git add app/Observers/AuditAdminActions.php app/Providers/AppServiceProvider.php tests/Feature/Filament/AuditLogTest.php
git commit -m "feat(admin): audit admin actions to dedicated log channel"
```

---

## Task 10: Phase 4 checkpoint — full admin verification

- [ ] **Step 1: Build the assets**

```bash
npm run build
```

- [ ] **Step 2: Run the entire test suite**

```bash
php artisan test --compact
vendor/bin/pint --dirty --format=agent
```

Expected: all green. ~340+ tests in total across Phase 1-4.

- [ ] **Step 3: Manual smoke test**

```bash
php artisan migrate:fresh --seed
php artisan serve &
```

Browser checks:
1. `/admin/login` — themed correctly with coral accents, sharp corners
2. Login as `admin@pod.local` / `password` — see KPI dashboard with stats + chart
3. Navigate to **Catalog → Designs** — list renders, click Edit, change a field, save — success
4. Navigate to **Operations → Orders** — click View on an order — cancel button works, status changes
5. Navigate to **Reports → Revenue by Day** — table renders; **Download CSV** returns CSV
6. Logout; visit `/admin` as a non-admin user — 403

- [ ] **Step 4: Production-mode smoke test**

```bash
APP_ENV=production APP_DEBUG=false php artisan serve &
curl -sI http://localhost:8000/admin
```

Expected: `302 Found` to `/admin/login`. Log in as admin → 200.

- [ ] **Step 5: Tag + push**

```bash
git tag -a phase-4-filament-admin -m "Phase 4 complete — Filament admin at /admin with Sand+Coral theme"
git push origin local-smart-shot --tags
```

---

## Phase 4 Summary

| Deliverable                       | Where                                                  | Verification                              |
| --------------------------------- | ------------------------------------------------------ | ----------------------------------------- |
| Filament v3 install + theme       | `app/Providers/Filament/AdminPanelProvider.php`        | `/admin` renders                          |
| Sand+Coral Filament CSS           | `resources/css/filament-admin.css`                     | Coral accents, 3px borders, no radius      |
| 13 Resources                      | `app/Filament/Resources/*`                             | Admin can CRUD every model                |
| KPI dashboard                     | `app/Filament/Pages/KpiDashboard.php` + 6 widgets      | Stats + chart render                      |
| 15 Report pages                   | `app/Filament/Pages/Reports/*`                         | Each renders table; CSV downloads          |
| Admin gate                        | `EnsureAdmin` middleware                               | Non-admin → 403                           |
| Audit logging                     | `app/Observers/AuditAdminActions.php`                  | Log channel receives admin.update events  |
| Production runbook                | `deploy/PRODUCTION.md`                                 | Manual review                              |
| ~340 tests                        | `tests/Feature/Filament/*`                             | `php artisan test --compact` green        |
| `phase-4-filament-admin` tag       | git tag                                                | `git tag -l`                              |

---

## Final Notes

- **Cross-phase integration:** Phase 4's Filament Resources are read/write over the same Eloquent models that Phase 2's API serves. There is no separate admin database or admin API — both surfaces hit the same tables.
- **Theme drift mitigation:** Both Phase 3 (`resources/css/app.css`) and Phase 4 (`resources/css/filament-admin.css`) define `@theme` blocks. If a designer changes the Sand+Coral palette, do it in both files in the same commit.
- **Maintenance contract:** All new admin features land in Phase 4 as both a Filament Resource (for staff) and Phase 2 routes (for API consumers). If you add an endpoint, you also add a Resource page; if you add a Resource page, you also document the endpoint in `OpenApi.php`.
- **Pre-launch checklist:** Run `php artisan test --compact`, run `npm run build`, run `vendor/bin/pint`, verify `/admin`, `/api/docs`, `/`, `/designs`, `/cart` all render with the Sand+Coral theme. Deploy from `main` (or whatever merged `local-smart-shot`) using `deploy/PRODUCTION.md`.
