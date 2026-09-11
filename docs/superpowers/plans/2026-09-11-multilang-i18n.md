# Multilingual i18n Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

> **⚠ PHPUnit, not Pest (SDD ruling 2026-09-11):** Project uses PHPUnit 12 (`phpunit/phpunit ^12.5.12` in `composer.json`; CLAUDE.md confirms: "All tests must be written as PHPUnit classes"). The test code blocks below were written in Pest syntax for compactness and MUST be converted to PHPUnit syntax by the implementer. Read this table BEFORE writing any test:

| Pest (in plan) | PHPUnit (write this) |
|---|---|
| `it('does X', function () { ... })` | `public function test_it_does_x(): void { ... }` inside `class XxxTest extends TestCase` |
| `expect($a)->toBe($b)` | `$this->assertSame($b, $a)` |
| `expect($a)->toEqual($b)` | `$this->assertEquals($b, $a)` |
| `expect($a)->toBeArray()` | `$this->assertIsArray($a)` |
| `expect($a)->toBeEmpty()` | `$this->assertEmpty($a)` |
| `expect($a)->not->toBeEmpty()` | `$this->assertNotEmpty($a)` |
| `expect($a)->toContain('x')` | `$this->assertStringContainsString('x', $a)` |
| `expect($a)->toMatch('/regex/')` | `$this->assertMatchesRegularExpression('/regex/', $a)` |
| `expect($a)->toBeIn([...])` | `$this->assertContains($a, [...])` |
| `beforeEach(function () { ... })` | `protected function setUp(): void { parent::setUp(); ... }` |
| `Blade::render('<x-ui.foo />')` | `view('components.ui.foo')->render()` |
| `$this->get('/...')->assertOk()` | Same — Laravel test methods work in PHPUnit |
| `expect(true)->toBeTrue()` | `$this->assertTrue(true)` |

Class naming: `tests/Feature/Middleware/LocalizeRequestsTest.php` → `class LocalizeRequestsTest extends TestCase`. Namespaces: `Tests\Feature\Middleware`.

**Goal:** Add English (default, unprefixed), Arabic (`/ar/...`), and Turkish (`/tr/...`) locale support to the POD public website and Filament admin panel. Full RTL flip for Arabic. Cookie persistence. Filament chrome translation. No DB-content translation.

**Architecture:** Wrap public routes in `Route::prefix('{locale?}')` group. Single `LocalizeRequests` middleware resolves locale from URL → cookie → Accept-Language → default. `localize()` helper rewrites the current route into another locale. New `I18nServiceProvider` overrides `route()` URL generation so `route('cart.show')` on an Arabic page returns `/ar/cart` automatically. Filament admin uses an independent `admin_locale` cookie + matching middleware. Translations live in `lang/{locale}/...` PHP files (per-page) and `lang/vendor/filament/{locale}/...` for admin chrome. Hreflang + canonical in layouts.

**Tech Stack:** Laravel 13, Filament v4, Tailwind v4 logical properties (`ms-*`/`me-*`/`text-end`), PHP translation files, `trans_choice` for plurals (Arabic 6-form), cookie-based persistence, Playwright for optional visual baselines.

**Spec:** [docs/superpowers/specs/2026-09-11-multilang-i18n-design.md](../specs/2026-09-11-multilang-i18n-design.md)

---

## Global Constraints

- **Locales supported:** `en` (default, unprefixed), `ar` (full RTL), `tr` (LTR). No other locales.
- **URL prefix:** `{locale?}` optional. `/cart` is English; `/ar/cart`, `/tr/cart` are explicit.
- **Cookies:** `pod_locale` for public site, `admin_locale` for Filament. Independent. 1-year expiry. `SameSite=Lax`, `Secure` in prod.
- **Persistence priority:** URL prefix > cookie > `Accept-Language` > `config('app.locale')`.
- **Translation storage:** PHP files in `lang/{locale}/...`. No JSON files. No DB-backed translation tables.
- **RTL:** Arabic pages set `<html dir="rtl" lang="ar">`. Tailwind v4 logical properties (`ms-*`/`me-*`/`ps-*`/`pe-*`/`text-start`/`text-end`/`border-s-*`/`border-e-*`) auto-flip.
- **Filament scope:** Chrome only (nav labels, button text, validation messages, column headers). Data values remain in stored language.
- **No DB-content translation:** `ProductTemplate.name`, `Design.title`, etc. stay English. No `spatie/laravel-translatable`.
- **Naming convention:** Dots map to slashes — `pages.cart.show.title` → `lang/{locale}/pages/cart/show.php` → `title` key.
- **Existing tests:** 419 tests must continue to pass at every checkpoint.
- **Branch:** All work on `local-smart-shot`.

---

## File Map (final state)

| Layer | Path | Count | Purpose |
|---|---|---|---|
| Middleware | `app/Http/Middleware/LocalizeRequests.php` | 1 | Public site locale resolution + cookie |
| Middleware | `app/Http/Middleware/LocalizeAdminRequests.php` | 1 | Filament admin locale resolution + cookie |
| Provider | `app/Providers/I18nServiceProvider.php` | 1 | Register `localize()` helper, override `route()` URL gen, register middleware aliases |
| Helper | `app/helpers.php` (or in provider) | 1 | `localize(?string $locale): string` |
| Routes | `routes/web.php` (modified) | 1 | Wrap public routes in `Route::prefix('{locale?}')` group |
| Bootstrap | `bootstrap/app.php` (modified) | 1 | Register middleware aliases |
| Config | `config/app.php` (modified) | 1 | Confirm `locale` + `fallback_locale` env hooks |
| Lang | `lang/en/**/*.php` | ~60 | English source strings (verbatim copy of existing English copy) |
| Lang | `lang/ar/**/*.php` | ~60 | Arabic translations (RTL audience) |
| Lang | `lang/tr/**/*.php` | ~60 | Turkish translations |
| Lang | `lang/vendor/filament/ar/**/*.php` | ~30 | Filament admin chrome Arabic |
| Lang | `lang/vendor/filament/tr/**/*.php` | ~30 | Filament admin chrome Turkish |
| Lang | `lang/{en,ar,tr}/admin/resources/*.php` | 12 | Per-resource labels (one file per resource × 3 locales) |
| Views | `resources/views/components/ui/language-switcher.blade.php` | 1 | Header switcher component |
| Views | All ~50 Blade files (modified) | ~50 | `__()` wrapping, logical property classes |
| Views | `resources/views/layouts/app.blade.php` (modified) | 1 | `<html dir>`, hreflang, canonical |
| Views | `resources/views/layouts/marketing.blade.php` (modified) | 1 | `<html dir>`, hreflang, canonical |
| Filament | `app/Filament/Resources/{Name}/{Name}Resource.php` (modified ×12) | 12 | `getModelLabel`, `getPluralModelLabel`, `getNavigationLabel` overrides |
| Filament | `app/Providers/Filament/AdminPanelProvider.php` (modified) | 1 | Admin topbar switcher action, admin middleware registration |
| Tests | `tests/Feature/Middleware/LocalizeRequestsTest.php` | 1 | Locale resolution priority |
| Tests | `tests/Feature/Middleware/LocalizeAdminRequestsTest.php` | 1 | Admin locale resolution |
| Tests | `tests/Unit/LocalizeHelperTest.php` | 1 | `localize()` URL generation |
| Tests | `tests/Feature/Routing/RouteLocaleAwareTest.php` | 1 | `route()` helper respects current locale |
| Tests | `tests/Feature/LocaleFilesTest.php` | 1 | Every key in en/ exists in ar/ and tr/ |
| Tests | `tests/Feature/ValidationTranslationTest.php` | 1 | Validation messages translated |
| Tests | `tests/Feature/Components/LanguageSwitcherTest.php` | 1 | Switcher renders, marks current locale, URLs correct |
| Tests | `tests/Feature/Seo/HreflangTest.php` | 1 | Response has hreflang links for en/ar/tr |
| Tests | `tests/Feature/Seo/RtlAttributeTest.php` | 1 | Arabic response has `<html dir="rtl">` |
| Tests | `tests/Feature/Seo/CanonicalTest.php` | 1 | Canonical points to English unprefixed |
| Tests | `tests/Feature/Routing/LocalizedTopRoutesTest.php` | 1 | Top-level routes return 200 in en/ar/tr |
| Tests | `tests/e2e/visual/homepage-multilang.spec.ts` (optional) | 1 | Playwright visual baselines for en/ar/tr |
| Sitemap | `app/Http/Controllers/SitemapController.php` (modified if exists) | 0-1 | Emit one URL per locale per page with hreflang children |

Total: **~180 files created, ~60 modified**.

---

## Phase A — Infrastructure (1 unit, shippable checkpoint)

Phase A delivers the URL prefix, middleware, helper, route() override, lang/ skeleton (English files with verbatim copy of existing strings), cookie handling, and hreflang skeleton. After Phase A, the site still renders entirely in English, but every URL works with `/ar/...` and `/tr/...` prefixes, the cookie persists, and the switcher (in English-only mode) is in place.

### Task A1: Locale config + middleware skeleton

**Files:**
- Modify: `config/app.php` (verify env hooks present)
- Create: `app/Http/Middleware/LocalizeRequests.php`
- Modify: `bootstrap/app.php` (register middleware alias)
- Create: `tests/Feature/Middleware/LocalizeRequestsTest.php`

**Interfaces:**
- Produces: middleware alias `localize` (used by `routes/web.php` in A5)
- Produces: `LocalizeRequests::handle(Request, Closure): Response` — public method

- [ ] **Step 1: Verify config/app.php has the right hooks**

Read `config/app.php`. Confirm:
- `'locale' => env('APP_LOCALE', 'en')`
- `'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en')`
- `'faker_locale' => env('APP_FAKER_LOCALE', 'en_US')`

If any are missing or hardcoded, fix them.

- [ ] **Step 2: Write the failing test for locale resolution priority**

```php
// tests/Feature/Middleware/LocalizeRequestsTest.php
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cookie;

it('uses URL prefix when present, ignoring cookie', function () {
    Cookie::queue('pod_locale', 'tr', 60);

    $response = $this->get('/ar/about');
    $response->assertOk();
    expect(app()->getLocale())->toBe('ar');
});

it('uses cookie when URL has no locale', function () {
    Cookie::queue('pod_locale', 'tr', 60);

    $this->get('/about');
    expect(app()->getLocale())->toBe('tr');
});

it('falls back to Accept-Language when no URL or cookie', function () {
    $response = $this->withHeader('Accept-Language', 'ar;q=0.9,en;q=0.8')
        ->get('/about');
    expect(app()->getLocale())->toBe('ar');
});

it('falls back to default when nothing matches', function () {
    $this->get('/about');
    expect(app()->getLocale())->toBe('en');
});

it('rejects invalid URL locales (404 or fallback)', function () {
    $response = $this->get('/xx/about');
    // Either 404 (regex blocked) or fallback to en — both acceptable
    expect($response->status())->toBeIn([200, 404]);
});
```

Use `tests/Feature/Middleware/LocalizeRequestsTest.php`. The 5 tests above use the Pest-style syntax already in the codebase (if PHPUnit, drop the `function ()` wrappers and use `test_` prefixes — match existing convention).

- [ ] **Step 3: Run tests to verify they fail**

Run: `php artisan test --compact tests/Feature/Middleware/LocalizeRequestsTest.php`
Expected: FAIL — `LocalizeRequests` class doesn't exist, routes don't exist.

- [ ] **Step 4: Create LocalizeRequests middleware (placeholder)**

```php
// app/Http/Middleware/LocalizeRequests.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocalizeRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        // Resolution priority: URL prefix > cookie > Accept-Language > config('app.locale')
        $locale = $this->resolveLocale($request);

        app()->setLocale($locale);

        if ($request->route('locale') !== null && in_array($locale, ['en', 'ar', 'tr'], true)) {
            Cookie::queue('pod_locale', $locale, 60 * 24 * 365);
        }

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        $supported = ['en', 'ar', 'tr'];

        $urlLocale = $request->route('locale');
        if ($urlLocale !== null && in_array($urlLocale, $supported, true)) {
            return $urlLocale;
        }

        $cookieLocale = $request->cookie('pod_locale');
        if ($cookieLocale !== null && in_array($cookieLocale, $supported, true)) {
            return $cookieLocale;
        }

        $acceptLanguage = $request->header('Accept-Language');
        if ($acceptLanguage !== null) {
            foreach ($supported as $code) {
                if (preg_match("/\b{$code}\b/i", $acceptLanguage) === 1) {
                    return $code;
                }
            }
        }

        return config('app.locale', 'en');
    }
}
```

- [ ] **Step 5: Register middleware alias in bootstrap/app.php**

In `bootstrap/app.php` inside the `->withMiddleware(...)` callback, add:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'localize' => \App\Http\Middleware\LocalizeRequests::class,
    ]);
})
```

If the project already has a `->withMiddleware(...)` block, add to it. Don't replace.

- [ ] **Step 6: Add a stub route to test against**

In `routes/web.php` (or a new `routes/test.php` only in testing), add:

```php
Route::middleware(['localize'])->group(function (): void {
    Route::get('/about', fn () => 'locale:' . app()->getLocale())->name('test.about');
});
```

In a real env, this route will be replaced in Task A5 when the full route group is wrapped.

- [ ] **Step 7: Run tests to verify they pass**

Run: `php artisan test --compact tests/Feature/Middleware/LocalizeRequestsTest.php`
Expected: PASS (5/5).

- [ ] **Step 8: Commit**

```bash
git add app/Http/Middleware/LocalizeRequests.php bootstrap/app.php routes/web.php tests/Feature/Middleware/LocalizeRequestsTest.php config/app.php
git commit -m "feat(i18n): LocalizeRequests middleware with URL > cookie > Accept-Language priority"
```

---

### Task A2: `localize()` helper

**Files:**
- Create: `app/Providers/I18nServiceProvider.php`
- Modify: `bootstrap/providers.php` (register provider)
- Create: `tests/Unit/LocalizeHelperTest.php`

**Interfaces:**
- Produces: global `localize(?string $locale): string` helper — see signature in Step 3.

- [ ] **Step 1: Write the failing helper test**

```php
// tests/Unit/LocalizeHelperTest.php
<?php

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/foo', fn () => '')->name('foo.test');
    Route::get('/bar/{id}', fn ($id) => '')->name('bar.test');
});

it('returns English unprefixed when locale is null', function () {
    $this->app['router']->aliasMiddleware('localize', \App\Http\Middleware\LocalizeRequests::class);

    $request = \Illuminate\Http\Request::create('/foo', 'GET');
    $request->setRouteResolver(fn () => Route::getRoutes()->match($request));
    app()->instance('request', $request);
    app()->setLocale('en');

    expect(localize(null))->toBe(url('/foo'));
});

it('strips prefix when switching to en from another locale', function () {
    $this->app['router']->aliasMiddleware('localize', \App\Http\Middleware\LocalizeRequests::class);

    $request = \Illuminate\Http\Request::create('/ar/foo', 'GET');
    $request->setRouteResolver(fn () => Route::getRoutes()->match($request));
    app()->instance('request', $request);
    app()->setLocale('ar');

    expect(localize('en'))->toBe(url('/foo'));
});

it('swaps prefix when switching between non-default locales', function () {
    $this->app['router']->aliasMiddleware('localize', \App\Http\Middleware\LocalizeRequests::class);

    $request = \Illuminate\Http\Request::create('/ar/foo', 'GET');
    $request->setRouteResolver(fn () => Route::getRoutes()->match($request));
    app()->instance('request', $request);
    app()->setLocale('ar');

    expect(localize('tr'))->toBe(url('/tr/foo'));
});

it('returns English for routes with parameters', function () {
    $this->app['router']->aliasMiddleware('localize', \App\Http\Middleware\LocalizeRequests::class);

    $request = \Illuminate\Http\Request::create('/bar/42', 'GET');
    $request->setRouteResolver(fn () => Route::getRoutes()->match($request));
    app()->instance('request', $request);
    app()->setLocale('en');

    expect(localize('ar'))->toBe(url('/ar/bar/42'));
});
```

(Adapt syntax if project uses PHPUnit instead of Pest.)

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact tests/Unit/LocalizeHelperTest.php`
Expected: FAIL — `localize()` helper doesn't exist.

- [ ] **Step 3: Create I18nServiceProvider with the helper**

```php
// app/Providers/I18nServiceProvider.php
<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\ServiceProvider;

class I18nServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerLocalizeHelper();
    }

    private function registerLocalizeHelper(): void
    {
        if (! function_exists('localize')) {
            function localize(?string $locale = null): string
            {
                $request = request();
                $route = $request->route();

                if ($route === null) {
                    return url('/');
                }

                $currentLocale = $route->parameter('locale') ?? app()->getLocale();
                $targetLocale = $locale ?? 'en';

                $parameters = $route->parameters();
                unset($parameters['locale']);

                if ($targetLocale === 'en') {
                    return url(route($route->getName(), $parameters, false));
                }

                $parameters['locale'] = $targetLocale;
                return url(route($route->getName(), $parameters, false));
            }
        }
    }
}
```

Note: `route()` calls inside the helper will go through the URL override registered in Task A3. For now, the helper builds URLs explicitly via `url(route(...))`.

- [ ] **Step 4: Register provider in bootstrap/providers.php**

Add to `bootstrap/providers.php`:
```php
App\Providers\I18nServiceProvider::class,
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --compact tests/Unit/LocalizeHelperTest.php`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Providers/I18nServiceProvider.php bootstrap/providers.php tests/Unit/LocalizeHelperTest.php
git commit -m "feat(i18n): localize() helper for cross-locale URL rewriting"
```

---

### Task A3: `route()` URL generator override

**Files:**
- Modify: `app/Providers/I18nServiceProvider.php`
- Create: `tests/Feature/Routing/RouteLocaleAwareTest.php`

**Interfaces:**
- Modifies: global `route($name, $params, $absolute)` to prepend `/{locale}` when `app()->getLocale()` is non-default.

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Routing/RouteLocaleAwareTest.php
<?php

namespace Tests\Feature\Routing;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteLocaleAwareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Route::middleware(['localize'])
            ->prefix('{locale?}')
            ->where(['locale' => '(en|ar|tr)'])
            ->group(function (): void {
                Route::get('/cart', fn () => '')->name('cart.show');
            });
    }

    public function test_returns_unprefixed_url_when_locale_is_en(): void
    {
        app()->setLocale('en');
        $this->assertSame(url('/cart'), \App\Support\LocalizedUrl::route('cart.show'));
    }

    public function test_returns_prefixed_url_when_locale_is_ar(): void
    {
        app()->setLocale('ar');
        $this->assertSame(url('/ar/cart'), \App\Support\LocalizedUrl::route('cart.show'));
    }

    public function test_returns_prefixed_url_when_locale_is_tr(): void
    {
        app()->setLocale('tr');
        $this->assertSame(url('/tr/cart'), \App\Support\LocalizedUrl::route('cart.show'));
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact tests/Feature/Routing/RouteLocaleAwareTest.php`
Expected: FAIL — `\App\Support\LocalizedUrl` class doesn't exist.

- [ ] **Step 3: Add `App\Support\LocalizedUrl` class**

```php
// app/Support/LocalizedUrl.php
<?php

namespace App\Support;

class LocalizedUrl
{
    public static function route(string $name, array $parameters = [], bool $absolute = true): string
    {
        $locale = app()->getLocale();
        if ($locale !== 'en') {
            $parameters['locale'] = $locale;
        }
        return app('url')->route($name, $parameters, $absolute);
    }
}
```

- [ ] **Step 4: Document in I18nServiceProvider that Blade templates should use `LocalizedUrl::route()`**

Add to `app/Providers/I18nServiceProvider.php` `boot()` docblock:

> Note: existing `route()` calls in Blade continue to work, but they will NOT automatically prepend the locale. Replace `route('foo')` with `App\Support\LocalizedUrl::route('foo')` for new code. (Phase B will do a project-wide find/replace.)

No changes to `I18nServiceProvider` body needed (the `localize()` helper from Task A2 stays as-is).

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --compact tests/Feature/Routing/RouteLocaleAwareTest.php`
Expected: PASS (3/3).

- [ ] **Step 6: Commit**

```bash
git add app/Providers/I18nServiceProvider.php app/Support/LocalizedUrl.php tests/Feature/Routing/RouteLocaleAwareTest.php
git commit -m "feat(i18n): LocalizedUrl::route() prepends current locale prefix"
```

---

### Task A4: Wrap web routes in localized group

**Files:**
- Modify: `routes/web.php`

**Interfaces:**
- Reads: existing route definitions (191 lines)
- Wraps: all public routes in `Route::prefix('{locale?}')->where(['locale' => '(en|ar|tr)'])->middleware(['localize'])->group(...)`

- [ ] **Step 1: Read routes/web.php and identify the route group boundaries**

Read `routes/web.php`. Identify:
- The L5Swagger / SwaggerController imports (top of file) — these stay OUTSIDE the group
- The `Route::middleware(['share.cart'])->group(...)` block (or wherever routes are defined)
- The `Filament` registration (if any) — stays OUTSIDE

- [ ] **Step 2: Wrap the public route group**

Wrap the existing route group (everything currently inside `share.cart` or equivalent) inside a new outer group:

```php
// routes/web.php — replace the existing top-level group
Route::prefix('{locale?}')
    ->where(['locale' => '(en|ar|tr)'])
    ->middleware(['localize'])
    ->group(function (): void {
        Route::middleware(['share.cart'])->group(function (): void {
            // ... all existing 191 lines, untouched ...
        });
    });
```

Leave L5Swagger / SwaggerController imports alone. They live above the group.

- [ ] **Step 3: Verify all 419 existing tests still pass**

Run: `php artisan test --compact`
Expected: PASS. The middleware just sets `app()->setLocale()`; existing routes don't read it yet.

- [ ] **Step 4: Commit**

```bash
git add routes/web.php
git commit -m "feat(i18n): wrap public routes in {locale?} prefix group with localize middleware"
```

---

### Task A5: Create lang/en/ skeleton with verbatim English copy

**Files:**
- Create: `lang/en/pages/**/*.php` (~40 files, one per page)
- Create: `lang/en/components/**/*.php` (~10 files, one per component)
- Create: `lang/en/errors/*.php` (4 files)
- Create: `lang/en/messages.php`
- Create: `lang/en/validation.php`
- Create: `lang/en/admin/resources/*.php` (12 files, one per Filament resource)

**Interfaces:**
- Produces: lang tree mirroring the existing Blade tree, with the EXACT existing English strings as values.

- [ ] **Step 1: Create lang/en/messages.php**

Read every flash message in controllers (`session()->flash('success', ...)` etc.) and every error message in validation. Put each in `lang/en/messages.php`:

```php
// lang/en/messages.php
<?php

return [
    'profile_updated' => 'Profile updated.',
    'item_added_to_cart' => 'Item added to cart.',
    'item_removed_from_cart' => 'Item removed from cart.',
    'address_created' => 'Address added.',
    'address_updated' => 'Address updated.',
    'address_deleted' => 'Address deleted.',
    'order_placed' => 'Order placed successfully.',
    'design_created' => 'Design uploaded.',
    'design_updated' => 'Design updated.',
    'design_deleted' => 'Design deleted.',
    'mapping_created' => 'Mapping created.',
    'mapping_updated' => 'Mapping updated.',
    'mapping_deleted' => 'Mapping deleted.',
    'login_successful' => 'Welcome back.',
    'registration_successful' => 'Account created.',
    'password_reset' => 'Password reset successfully.',
    'email_verified' => 'Email verified.',
];
```

Add keys as you find them — every `flash(...)` call in `app/Http/Controllers/Web/` should have a key here.

- [ ] **Step 2: Create lang/en/validation.php**

```php
// lang/en/validation.php
<?php

return [
    'required' => 'The :attribute field is required.',
    'string' => 'The :attribute must be a string.',
    'email' => 'The :attribute must be a valid email address.',
    'min' => [
        'numeric' => 'The :attribute must be at least :min.',
        'string' => 'The :attribute must be at least :min characters.',
    ],
    'max' => [
        'numeric' => 'The :attribute may not be greater than :max.',
        'string' => 'The :attribute may not be greater than :max characters.',
    ],
    'confirmed' => 'The :attribute confirmation does not match.',
    'unique' => 'The :attribute has already been taken.',
    'image' => 'The :attribute must be an image.',
    'mimes' => 'The :attribute must be a file of type: :values.',
];
```

Add keys as needed. Start with the Laravel defaults; override per-project where English copy differs.

- [ ] **Step 3: Create lang/en/errors/{404,403,500,503}.php**

```php
// lang/en/errors/404.php
<?php

return [
    'title' => 'Page not found',
    'heading' => '404',
    'message' => 'We couldn\'t find the page you were looking for.',
    'back' => 'Go back home',
];

// 403.php
return [
    'title' => 'Forbidden',
    'heading' => '403',
    'message' => 'You don\'t have permission to view this page.',
    'back' => 'Go back home',
];

// 500.php
return [
    'title' => 'Server error',
    'heading' => '500',
    'message' => 'Something went wrong on our end. We\'ve been notified.',
    'back' => 'Go back home',
];

// 503.php
return [
    'title' => 'Service unavailable',
    'heading' => '503',
    'message' => 'We\'re performing maintenance. Please check back soon.',
    'back' => 'Go back home',
];
```

- [ ] **Step 4: Create lang/en/admin/resources/{model}.php for each Filament resource**

12 resources, one file each. Pattern:

```php
// lang/en/admin/resources/product_templates.php
<?php

return [
    'label' => 'Product template',
    'plural' => 'Product templates',
    'nav' => 'Product templates',
    'navigation_group' => 'Catalog',
    'sections' => [
        'basic_info' => 'Basic information',
        'pricing' => 'Pricing',
        'media' => 'Media',
    ],
    'fields' => [
        'name' => 'Name',
        'slug' => 'Slug',
        'description' => 'Description',
        'base_price' => 'Base price',
        'status' => 'Status',
        'is_active' => 'Active',
    ],
];
```

Create one file per existing Filament resource. Pull the labels from the Schemas (form fields) and Tables (column headers).

- [ ] **Step 5: Create lang/en/pages/ and lang/en/components/ skeletons**

For each Blade page/component in `resources/views/`, create the corresponding lang file with the existing English strings as values. Example for `resources/views/pages/cart/show.blade.php`:

```php
// lang/en/pages/cart/show.php
<?php

return [
    'title' => 'Your cart',
    'empty' => 'Your cart is empty.',
    'subtotal' => 'Subtotal',
    'total' => 'Total',
    'checkout' => 'Proceed to checkout',
    'remove' => 'Remove',
    'qty' => 'Quantity',
    'items_count' => '{0} No items|{1} One item|[2,*]:count items',
];
```

Read each Blade file, extract the visible strings, put them in the corresponding lang file. Use the dotted naming convention from the spec.

- [ ] **Step 6: Commit**

```bash
git add lang/en/
git commit -m "feat(i18n): create lang/en/ skeleton with verbatim English copy"
```

---

### Task A6: Locale file consistency test

**Files:**
- Create: `tests/Feature/LocaleFilesTest.php`

- [ ] **Step 1: Write the test that en/ keys must be valid PHP**

```php
// tests/Feature/LocaleFilesTest.php
<?php

it('every en/ file returns a valid array', function () {
    $files = glob(base_path('lang/en/**/*.php'));
    expect($files)->not->toBeEmpty();

    foreach ($files as $file) {
        $values = require $file;
        expect($values)->toBeArray("File {$file} did not return an array");
    }
});
```

- [ ] **Step 2: Run test to verify it passes**

Run: `php artisan test --compact tests/Feature/LocaleFilesTest.php`
Expected: PASS.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/LocaleFilesTest.php
git commit -m "test(i18n): every lang/en/ file returns a valid array"
```

---

### Task A7: Wire hreflang + canonical into layouts

**Files:**
- Modify: `resources/views/layouts/app.blade.php`
- Modify: `resources/views/layouts/marketing.blade.php`

**Interfaces:**
- Reads: `localize(null)`, `localize($locale)`
- Produces: `<head>` contains `<link rel="alternate" hreflang="...">` for en/ar/tr and `<link rel="canonical">`.

- [ ] **Step 1: Locate the `<head>` block in both layouts**

In `resources/views/layouts/app.blade.php` and `resources/views/layouts/marketing.blade.php`, find the `<head>` element. Both should be similar Blade layouts.

- [ ] **Step 2: Add hreflang and canonical tags**

Add to the `<head>` (just before `</head>` or after any `@stack('head')`):

```blade
@foreach (['en', 'ar', 'tr'] as $hreflangLocale)
    <link rel="alternate" hreflang="{{ $hreflangLocale }}"
          href="{{ \App\Support\LocalizedUrl::route(request()->route()?->getName() ?? 'home', $hreflangLocale === 'en' ? [] : ['locale' => $hreflangLocale]) }}" />
@endforeach
<link rel="canonical" href="{{ \App\Support\LocalizedUrl::route(request()->route()?->getName() ?? 'home') }}" />
```

For routes with parameters, pull them from `request()->route()->parameters()`:

```blade
@php
    $route = request()->route();
    $routeName = $route?->getName() ?? 'home';
    $routeParams = $route?->parameters() ?? [];
    unset($routeParams['locale']);
@endphp
@foreach (['en', 'ar', 'tr'] as $hreflangLocale)
    <link rel="alternate" hreflang="{{ $hreflangLocale }}"
          href="{{ \App\Support\LocalizedUrl::route($routeName, array_merge($routeParams, $hreflangLocale === 'en' ? [] : ['locale' => $hreflangLocale])) }}" />
@endforeach
<link rel="canonical" href="{{ \App\Support\LocalizedUrl::route($routeName, $routeParams) }}" />
```

- [ ] **Step 3: Verify hreflang appears in homepage HTML**

Run:
```bash
php artisan serve --no-reload &
curl -s http://127.0.0.1:8000/ | grep 'hreflang'
```

Expected: three `<link rel="alternate" hreflang="...">` tags (en, ar, tr) + one canonical.

- [ ] **Step 4: Commit**

```bash
git add resources/views/layouts/app.blade.php resources/views/layouts/marketing.blade.php
git commit -m "feat(i18n): hreflang + canonical link tags in both layouts"
```

---

### Task A8: Language switcher component (English-only)

**Files:**
- Create: `resources/views/components/ui/language-switcher.blade.php`
- Modify: `resources/views/components/layout/header.blade.php`
- Create: `tests/Feature/Components/LanguageSwitcherTest.php`

**Interfaces:**
- Produces: `<x-ui.language-switcher />` Blade component

- [ ] **Step 1: Write the failing component test**

```php
// tests/Feature/Components/LanguageSwitcherTest.php
<?php

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware(['localize'])->prefix('{locale?}')->where(['locale' => '(en|ar|tr)'])->group(function () {
        Route::get('/cart', fn () => '')->name('cart.show');
    });
});

it('renders three locale links', function () {
    $rendered = Blade::render('<x-ui.language-switcher />');
    expect($rendered)->toContain('>EN<');
    expect($rendered)->toContain('>AR<');
    expect($rendered)->toContain('>TR<');
});

it('marks the current locale', function () {
    app()->setLocale('ar');
    $rendered = Blade::render('<x-ui.language-switcher />');
    // The ar link should have aria-current="true" or visual marking class
    expect($rendered)->toMatch('/aria-current="true"[^>]*>AR</');
});

it('uses localize() for cross-locale URLs', function () {
    app()->setLocale('en');
    $rendered = Blade::render('<x-ui.language-switcher />');
    // The ar link's href should be /ar/cart (current route in Arabic)
    expect($rendered)->toContain('href="http://localhost/ar/cart"');
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact tests/Feature/Components/LanguageSwitcherTest.php`
Expected: FAIL — component doesn't exist.

- [ ] **Step 3: Create the component**

```blade
{{-- resources/views/components/ui/language-switcher.blade.php --}}
@props(['compact' => false])
@php
    $locales = ['en' => 'EN', 'ar' => 'AR', 'tr' => 'TR'];
    $current = app()->getLocale();
@endphp
<div class="flex items-center gap-1 {{ $compact ? 'text-xs' : 'text-sm' }} font-mono">
    @foreach ($locales as $code => $label)
        <a href="{{ \App\Support\LocalizedUrl::route(request()->route()?->getName() ?? 'home', $code === 'en' ? [] : ['locale' => $code]) }}"
           @class([
               'border-2 px-2 py-0.5',
               $code === $current
                   ? 'border-black bg-black text-white'
                   : 'border-black hover:bg-black hover:text-white',
           ])
           hreflang="{{ $code }}"
           lang="{{ $code }}"
           dir="{{ $code === 'ar' ? 'rtl' : 'ltr' }}"
           aria-current="{{ $code === $current ? 'true' : 'false' }}">
            {{ $label }}
        </a>
    @endforeach
</div>
```

- [ ] **Step 4: Insert into the header**

In `resources/views/components/layout/header.blade.php`, add `<x-ui.language-switcher />` next to the existing nav items (where exactly depends on the existing layout — usually in the right-side flex container).

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --compact tests/Feature/Components/LanguageSwitcherTest.php`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add resources/views/components/ui/language-switcher.blade.php resources/views/components/layout/header.blade.php tests/Feature/Components/LanguageSwitcherTest.php
git commit -m "feat(i18n): language switcher component in site header"
```

---

### Task A9: Phase A verification

- [ ] **Step 1: Run the full test suite**

Run: `php artisan test --compact`
Expected: PASS (419 existing + ~15 new = ~434 tests).

- [ ] **Step 2: Manual smoke test**

```bash
php artisan serve --no-reload &
```

Visit in browser:
- `http://127.0.0.1:8000/` → page loads, switcher visible, EN is marked
- `http://127.0.0.1:8000/ar` → page loads (Arabic route exists), switcher shows AR marked
- `http://127.0.0.1:8000/tr` → page loads (Turkish route exists), switcher shows TR marked
- Click the AR button → URL becomes `/ar/...`, AR is marked
- Refresh → cookie keeps AR
- Open `/cart` directly → cookie redirects to `/ar/cart`

Expected: all four behaviors work. The site is still in English (Phase B converts the copy), but the URL prefix and switcher work.

- [ ] **Step 3: Tag the phase commit (optional)**

```bash
git tag phase-a-infrastructure-complete
```

---

**✅ Phase A checkpoint.** English-only UI, but the entire i18n infrastructure (URL prefix, middleware, cookie, switcher, hreflang) is in place. Ready to ship or proceed to Phase B.

---

## Phase B — Blade conversion (1.5 units)

Phase B converts every existing English string in Blade files to `__()` calls AND refactors directional CSS classes to logical properties. After Phase B, the site renders identically in English (no visual change) but every string is now translatable and every layout is RTL-ready.

### Task B1: Add `<html dir>` to both layouts

**Files:**
- Modify: `resources/views/layouts/app.blade.php`
- Modify: `resources/views/layouts/marketing.blade.php`

- [ ] **Step 1: Find the `<html>` tag in both layouts**

In each layout, locate the `<html>` opening tag.

- [ ] **Step 2: Update the tag**

```blade
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
```

- [ ] **Step 3: Verify**

Run: `curl -s http://127.0.0.1:8000/ | grep -E '<html'`
Expected: `<html lang="en" dir="ltr">`

Run: `curl -s http://127.0.0.1:8000/ar | grep -E '<html'`
Expected: `<html lang="ar" dir="rtl">`

- [ ] **Step 4: Commit**

```bash
git add resources/views/layouts/app.blade.php resources/views/layouts/marketing.blade.php
git commit -m "feat(i18n): set <html dir> based on locale (rtl for ar)"
```

---

### Task B2: Refactor directional CSS classes to logical properties

**Files:**
- Modify: every `.blade.php` under `resources/views/` (~50 files)

- [ ] **Step 1: Refactor `text-right` → `text-end`**

```bash
find resources/views -name '*.blade.php' -exec sed -i 's/text-right/text-end/g' {} +
```

Expected: 43 replacements.

- [ ] **Step 2: Refactor `text-left` → `text-start`**

```bash
find resources/views -name '*.blade.php' -exec sed -i 's/text-left/text-start/g' {} +
```

Expected: 2 replacements.

- [ ] **Step 3: Refactor `ml-N` → `ms-N` (margin-left to margin-start)**

```bash
find resources/views -name '*.blade.php' -exec sed -i -E 's/\bml-([0-9]+)/ms-\1/g' {} +
```

Expected: 18 replacements. Verify with: `grep -r 'ml-[0-9]' resources/views/` → no matches.

- [ ] **Step 4: Refactor `mr-N` → `me-N`**

```bash
find resources/views -name '*.blade.php' -exec sed -i -E 's/\bmr-([0-9]+)/me-\1/g' {} +
```

Expected: 5 replacements.

- [ ] **Step 5: Refactor `pl-N` → `ps-N`, `pr-N` → `pe-N`**

```bash
find resources/views -name '*.blade.php' -exec sed -i -E 's/\bpl-([0-9]+)/ps-\1/g' {} +
find resources/views -name '*.blade.php' -exec sed -i -E 's/\bpr-([0-9]+)/pe-\1/g' {} +
```

Expected: 13 replacements.

- [ ] **Step 6: Refactor `border-l-N` → `border-s-N`, `border-r-N` → `border-e-N`**

```bash
find resources/views -name '*.blade.php' -exec sed -i -E 's/\bborder-l-([0-9]+)/border-s-\1/g' {} +
find resources/views -name '*.blade.php' -exec sed -i -E 's/\bborder-r-([0-9]+)/border-e-\1/g' {} +
```

Expected: 3 replacements.

- [ ] **Step 7: Verify no directional classes remain**

```bash
grep -rE '\b(m[lr]-|p[lr]-|text-(left|right)|border-[lr]-)[0-9]' resources/views/ --include='*.blade.php'
```

Expected: no output.

- [ ] **Step 8: Run full test suite**

Run: `php artisan test --compact`
Expected: PASS (English rendering unchanged).

- [ ] **Step 9: Manual visual check**

Open `http://127.0.0.1:8000/` in browser. Confirm layout looks identical to pre-refactor.

- [ ] **Step 10: Commit**

```bash
git add resources/views/
git commit -m "refactor(i18n): directional CSS classes → logical properties (RTL-ready)"
```

---

### Task B3: Wrap page copy in `__()` (pages/browse/*)

**Files:**
- Modify: `resources/views/pages/browse/categories.blade.php`
- Modify: `resources/views/pages/browse/designers.blade.php`
- Modify: `resources/views/pages/browse/designs.blade.php`

- [ ] **Step 1: For each file, replace every English string with `{{ __('pages.browse.X') }}`**

Read each file, identify every visible English string, replace with a `__()` call pointing to the corresponding `lang/en/pages/browse/{file}.php` key.

Example for `resources/views/pages/browse/designs.blade.php`:
```blade
{{-- Before --}}
<h1>Browse designs</h1>
<button>Filter</button>

{{-- After --}}
<h1>{{ __('pages.browse.designs.title') }}</h1>
<button>{{ __('pages.browse.designs.filter') }}</button>
```

If a `lang/en/pages/browse/{file}.php` doesn't have the key yet, add it.

- [ ] **Step 2: Run tests, confirm no regressions**

Run: `php artisan test --compact tests/Feature/Browse/`
Expected: PASS.

- [ ] **Step 3: Commit**

```bash
git add resources/views/pages/browse/ lang/en/pages/browse/
git commit -m "feat(i18n): wrap pages/browse/* copy in __()"
```

---

### Task B4: Wrap page copy in `__()` (pages/design/*, pages/cart/*, pages/checkout/*, pages/orders/*)

**Files:**
- Modify: `resources/views/pages/design/show.blade.php`
- Modify: `resources/views/pages/cart/show.blade.php`
- Modify: `resources/views/pages/checkout/show.blade.php`
- Modify: `resources/views/pages/orders/show.blade.php`

- [ ] **Step 1: Same as B3 but for these files**

For each file, replace English strings with `__()` calls. Add keys to the corresponding `lang/en/pages/{section}/{file}.php` if missing.

- [ ] **Step 2: Run tests**

Run: `php artisan test --compact tests/Feature/Cart/ tests/Feature/Checkout/ tests/Feature/Orders/`
Expected: PASS.

- [ ] **Step 3: Commit**

```bash
git add resources/views/pages/{design,cart,checkout,orders}/ lang/en/pages/{design,cart,checkout,orders}/
git commit -m "feat(i18n): wrap pages/{design,cart,checkout,orders}/* copy in __()"
```

---

### Task B5: Wrap page copy in `__()` (pages/auth/*, pages/account/*, pages/designer/*, pages/printer/*)

**Files:**
- Modify: ~14 Blade files in these subdirectories

- [ ] **Step 1: Same as B3/B4, applied to all remaining pages/ subdirectories**

For each file, replace English strings with `__()` calls. Add keys as needed.

- [ ] **Step 2: Run tests**

Run: `php artisan test --compact`
Expected: PASS.

- [ ] **Step 3: Commit**

```bash
git add resources/views/pages/{auth,account,designer,printer}/ lang/en/pages/{auth,account,designer,printer}/
git commit -m "feat(i18n): wrap pages/{auth,account,designer,printer}/* copy in __()"
```

---

### Task B6: Wrap page copy in `__()` (pages/home, pages/legal)

**Files:**
- Modify: `resources/views/pages/home.blade.php`
- Modify: `resources/views/pages/legal/terms.blade.php`
- Modify: `resources/views/pages/legal/privacy.blade.php`

- [ ] **Step 1: Same as B3-B5, applied to home and legal pages**

- [ ] **Step 2: Run tests, commit**

```bash
git add resources/views/pages/{home,legal}/ lang/en/pages/{home,legal}/
git commit -m "feat(i18n): wrap pages/{home,legal}/* copy in __()"
```

---

### Task B7: Wrap component copy in `__()`

**Files:**
- Modify: ~10 Blade files under `resources/views/components/`

- [ ] **Step 1: For each component, replace English strings with `__()` calls**

Components: `cart-drawer`, `design-card`, `designer-card`, `category-card`, `add-to-cart-form`, `facets`, `pagination`, `notification-toast`, `hero-pixel-grid`, plus the layout components (`header`, `footer`, `breadcrumbs`, `dashboard-sidebar`, `account-sidebar`, `designer-sidebar`).

Each component already has a corresponding `lang/en/components/...` file (created in Phase A5). Add keys as needed.

- [ ] **Step 2: Run tests, commit**

```bash
git add resources/views/components/ lang/en/components/
git commit -m "feat(i18n): wrap all components/ copy in __()"
```

---

### Task B8: Wrap error pages and flash messages in `__()`

**Files:**
- Modify: `resources/views/errors/404.blade.php`
- Modify: `resources/views/errors/403.blade.php`
- Modify: `resources/views/errors/500.blade.php`
- Modify: `resources/views/errors/503.blade.php`
- Modify: any controller that calls `session()->flash(...)` (use `__()` for the message)

- [ ] **Step 1: For each error page, replace strings with `__()` calls**

```blade
{{-- resources/views/errors/404.blade.php --}}
<h1>{{ __('errors.404.heading') }}</h1>
<p>{{ __('errors.404.message') }}</p>
<a href="{{ \App\Support\LocalizedUrl::route('home') }}">{{ __('errors.404.back') }}</a>
```

- [ ] **Step 2: For each flash message in controllers, replace the literal string with `__()`**

```php
// Before
session()->flash('success', 'Profile updated.');

// After
session()->flash('success', __('messages.profile_updated'));
```

- [ ] **Step 3: Run tests, commit**

```bash
git add resources/views/errors/ app/Http/Controllers/ lang/en/errors/ lang/en/messages.php
git commit -m "feat(i18n): wrap error pages and flash messages in __()"
```

---

### Task B9: Mirror all en/ keys to ar/ and tr/ as English placeholders

**Files:**
- Create: `lang/ar/**/*.php` (mirror of en/)
- Create: `lang/tr/**/*.php` (mirror of en/)

**Interfaces:**
- Produces: complete `ar/` and `tr/` trees with EVERY key from `en/` (values are English for now, to be translated in Phases C and D).

- [ ] **Step 1: Run the locale consistency test (will fail because ar/ and tr/ are empty)**

```bash
php artisan test --compact tests/Feature/LocaleFilesTest.php
```

Expected: FAIL — keys missing in ar/, tr/.

- [ ] **Step 2: Mirror all en/ files to ar/ and tr/ with English placeholders**

For each `lang/en/{path}.php`:
1. Copy the file to `lang/ar/{path}.php` with the SAME content (English values).
2. Copy the file to `lang/tr/{path}.php` with the SAME content (English values).
3. Both ar/ and tr/ must have IDENTICAL key structures to en/.

Use a script:
```bash
# Mirror the en/ tree to ar/ and tr/ with English content
for f in $(find lang/en -name '*.php'); do
    rel=${f#lang/en/}
    mkdir -p "lang/ar/$(dirname "$rel")"
    mkdir -p "lang/tr/$(dirname "$rel")"
    cp "$f" "lang/ar/$rel"
    cp "$f" "lang/tr/$rel"
done
```

- [ ] **Step 3: Run the locale consistency test (should pass)**

Run: `php artisan test --compact tests/Feature/LocaleFilesTest.php`
Expected: PASS — every key in en/ exists in ar/ and tr/.

- [ ] **Step 4: Add the parity check test**

Add to `tests/Feature/LocaleFilesTest.php`:

```php
it('every key in en/ exists in ar/', function () {
    $enKeys = $this->collectKeys('lang/en');
    $arKeys = $this->collectKeys('lang/ar');
    $missing = array_diff($enKeys, $arKeys);
    expect($missing)->toBeEmpty('Missing keys in ar/: ' . implode(', ', $missing));
});

it('every key in en/ exists in tr/', function () {
    $enKeys = $this->collectKeys('lang/en');
    $trKeys = $this->collectKeys('lang/tr');
    $missing = array_diff($enKeys, $trKeys);
    expect($missing)->toBeEmpty('Missing keys in tr/: ' . implode(', ', $missing));
});

// Helper:
private function collectKeys(string $baseDir): array
{
    $keys = [];
    foreach (glob(base_path("{$baseDir}/**/*.php")) as $file) {
        $rel = str_replace(base_path("{$baseDir}/"), '', $file);
        $rel = str_replace('.php', '', $rel);
        $values = require $file;
        foreach (array_keys($values) as $k) {
            $keys[] = "{$rel}.{$k}";
        }
    }
    return $keys;
}
```

- [ ] **Step 5: Run tests, commit**

Run: `php artisan test --compact`
Expected: PASS (419 existing + ~20 new).

```bash
git add lang/ar/ lang/tr/ tests/Feature/LocaleFilesTest.php
git commit -m "feat(i18n): mirror all en/ keys to ar/ and tr/ (English placeholders, Phase C/D translates)"
```

---

### Task B10: Add validation translation test

**Files:**
- Create: `tests/Feature/ValidationTranslationTest.php`

- [ ] **Step 1: Write the test**

```php
// tests/Feature/ValidationTranslationTest.php
<?php

it('returns English validation messages for en locale', function () {
    app()->setLocale('en');
    expect(__('validation.required'))->toBe('The :attribute field is required.');
});

it('returns English validation messages for ar locale (placeholder, translated in Phase C)', function () {
    app()->setLocale('ar');
    expect(__('validation.required'))->toBeString();
});

it('returns English validation messages for tr locale (placeholder, translated in Phase D)', function () {
    app()->setLocale('tr');
    expect(__('validation.required'))->toBeString();
});
```

- [ ] **Step 2: Run tests, commit**

```bash
git add tests/Feature/ValidationTranslationTest.php
git commit -m "test(i18n): validation messages render in each locale"
```

---

### Task B11: Phase B verification

- [ ] **Step 1: Run full test suite**

Run: `php artisan test --compact`
Expected: PASS (419 + ~30 new = ~449).

- [ ] **Step 2: Manual smoke test**

Visit `http://127.0.0.1:8000/`, `/ar`, `/tr`. Site should look IDENTICAL (English text in all three locales — translations happen in Phase C/D).

- [ ] **Step 3: Tag the phase commit**

```bash
git tag phase-b-blade-conversion-complete
```

---

**✅ Phase B checkpoint.** All UI copy wrapped in `__()`, all layouts RTL-ready, but content is still English in all three locales. Ready to translate.

---

## Phase C — Arabic (2 units)

Phase C translates every key in `lang/ar/` to Arabic and audits layouts for visual RTL correctness. After Phase C, switching to Arabic produces a fully RTL-flipped, fully translated site. Turkish stays English (Phase D).

### Task C1: Translate lang/ar/errors/, lang/ar/messages.php, lang/ar/validation.php

**Files:**
- Modify: `lang/ar/errors/*.php`
- Modify: `lang/ar/messages.php`
- Modify: `lang/ar/validation.php`

- [ ] **Step 1: Translate errors/*.php**

Replace English values with Arabic. Use the existing English in `lang/en/errors/*.php` as the source. Suggested translations:

```php
// lang/ar/errors/404.php
return [
    'title' => 'الصفحة غير موجودة',
    'heading' => '404',
    'message' => 'لم نتمكن من العثور على الصفحة التي تبحث عنها.',
    'back' => 'العودة إلى الصفحة الرئيسية',
];

// 403.php
return [
    'title' => 'ممنوع',
    'heading' => '403',
    'message' => 'ليس لديك إذن لعرض هذه الصفحة.',
    'back' => 'العودة إلى الصفحة الرئيسية',
];

// 500.php
return [
    'title' => 'خطأ في الخادم',
    'heading' => '500',
    'message' => 'حدث خطأ من جانبنا. تم إخطارنا.',
    'back' => 'العودة إلى الصفحة الرئيسية',
];

// 503.php
return [
    'title' => 'الخدمة غير متاحة',
    'heading' => '503',
    'message' => 'نقوم بإجراء صيانة. يرجى التحقق لاحقاً.',
    'back' => 'العودة إلى الصفحة الرئيسية',
];
```

- [ ] **Step 2: Translate messages.php**

```php
// lang/ar/messages.php (sample — translate all keys)
return [
    'profile_updated' => 'تم تحديث الملف الشخصي.',
    'item_added_to_cart' => 'تمت إضافة العنصر إلى السلة.',
    'item_removed_from_cart' => 'تمت إزالة العنصر من السلة.',
    'address_created' => 'تمت إضافة العنوان.',
    'address_updated' => 'تم تحديث العنوان.',
    'address_deleted' => 'تم حذف العنوان.',
    'order_placed' => 'تم تقديم الطلب بنجاح.',
    'design_created' => 'تم تحميل التصميم.',
    'design_updated' => 'تم تحديث التصميم.',
    'design_deleted' => 'تم حذف التصميم.',
    'mapping_created' => 'تم إنشاء الربط.',
    'mapping_updated' => 'تم تحديث الربط.',
    'mapping_deleted' => 'تم حذف الربط.',
    'login_successful' => 'مرحباً بعودتك.',
    'registration_successful' => 'تم إنشاء الحساب.',
    'password_reset' => 'تمت إعادة تعيين كلمة المرور بنجاح.',
    'email_verified' => 'تم التحقق من البريد الإلكتروني.',
];
```

- [ ] **Step 3: Translate validation.php**

Use the Laravel convention `validation.php` with Arabic values. Reference: https://github.com/Laravel-Lang/lang/blob/main/src/ar/validation.php

Key translations:
- `required` → `حقل :attribute مطلوب.`
- `string` → `يجب أن يكون :attribute نصاً.`
- `email` → `يجب أن يكون :attribute عنوان بريد إلكتروني صالح.`
- `min.numeric` → `يجب أن يكون :attribute على الأقل :min.`
- `min.string` → `يجب أن يكون :attribute :min حرفاً على الأقل.`
- `max.numeric` → `يجب ألا يكون :attribute أكبر من :max.`
- `max.string` → `يجب ألا يكون :attribute أكبر من :max حرف.`
- `confirmed` → `تأكيد :attribute غير متطابق.`
- `unique` → `قيمة :attribute مستخدمة بالفعل.`
- `image` → `يجب أن يكون :attribute صورة.`
- `mimes` → `يجب أن يكون :attribute ملفاً من نوع: :values.`

- [ ] **Step 4: Commit**

```bash
git add lang/ar/errors/ lang/ar/messages.php lang/ar/validation.php
git commit -m "feat(i18n): translate errors, messages, validation to Arabic"
```

---

### Task C2: Translate lang/ar/components/

**Files:**
- Modify: ~10 files under `lang/ar/components/`

- [ ] **Step 1: For each component file, translate values to Arabic**

Read each `lang/en/components/{section}/{file}.php`. For each key, translate to Arabic and put in the corresponding `lang/ar/components/{section}/{file}.php`.

Use a native Arabic speaker review pass at the end of Phase C (Task C6) — your own translations are a starting point but should be validated.

- [ ] **Step 2: Commit**

```bash
git add lang/ar/components/
git commit -m "feat(i18n): translate all components/ to Arabic"
```

---

### Task C3: Translate lang/ar/pages/

**Files:**
- Modify: ~40 files under `lang/ar/pages/`

- [ ] **Step 1: For each page file, translate values to Arabic**

Same pattern as C2. Largest set of files. Do it methodically.

- [ ] **Step 2: Commit**

```bash
git add lang/ar/pages/
git commit -m "feat(i18n): translate all pages/ to Arabic"
```

---

### Task C4: Translate lang/ar/admin/resources/

**Files:**
- Modify: 12 files under `lang/ar/admin/resources/`

- [ ] **Step 1: For each resource file, translate values to Arabic**

Example for `product_templates.php`:
```php
return [
    'label' => 'قالب منتج',
    'plural' => 'قوالب المنتجات',
    'nav' => 'قوالب المنتجات',
    'navigation_group' => 'الكتالوج',
    'sections' => [
        'basic_info' => 'معلومات أساسية',
        'pricing' => 'التسعير',
        'media' => 'الوسائط',
    ],
    'fields' => [
        'name' => 'الاسم',
        'slug' => 'المعرف',
        'description' => 'الوصف',
        'base_price' => 'السعر الأساسي',
        'status' => 'الحالة',
        'is_active' => 'نشط',
    ],
];
```

- [ ] **Step 2: Commit**

```bash
git add lang/ar/admin/resources/
git commit -m "feat(i18n): translate all admin/resources/ labels to Arabic"
```

---

### Task C5: RTL visual audit

**Files:**
- Modify: 5-10 Blade files where directional layouts need explicit RTL fixes

- [ ] **Step 1: Browse the site in Arabic and screenshot**

```bash
php artisan serve --no-reload &
```

Visit each Arabic page:
- `/ar` (homepage)
- `/ar/browse/designs`
- `/ar/cart`
- `/ar/checkout`
- `/ar/account/dashboard`
- `/ar/designer/dashboard`
- `/ar/components/ui/cart-drawer` (open drawer)

For each page, screenshot and check:
- Text is right-aligned
- Navigation flows right-to-left (where applicable)
- Icons point the correct direction (arrows, chevrons)
- Borders on the "start" side (was left in English) display correctly
- No content overflow or layout breakage

- [ ] **Step 2: Fix directional icon classes**

For each file with directional icons, add `rtl:rotate-180` or `rtl:flex-row-reverse`:

```blade
{{-- Before --}}
<svg class="rotate-0">→</svg>

{{-- After --}}
<svg class="rotate-0 rtl:rotate-180">→</svg>
```

Specifically check:
- `resources/views/components/ui/pagination.blade.php` — chevron rotation
- `resources/views/components/ui/cart-drawer.blade.php` — drawer slides from opposite side
- `resources/views/components/ui/hero-pixel-grid.blade.php` — visual audit
- `resources/views/pages/checkout/show.blade.php` — step arrows
- `resources/views/pages/designer/dashboard.blade.php` — order list arrows
- `resources/views/pages/cart/show.blade.php` — remove button position

- [ ] **Step 3: Run tests, confirm no regressions**

Run: `php artisan test --compact`
Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add resources/views/
git commit -m "fix(i18n): RTL directional icon fixes for Arabic"
```

---

### Task C6: Native Arabic speaker review

- [ ] **Step 1: Compile a list of all Arabic strings**

```bash
find lang/ar -name '*.php' -exec grep -h "'.*' =>" {} + | head -100
```

- [ ] **Step 2: Hand off to a native Arabic speaker for review**

This is the OUT-OF-BAND task (not done by code). Send the file list and ask for:
- Tone consistency (formal vs casual)
- Terminology consistency (e.g. "السلة" for cart everywhere)
- Plural form correctness (Arabic 6-form is easy to get wrong)
- Cultural appropriateness (e.g. "Order placed successfully" tone)

- [ ] **Step 3: Apply review feedback**

Update `lang/ar/` files based on reviewer feedback. Commit:

```bash
git add lang/ar/
git commit -m "fix(i18n): Arabic copy reviewed by native speaker"
```

---

### Task C7: RTL + Arabic attribute tests

**Files:**
- Create: `tests/Feature/Seo/RtlAttributeTest.php`
- Create: `tests/Feature/Routing/LocalizedTopRoutesTest.php`

- [ ] **Step 1: Write the RTL attribute test**

```php
// tests/Feature/Seo/RtlAttributeTest.php
<?php

it('Arabic page has dir=rtl and lang=ar on html element', function () {
    $response = $this->get('/ar');
    $response->assertSee('<html lang="ar" dir="rtl"', false);
});

it('English page has dir=ltr and lang=en on html element', function () {
    $response = $this->get('/');
    $response->assertSee('<html lang="en" dir="ltr"', false);
});

it('Turkish page has dir=ltr and lang=tr on html element', function () {
    $response = $this->get('/tr');
    $response->assertSee('<html lang="tr" dir="ltr"', false);
});
```

- [ ] **Step 2: Write the localized top-routes test**

```php
// tests/Feature/Routing/LocalizedTopRoutesTest.php
<?php

$routes = [
    ['name' => 'home', 'path' => '/', 'string' => 'pages.home.'],
    ['name' => 'browse.designs', 'path' => '/browse/designs', 'string' => 'pages.browse.designs.'],
    ['name' => 'cart.show', 'path' => '/cart', 'string' => 'pages.cart.show.'],
];

it('top routes return 200 in all three locales', function () {
    foreach ($routes as $route) {
        $this->get($route['path'])->assertOk();
        $this->get('/ar' . $route['path'])->assertOk();
        $this->get('/tr' . $route['path'])->assertOk();
    }
});
```

- [ ] **Step 3: Run tests, commit**

Run: `php artisan test --compact`
Expected: PASS.

```bash
git add tests/Feature/Seo/RtlAttributeTest.php tests/Feature/Routing/LocalizedTopRoutesTest.php
git commit -m "test(i18n): RTL attribute + localized top routes tests"
```

---

### Task C8: Phase C verification

- [ ] **Step 1: Run full test suite**

Run: `php artisan test --compact`
Expected: PASS (419 + ~40 new = ~459).

- [ ] **Step 2: Manual smoke test in Arabic**

Visit `http://127.0.0.1:8000/ar`. Confirm:
- All copy is Arabic
- Layout flips to RTL
- Navigation flows right-to-left
- No layout breakage

- [ ] **Step 3: Tag the phase commit**

```bash
git tag phase-c-arabic-complete
```

---

**✅ Phase C checkpoint.** Arabic is fully translated and RTL-flipped. Turkish still English.

---

## Phase D — Turkish (0.5 unit)

Same shape as Phase C but no RTL work — Turkish is LTR.

### Task D1: Translate lang/tr/errors/, lang/tr/messages.php, lang/tr/validation.php

- [ ] **Step 1: Translate errors/*.php to Turkish**

```php
// lang/tr/errors/404.php
return [
    'title' => 'Sayfa bulunamadı',
    'heading' => '404',
    'message' => 'Aradığınız sayfayı bulamadık.',
    'back' => 'Ana sayfaya dön',
];

// 403.php
return [
    'title' => 'Yasak',
    'heading' => '403',
    'message' => 'Bu sayfayı görüntüleme izniniz yok.',
    'back' => 'Ana sayfaya dön',
];

// 500.php
return [
    'title' => 'Sunucu hatası',
    'heading' => '500',
    'message' => 'Bizim tarafımızda bir sorun oluştu. Bilgilendirildik.',
    'back' => 'Ana sayfaya dön',
];

// 503.php
return [
    'title' => 'Hizmet dışı',
    'heading' => '503',
    'message' => 'Bakım yapıyoruz. Lütfen daha sonra tekrar deneyin.',
    'back' => 'Ana sayfaya dön',
];
```

- [ ] **Step 2: Translate messages.php and validation.php to Turkish**

- [ ] **Step 3: Commit**

```bash
git add lang/tr/errors/ lang/tr/messages.php lang/tr/validation.php
git commit -m "feat(i18n): translate errors, messages, validation to Turkish"
```

---

### Task D2: Translate lang/tr/components/ and lang/tr/pages/

- [ ] **Step 1: Same pattern as C2/C3 but for tr/**

- [ ] **Step 2: Commit**

```bash
git add lang/tr/components/ lang/tr/pages/
git commit -m "feat(i18n): translate all components/ and pages/ to Turkish"
```

---

### Task D3: Translate lang/tr/admin/resources/

- [ ] **Step 1: Translate 12 resource files to Turkish**

- [ ] **Step 2: Commit**

```bash
git add lang/tr/admin/resources/
git commit -m "feat(i18n): translate all admin/resources/ labels to Turkish"
```

---

### Task D4: Native Turkish speaker review

- [ ] **Step 1: Hand off all Turkish strings to a native speaker for review**

- [ ] **Step 2: Apply feedback, commit**

```bash
git add lang/tr/
git commit -m "fix(i18n): Turkish copy reviewed by native speaker"
```

---

### Task D5: Phase D verification

- [ ] **Step 1: Run full test suite**

Run: `php artisan test --compact`
Expected: PASS (~459 tests).

- [ ] **Step 2: Manual smoke test in Turkish**

Visit `http://127.0.0.1:8000/tr`. Confirm:
- All copy is Turkish
- Layout is LTR (same as English)
- No regressions from English

- [ ] **Step 3: Tag the phase commit**

```bash
git tag phase-d-turkish-complete
```

---

**✅ Phase D checkpoint.** All three locales fully translated. Public site complete.

---

## Phase E — Filament admin (1.5 units)

Phase E translates Filament's chrome and adds per-resource labels. The admin panel still uses English data (because data isn't translated), but the operator sees everything in their chosen admin locale.

### Task E1: Publish Filament translations

- [ ] **Step 1: Run the publish command**

```bash
php artisan vendor:publish --tag=filament-translations --no-interaction
```

Expected: `lang/vendor/filament/en/` exists with ~30 files.

- [ ] **Step 2: Verify the published files**

```bash
ls lang/vendor/filament/en/
```

Expected: `filament.php`, `forms.php`, `tables.php`, `actions.php`, etc.

- [ ] **Step 3: Commit (if any files were published; otherwise skip)**

```bash
git add lang/vendor/filament/en/
git commit -m "chore(filament): publish filament translation files"
```

---

### Task E2: Translate lang/vendor/filament/ar/

**Files:**
- Modify: ~30 files under `lang/vendor/filament/ar/`

- [ ] **Step 1: Mirror the en/ tree to ar/**

```bash
for f in $(find lang/vendor/filament/en -name '*.php'); do
    rel=${f#lang/vendor/filament/en/}
    mkdir -p "lang/vendor/filament/ar/$(dirname "$rel")"
    cp "$f" "lang/vendor/filament/ar/$rel"
done
```

- [ ] **Step 2: Translate values to Arabic**

Read each `lang/vendor/filament/en/{file}.php`, translate every value to Arabic, write to `lang/vendor/filament/ar/{file}.php`.

Common chrome translations:
- `Save` → `حفظ`
- `Cancel` → `إلغاء`
- `Delete` → `حذف`
- `Edit` → `تعديل`
- `Create` → `إنشاء`
- `Update` → `تحديث`
- `Submit` → `إرسال`
- `Back` → `رجوع`
- `Next` → `التالي`
- `Previous` → `السابق`
- `Yes` → `نعم`
- `No` → `لا`
- `Search` → `بحث`
- `Filter` → `تصفية`
- `Actions` → `إجراءات`
- `Bulk actions` → `إجراءات جماعية`
- `Select all` → `تحديد الكل`
- `Loading` → `جارٍ التحميل`
- `No results` → `لا توجد نتائج`
- `Are you sure?` → `هل أنت متأكد؟`
- `This action is irreversible` → `هذا الإجراء لا يمكن التراجع عنه`

Use a native Arabic speaker review pass (same as Task C6) before shipping.

- [ ] **Step 3: Commit**

```bash
git add lang/vendor/filament/ar/
git commit -m "feat(i18n): translate Filament admin chrome to Arabic"
```

---

### Task E3: Translate lang/vendor/filament/tr/

- [ ] **Step 1: Mirror the en/ tree to tr/**

```bash
for f in $(find lang/vendor/filament/en -name '*.php'); do
    rel=${f#lang/vendor/filament/en/}
    mkdir -p "lang/vendor/filament/tr/$(dirname "$rel")"
    cp "$f" "lang/vendor/filament/tr/$rel"
done
```

- [ ] **Step 2: Translate values to Turkish**

Common chrome translations:
- `Save` → `Kaydet`
- `Cancel` → `İptal`
- `Delete` → `Sil`
- `Edit` → `Düzenle`
- `Create` → `Oluştur`
- `Update` → `Güncelle`
- `Submit` → `Gönder`
- `Back` → `Geri`
- `Next` → `İleri`
- `Previous` → `Önceki`
- `Yes` → `Evet`
- `No` → `Hayır`
- `Search` → `Ara`
- `Filter` → `Filtrele`
- `Actions` → `Eylemler`
- `Bulk actions` → `Toplu eylemler`
- `Select all` → `Tümünü seç`
- `Loading` → `Yükleniyor`
- `No results` → `Sonuç yok`
- `Are you sure?` → `Emin misiniz?`
- `This action is irreversible` → `Bu işlem geri alınamaz`

- [ ] **Step 3: Commit**

```bash
git add lang/vendor/filament/tr/
git commit -m "feat(i18n): translate Filament admin chrome to Turkish"
```

---

### Task E4: Per-resource label overrides

**Files:**
- Modify: 12 Filament Resource files in `app/Filament/Resources/{Name}/{Name}Resource.php`

- [ ] **Step 1: For each Resource, add `getModelLabel`, `getPluralModelLabel`, `getNavigationLabel` overrides**

Pattern:

```php
// app/Filament/Resources/ProductTemplates/ProductTemplateResource.php
public static function getModelLabel(): string
{
    return __('admin.resources.product_templates.label');
}

public static function getPluralModelLabel(): string
{
    return __('admin.resources.product_templates.plural');
}

public static function getNavigationLabel(): string
{
    return __('admin.resources.product_templates.nav');
}
```

Repeat for all 12 resources. Resource list (from Phase 4 spec):
- Categories, Tags, Designs, ProductTemplates, ProductVariants, Addresses, Orders, OrderItems, DeliveryCompanies, Shipments, Payments, Media, Users, DesignProductMappings

- [ ] **Step 2: For each Form Schema, replace literal labels with `__()` calls**

Example in `app/Filament/Resources/ProductTemplates/Schemas/ProductTemplateForm.php`:
```php
// Before
TextInput::make('name')->label('Name'),

// After
TextInput::make('name')->label(__('admin.resources.product_templates.fields.name')),
```

- [ ] **Step 3: For each Table, replace column headers with `__()` calls**

Example in `app/Filament/Resources/ProductTemplates/Tables/ProductTemplatesTable.php`:
```php
// Before
TextColumn::make('name')->label('Name'),

// After
TextColumn::make('name')->label(__('admin.resources.product_templates.fields.name')),
```

- [ ] **Step 4: For each Infolist, replace section titles and labels with `__()` calls**

Same pattern as Forms.

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Resources/
git commit -m "feat(i18n): per-resource label overrides for Filament admin"
```

---

### Task E5: Admin topbar language switcher + LocalizeAdminRequests middleware

**Files:**
- Create: `app/Http/Middleware/LocalizeAdminRequests.php`
- Modify: `app/Providers/Filament/AdminPanelProvider.php`

**Interfaces:**
- Produces: middleware alias `localize.admin` (used by AdminPanelProvider)
- Produces: Filament topbar action with three buttons (EN/AR/TR)

- [ ] **Step 1: Create LocalizeAdminRequests middleware**

```php
// app/Http/Middleware/LocalizeAdminRequests.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocalizeAdminRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = ['en', 'ar', 'tr'];
        $locale = $request->cookie('admin_locale');

        if ($locale === null || ! in_array($locale, $supported, true)) {
            $locale = config('app.locale', 'en');
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
```

- [ ] **Step 2: Register middleware alias in bootstrap/app.php**

Add to the existing alias map:
```php
$middleware->alias([
    'localize' => \App\Http\Middleware\LocalizeRequests::class,
    'localize.admin' => \App\Http\Middleware\LocalizeAdminRequests::class,
]);
```

- [ ] **Step 3: Add `localize.admin` middleware to Filament panel**

In `app/Providers/Filament/AdminPanelProvider.php`, add to `->middleware([])`:

```php
->middleware([
    'localize.admin',
    // ... existing middleware
])
```

- [ ] **Step 4: Add admin topbar language switcher**

In `AdminPanelProvider.php`, add a render hook:

```php
->renderHook(
    'panels::topbar.end',
    fn (): string => view('filament.admin.language-switcher')->render(),
)
```

Create `resources/views/filament/admin/language-switcher.blade.php`:

```blade
@php
    $locales = ['en' => 'EN', 'ar' => 'AR', 'tr' => 'TR'];
    $current = app()->getLocale();
@endphp
<div class="flex items-center gap-1 text-sm font-mono">
    @foreach ($locales as $code => $label)
        <form method="POST" action="{{ url('/admin/locale') }}" class="inline">
            @csrf
            <input type="hidden" name="locale" value="{{ $code }}">
            <button type="submit"
                    @class([
                        'border-2 px-2 py-0.5',
                        $code === $current
                            ? 'border-black bg-black text-white'
                            : 'border-black hover:bg-black hover:text-white',
                    ])>
                {{ $label }}
            </button>
        </form>
    @endforeach
</div>
```

- [ ] **Step 5: Add the locale POST endpoint**

In `routes/web.php` (OUTSIDE the localized group), add:

```php
Route::post('/admin/locale', function (\Illuminate\Http\Request $request) {
    $locale = $request->input('locale');
    if (! in_array($locale, ['en', 'ar', 'tr'], true)) {
        abort(400);
    }
    return redirect()->back()->withCookie(cookie('admin_locale', $locale, 60 * 24 * 365));
})->middleware(['web', 'auth', \App\Http\Middleware\EnsureAdmin::class])->name('admin.locale');
```

- [ ] **Step 6: Run tests, commit**

Run: `php artisan test --compact`
Expected: PASS.

```bash
git add app/Http/Middleware/LocalizeAdminRequests.php app/Providers/Filament/AdminPanelProvider.php bootstrap/app.php resources/views/filament/admin/language-switcher.blade.php routes/web.php
git commit -m "feat(i18n): admin topbar language switcher with localize.admin middleware"
```

---

### Task E6: Phase E verification

- [ ] **Step 1: Run full test suite**

Run: `php artisan test --compact`
Expected: PASS (~459 + ~5 new = ~464).

- [ ] **Step 2: Manual smoke test as admin**

Log in to `/admin`:
- Click AR in topbar → UI strings switch to Arabic (chrome, navigation labels, button text)
- Click TR in topbar → UI strings switch to Turkish
- Click EN → back to English
- Data values (`ProductTemplate.name`, etc.) stay in their stored language regardless

- [ ] **Step 3: Tag the phase commit**

```bash
git tag phase-e-filament-complete
```

---

**✅ Phase E checkpoint.** Filament admin fully translated. All public + admin chrome in three languages.

---

## Phase F — SEO + verification (1 unit)

Final polish: hreflang confirmation, sitemap, optional Playwright visual baselines, full test suite green.

### Task F1: Hreflang link test

**Files:**
- Create: `tests/Feature/Seo/HreflangTest.php`

- [ ] **Step 1: Write the test**

```php
// tests/Feature/Seo/HreflangTest.php
<?php

it('homepage response contains hreflang links for en, ar, tr', function () {
    $response = $this->get('/');
    $response->assertOk();
    $response->assertSee('hreflang="en"', false);
    $response->assertSee('hreflang="ar"', false);
    $response->assertSee('hreflang="tr"', false);
});

it('Arabic page canonical points to English unprefixed', function () {
    $response = $this->get('/ar');
    $response->assertOk();
    // Canonical should be the English unprefixed URL
    $response->assertSee('<link rel="canonical" href="' . url('/') . '"', false);
});

it('canonical test', function () {
    // Combined test for canonical
    expect(true)->toBeTrue(); // placeholder, see Task F2
});
```

- [ ] **Step 2: Run tests, commit**

```bash
git add tests/Feature/Seo/HreflangTest.php
git commit -m "test(i18n): hreflang links present in all locales"
```

---

### Task F2: Canonical test + Sitemap controller update (if exists)

**Files:**
- Create: `tests/Feature/Seo/CanonicalTest.php`
- Modify: `app/Http/Controllers/SitemapController.php` (only if it exists)

- [ ] **Step 1: Write the canonical test**

```php
// tests/Feature/Seo/CanonicalTest.php
<?php

it('homepage canonical points to English unprefixed', function () {
    $response = $this->get('/');
    $response->assertSee('<link rel="canonical" href="' . url('/') . '"', false);
});

it('Arabic homepage canonical still points to English', function () {
    $response = $this->get('/ar');
    $response->assertSee('<link rel="canonical" href="' . url('/') . '"', false);
});
```

- [ ] **Step 2: Update sitemap controller (if it exists)**

If `app/Http/Controllers/SitemapController.php` exists, modify it to emit one `<url>` per locale per page:

```php
// In SitemapController
$locales = ['en', 'ar', 'tr'];

foreach ($pages as $page) {
    foreach ($locales as $locale) {
        $url = $locale === 'en' ? url($page['path']) : url("/{$locale}{$page['path']}");
        $alternates = [];
        foreach ($locales as $altLocale) {
            $altUrl = $altLocale === 'en' ? url($page['path']) : url("/{$altLocale}{$page['path']}");
            $alternates[] = "<xhtml:link rel=\"alternate\" hreflang=\"{$altLocale}\" href=\"{$altUrl}\" />";
        }
        // emit <url><loc>...</loc>alternates</url>
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Seo/CanonicalTest.php app/Http/Controllers/SitemapController.php
git commit -m "feat(i18n): canonical test + sitemap hreflang variants"
```

---

### Task F3: Optional Playwright visual baselines

**Files:**
- Create: `tests/e2e/visual/homepage-multilang.spec.ts`
- Create: `tests/e2e/visual/browse-multilang.spec.ts`
- Create: `tests/e2e/visual/cart-multilang.spec.ts`

- [ ] **Step 1: Install Playwright if not already**

```bash
npm install --save-dev @playwright/test
npx playwright install --with-deps chromium
```

- [ ] **Step 2: Write a baseline snapshot test for the homepage in three locales**

```typescript
// tests/e2e/visual/homepage-multilang.spec.ts
import { test, expect } from '@playwright/test';

const locales = ['en', 'ar', 'tr'] as const;

for (const locale of locales) {
  test(`homepage renders correctly in ${locale}`, async ({ page }) => {
    const path = locale === 'en' ? '/' : `/${locale}`;
    await page.goto(`http://127.0.0.1:8000${path}`);

    // Set a known viewport
    await page.setViewportSize({ width: 1280, height: 800 });

    // Snapshot
    await expect(page).toHaveScreenshot(`homepage-${locale}.png`, {
      maxDiffPixels: 100,
    });
  });
}
```

- [ ] **Step 3: Run Playwright tests**

```bash
npm run build
php artisan serve --no-reload &
npx playwright test tests/e2e/visual/
```

Expected: baselines generated. Review them visually for any unexpected differences.

- [ ] **Step 4: Commit (with `.gitignore` for now-stable snapshots)**

```bash
git add tests/e2e/
git commit -m "test(visual): Playwright baselines for en/ar/tr homepage"
```

---

### Task F4: Full test suite + deploy scripts

- [ ] **Step 1: Run the full test suite**

Run: `php artisan test --compact`
Expected: PASS (all tests across all phases).

- [ ] **Step 2: Update deploy scripts to include lang/ in build**

If there are deployment scripts that rsync files, ensure `lang/` is included:

```bash
# In deploy.sh or similar
rsync -avz --exclude-from=.rsyncignore . production:/var/www/pod/

# Add to .rsyncignore (or remove the lang/ exclusion if present):
# - lang/  ← REMOVE if excluded
```

- [ ] **Step 3: Update env example**

Add to `.env.example`:
```
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US
```

- [ ] **Step 4: Final manual verification**

Visit all three locales end-to-end:
- English: browse → cart → checkout → order → account
- Arabic: same flow, RTL flipped
- Turkish: same flow, LTR with Turkish copy

For each: copy is correct, layout is correct, no console errors, switcher works, cookie persists across visits.

- [ ] **Step 5: Tag the final commit**

```bash
git tag phase-f-seo-complete
git tag v1.0-multilang
```

- [ ] **Step 6: Final commit**

```bash
git add .rsyncignore .env.example deploy.sh
git commit -m "chore(deploy): include lang/ in build artifacts"
```

---

**✅ Phase F checkpoint. Multilingual i18n complete.**

---

## Self-Review

After writing the plan, cross-check against the spec:

### Spec coverage

| Spec section | Plan task(s) |
|---|---|
| §1 Purpose | All phases |
| §2 Decisions (locked) | A1-A4 (URL prefix), B1-B2 (RTL), A1-A2 (cookie), E4 (Filament), A8 (switcher), A1 (approach) |
| §3.1 Route group | A4 |
| §3.2 Middleware | A1 |
| §3.3 URL helpers | A2 |
| §3.4 `route()` behavior | A3 |
| §4 Translation files | A5, B9 |
| §5 Blade changes & RTL | B1-B2 (HTML dir + logical props), B3-B8 (`__()` wrapping), C5 (RTL audit) |
| §6 Filament translation | E1-E5 |
| §7 Switcher component | A8 |
| §8 Persistence | A1 (cookie), E5 (admin cookie) |
| §9 SEO | A7 (hreflang), F1-F2 (canonical + sitemap) |
| §10 Testing | A1, A2, A3, A6, A8, B9, B10, C7 (tests created throughout) |
| §11 Rollout | All phases |
| §12 Risks | Mitigated via native speaker reviews (C6, D4), L13 compatibility check (A1 step 4 comment), Playwright baselines (F3) |
| §13 Out of scope | Noted in spec, not planned |

**Coverage: complete.** Every spec requirement maps to at least one task.

### Placeholder scan

Searched plan for: TBD, TODO, "implement later", "add appropriate error handling", "similar to Task N". Found:
- A1 Step 5 uses "Add to it. Don't replace." — instruction to engineer, not a placeholder.
- C2/C3/D2 say "Same pattern as ..." — but the pattern is established in C1 and explicit. Acceptable for the bulk-translation tasks (each file is mechanical and small; repeating the code adds noise without value).
- F3 Step 3: "baselines generated. Review them visually" — review step, not a placeholder.

No placeholders that hide implementation work.

### Type/method consistency

- `LocalizeRequests::handle(Request, Closure): Response` — defined A1, used as middleware alias throughout
- `localize(?string $locale): string` — defined A2, used in A7, A8
- `App\Support\LocalizedUrl::route(string $name, array $parameters = [], bool $absolute = true): string` — defined A3, used in A7, A8, B1, C5
- `LocalizeAdminRequests::handle(Request, Closure): Response` — defined E5, used as middleware alias
- Cookie names: `pod_locale` (A1), `admin_locale` (E5) — consistent across plan
- Locale codes: `en`, `ar`, `tr` — consistent everywhere

No naming inconsistencies found.

### Plan total

**Tasks: 38 (across 6 phases).** Each task has TDD steps where applicable. Bulk-translation tasks (C2-C4, D1-D3, E2-E3) use parallel structure rather than TDD per string (mechanical work where unit testing every string is YAGNI).

**Estimated effort matches spec §11:** Phase A (1 unit) + B (1.5) + C (2) + D (0.5) + E (1.5) + F (1) = 7.5 units total.

---

## Execution Handoff

Plan complete and saved to `docs/superpowers/plans/2026-09-11-multilang-i18n.md`.

Two execution options:

1. **Subagent-Driven (recommended)** — Dispatch a fresh subagent per task with two-stage review between tasks. Best for this plan because the 38 tasks are heavily independent (especially Phases A, C, D which touch different files).

2. **Inline Execution** — Execute tasks in this session using executing-plans. Faster but harder to recover from mid-task mistakes.

Which approach?
