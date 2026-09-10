# Multilingual i18n Design — POD

**Status:** Draft for review
**Date:** 2026-09-11
**Branch:** `local-smart-shot`
**Author:** Brainstorming session output

---

## 1. Purpose

Add multilingual support to the POD platform so the public-facing website and the Filament admin panel render in **English (en)**, **Arabic (ar)**, and **Turkish (tr)**.

**Scope (locked):**

- Translate **UI copy and layout text** in every Blade view and every Filament resource.
- **Do not** translate seeded data — `ProductTemplate.name`, `Design.title`, user-supplied content, and similar DB-stored values remain in their stored language (English, since that's what we seed).
- Apply **full RTL flipping** for Arabic (logical CSS properties, `<html dir="rtl">`, mirrored icons where needed).
- Filament admin: translate **UI chrome only** (nav labels, button text, validation messages, table column headers, infolist section titles) — not the actual data displayed.

## 2. Decisions (locked from brainstorming)

| Decision | Choice |
|---|---|
| URL strategy | Prefix: `/ar/...`, `/tr/...`. English is unprefixed. |
| RTL handling | Full flip for Arabic (logical properties, `dir="rtl"`, mirrored icons). |
| Default locale | English. `/cart` is English; `/ar/cart` is Arabic; `/tr/cart` is Turkish. |
| Persistence | `pod_locale` cookie, 1-year expiry. URL prefix always wins over cookie. |
| Filament scope | Chrome only — menus, buttons, validation, column headers. Data stays in stored language. |
| Switcher placement | Header dropdown for public site. Filament topbar action for admin. |
| Approach | `mcamara/laravel-localization` if it supports Laravel 13; else native middleware (Approach B from brainstorming). |

## 3. Route structure & locale resolution

### 3.1 Route group layout

Wrap every public route in `routes/web.php` inside a localized group. Filament routes stay outside this group.

```php
// routes/web.php
Route::prefix('{locale?}')
    ->where(['locale' => '(en|ar|tr)'])
    ->middleware(['localize'])
    ->group(function () {
        // existing routes, untouched
    });
```

`{locale?}` makes the segment optional. English renders without a prefix; `ar` and `tr` require it. The regex constraint blocks invalid locales.

Filament routes are registered separately by Filament's panel provider (unchanged).

### 3.2 Middleware (`App\Http\Middleware\LocalizeRequests`)

Single middleware that resolves and applies the locale:

1. Read `{locale}` from route parameters (if present).
2. Fall back to `cookie('pod_locale')`.
3. Fall back to `Accept-Language` header (parsed for `ar`, `en`, `tr`).
4. Fall back to `config('app.locale')` (English).

Then:
- `app()->setLocale($resolved)`
- Set `app()->getLocale()` direction (read via `app()->isLocale('ar')` in views)
- Persist the resolved locale into `cookie('pod_locale', $resolved, 60 * 24 * 365)` when it differs from the current cookie value AND a URL prefix was the source (don't write cookie on Accept-Language-only fallback).

Register the middleware alias `'localize' => LocalizeRequests::class` in `bootstrap/app.php` (Laravel 13 style — middleware registration via `withMiddleware()`).

### 3.3 URL helpers

Add a `localize(?string $locale): string` helper:

- `localize(null)` → canonical URL of the current route, English unprefixed (e.g. on `/ar/cart`, returns `/cart`).
- `localize('en')` → current route in English (strips any prefix).
- `localize('ar')` → current route in Arabic (swaps or adds `/ar` prefix).
- `localize('tr')` → current route in Turkish.

The helper resolves the current route name + parameters via `request()->route()`, swaps the `locale` parameter, and rebuilds the URL with `route()`. If the requested locale is `en` and the URL is already unprefixed, returns it as-is.

### 3.4 `route()` helper behavior

Plain `route('cart.show')` always produces a URL using the **current request locale** (e.g. on an Arabic page, returns `/ar/cart`).

**Implementation mechanism (locked):** A small `App\Providers\RouteServiceProvider` (or extension of Laravel 13's existing `RouteServiceProvider`) overrides `URL::route()` behavior by:
1. Reading `app()->getLocale()` on each call.
2. If locale is `en`, generating the URL normally.
3. If locale is `ar` or `tr`, calling `URL::to()` with the unprefixed URL and prepending `/{locale}`.

This makes `route()` calls locale-aware without requiring every call site to pass `locale` explicitly. The `localize($locale)` helper remains for cross-locale links (language switcher).

Result:
- English page → `route('cart.show')` → `/cart`
- Arabic page → `route('cart.show')` → `/ar/cart`
- Switcher on any page → `localize('tr')` → current route in Turkish (strips `/ar/` if present, adds `/tr/`)

## 4. Translation files (`lang/`)

### 4.1 Directory layout

PHP files keyed by category. One file per Blade page/component. Mirror tree across `en/`, `ar/`, `tr/`.

```
lang/
  en/
    pages/
      home.php
      browse/
        designs.php
        designers.php
        categories.php
      design/
        show.php
      cart/
        show.php
      checkout/
        show.php
      orders/
        show.php
      auth/
        login.php
        register.php
        forgot-password.php
        reset-password.php
      account/
        dashboard.php
        addresses.php
        orders.php
        notifications.php
      designer/
        dashboard.php
        designs.php
        mappings.php
        orders.php
        edit.php
      printer/
        dashboard.php
        edit.php
        fulfilment.php
        show.php
      legal/
        terms.php
        privacy.php
    components/
      layout/
        header.php
        footer.php
        breadcrumbs.php
        dashboard-sidebar.php
        account-sidebar.php
        designer-sidebar.php
      ui/
        cart-drawer.php
        design-card.php
        designer-card.php
        category-card.php
        add-to-cart-form.php
        facets.php
        pagination.php
        notification-toast.php
        hero-pixel-grid.php
    errors/
      404.php
      403.php
      500.php
      503.php
    messages.php
    validation.php
  ar/   # mirror tree
  tr/   # mirror tree
```

### 4.2 File shape (PHP)

```php
// lang/en/pages/cart/show.php
return [
    'title' => 'Your cart',
    'empty' => 'Your cart is empty.',
    'subtotal' => 'Subtotal',
    'checkout' => 'Proceed to checkout',
    'remove' => 'Remove',
    'qty' => 'Quantity',
];

// lang/ar/pages/cart/show.php
return [
    'title' => 'سلتك',
    'empty' => 'سلتك فارغة.',
    'subtotal' => 'المجموع الفرعي',
    'checkout' => 'متابعة الدفع',
    'remove' => 'إزالة',
    'qty' => 'الكمية',
];
```

Strings with variables use `:name` placeholders, replaced via `__('messages.greeting', ['name' => $user->name])`.

### 4.3 Pluralization

Use `trans_choice` for count-dependent strings. Arabic has 6 plural categories; Turkish has 2.

```php
// lang/en/pages/cart/show.php
'items_count' => '{0} No items|{1} One item|[2,*]:count items',

// lang/ar/pages/cart/show.php
'items_count' => '{0} لا توجد عناصر|{1} عنصر واحد|{2} عنصران|{3,:count} عناصر|{11,:count} عنصراً|* :count عنصر',

// lang/tr/pages/cart/show.php
'items_count' => '{1} :count ürün|[2,*]:count ürün',
```

### 4.4 Naming convention

- Dots map to slashes: `pages.cart.show.title` → `lang/{locale}/pages/cart/show.php` → key `title`.
- Always fully qualified from `pages.` or `components.`. No short keys.
- Validation messages in `validation.php` (Laravel convention, picked up automatically).

### 4.5 What is translated vs left alone

| Translated | Left alone |
|---|---|
| All Blade copy | DB-stored data (Product.name, Design.title, user content) |
| Flash messages | Email templates (separate task) |
| Validation messages | Slugs, route names, CSS classes, HTML attributes |
| Error pages | JS strings in `resources/js/` (separate task if needed) |
| Filament chrome | Filament-stored data |

## 5. Blade changes & RTL strategy

### 5.1 Step 1 — wrap copy in `__()`

Every English string in `resources/views/**/*.blade.php` becomes `{{ __('pages.foo.bar') }}`. Headers, buttons, labels, alt text, placeholders, error messages. No file exempt except pure layout shells with no user-visible copy.

### 5.2 Step 2 — refactor directional classes to logical properties

Audit results (84 occurrences total across 50 files — small footprint):

| Current | Count | Replace with |
|---|---|---|
| `text-right` | 43 | `text-end` |
| `ml-*` | 18 | `ms-*` |
| `pl-*` | 11 | `ps-*` |
| `mr-*` | 5 | `me-*` |
| `text-left` | 2 | `text-start` |
| `pr-*` | 2 | `pe-*` |
| `border-l-*` | 2 | `border-s-*` |
| `border-r-*` | 1 | `border-e-*` |

Brutalist borders that aren't side-specific (`border-2`, `border-b-3`, etc.) stay as-is.

### 5.3 Layout shell `<html>` tag

Both `resources/views/layouts/app.blade.php` and `resources/views/layouts/marketing.blade.php` get:

```blade
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
```

### 5.4 Directional icon handling

10 files contain `flex-row` or arrow glyphs (`→`, `←`). Tailwind v4 supports the `rtl:` variant directly:

- **Pagination chevrons**: `class="rtl:rotate-180"` on the arrow span.
- **Cart drawer / checkout step arrows**: same `rtl:rotate-180` treatment.
- **`hero-pixel-grid`**: visual audit during Phase C.
- **Account/dashboard sidebar**: handled by `text-start` after refactor — no special work.

### 5.5 Brutalist design RTL impact

- Borders: only the 3 `border-l-*`/`border-r-*` cases need swapping. Others direction-agnostic.
- Buttons: text alignment via `text-start`. Icon-left-of-text patterns get `rtl:flex-row-reverse`.
- Inputs: `pl-*`/`pr-*` → `ps-*`/`pe-*`.
- Hero pixel grid: visual audit in Phase C.

## 6. Filament admin translation

### 6.1 Chrome translation

1. Publish Filament v4 translations: `php artisan vendor:publish --tag=filament-translations` → `lang/vendor/filament/en/`.
2. Mirror to `lang/vendor/filament/ar/` and `lang/vendor/filament/tr/`.
3. Translate every chrome string (Save / حفظ / Kaydet, Cancel / إلغاء / İptal, Delete / حذف / Sil, etc.).

### 6.2 Per-resource labels

Each `*Resource.php` overrides `getModelLabel()`, `getPluralModelLabel()`, `getNavigationLabel()` to return translated strings from `lang/{locale}/admin/resources/{model}.php`.

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

Same for column headers, section headers, relation managers — anything calling `->label(...)` or `->title(...)`.

### 6.3 Admin topbar language switcher

Custom Filament `Action` in `AdminPanelProvider.php`'s render hook. Three buttons set `admin_locale` cookie and reload. No URL prefix — admin stays at `/admin/...`.

### 6.4 Data remains English

`ProductTemplate.name`, `Design.title`, etc. render as-is in admin tables/forms regardless of UI locale. (Per locked decision — no Spatie Translatable.)

## 7. Language switcher component

### 7.1 Public site

New file `resources/views/components/ui/language-switcher.blade.php`:

```blade
@props(['compact' => false])
@php
    $locales = ['en' => 'EN', 'ar' => 'AR', 'tr' => 'TR'];
    $current = app()->getLocale();
@endphp
<div class="flex items-center gap-1 {{ $compact ? 'text-xs' : 'text-sm' }} font-mono">
    @foreach ($locales as $code => $label)
        <a href="{{ localize($code) }}"
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

Inserted into `resources/views/components/layout/header.blade.php`.

### 7.2 Filament topbar

Custom Filament `Action` in `AdminPanelProvider.php`'s render hook — three buttons that set a **`admin_locale` cookie** (separate from public `pod_locale`) and reload the page.

**Why two cookies:** Public site (`pod_locale`) and Filament admin (`admin_locale`) are independent surfaces. An operator may want the public site in English (their native) and the admin panel in Arabic (their working language), or vice versa. Keeping them separate avoids coupling.

The admin switcher applies locale via a middleware on Filament routes (`AdminPanelProvider::middleware()`), which only reads the `admin_locale` cookie. The public `localize()` middleware does NOT run on admin routes (Filament routes are outside the localized route group per §3.1).

## 8. Persistence (cookie + Accept-Language)

### 8.1 Cookie

- Name: `pod_locale`
- Value: `en | ar | tr`
- Expiry: 1 year (`60 * 24 * 365` minutes)
- Attributes: `SameSite=Lax`, `Secure` in production
- Domain: root (applies to all subdomains)

### 8.2 Resolution priority

1. URL prefix (`/ar/`, `/tr/`) — always wins. Shareable links work.
2. `pod_locale` cookie — user's saved preference.
3. `Accept-Language` header — browser preference (parse for `ar`, `en`, `tr`).
4. `config('app.locale')` — English.

### 8.3 Cookie writes

Cookie is written by the `LocalizeRequests` middleware when:
- URL prefix was the source of resolution (explicit user choice via URL or switcher), AND
- Resolved locale differs from current cookie value.

Not written on Accept-Language-only fallback (transient, would pollute cookies).

## 9. SEO

### 9.1 Hreflang & canonical

Inside `<head>` of both layouts:

```blade
@foreach (['en', 'ar', 'tr'] as $hreflangLocale)
    <link rel="alternate" hreflang="{{ $hreflangLocale }}"
          href="{{ localize($hreflangLocale, $hreflangLocale === 'en' ? null : true) }}" />
@endforeach
<link rel="canonical" href="{{ localize(null) }}" />
```

`localize(null)` returns canonical English URL. `localize($locale)` for hreflang variants.

### 9.2 Sitemap

If `sitemap.xml` controller exists, update to emit one `<url>` per locale per page with `<xhtml:link rel="alternate" hreflang="...">` children.

## 10. Testing strategy

Existing **419 tests must continue to pass**. Add:

### 10.1 Unit tests (~6)

- `LocalizeRequestsTest`: URL prefix > cookie > Accept-Language > default priority
- `CookieWriteTest`: cookie written when resolved locale differs from cookie AND URL prefix was source
- `LocalizeHelperTest`: `localize('ar')` produces correct URL from each starting locale
- `LocaleFilesTest`: every lang file is valid PHP; every key in `en/` exists in `ar/` and `tr/`
- `ValidationTranslationTest`: `__('validation.required')` etc. returns the localized string for each locale (no Laravel fallback to English for active locale)

### 10.2 Feature tests (~8)

- One per top-level route: `/`, `/ar`, `/tr` return 200 and page contains expected translated string
- Switcher component renders three links; current locale marked; `localize()` URLs correct
- Hreflang test: response contains `<link rel="alternate" hreflang="...">` for all three locales
- RTL test: Arabic response has `<html dir="rtl">` and `<html lang="ar">`; others don't

### 10.3 Visual regression (Playwright, optional)

- One snapshot per locale of: homepage, browse/designs, cart, checkout, account dashboard
- Baselines stored; catches brutalist layout breakage unit tests miss

### 10.4 Not tested

- Every translated string. Test the key-resolution mechanism; human review the translations.

## 11. Rollout

Six phases, each independently shippable. All work on `local-smart-shot` branch.

| Phase | Deliverable | Touches | Effort |
|---|---|---|---|
| **A. Infrastructure** | Locale config, middleware, route groups, lang/ skeleton, `en/` files (verbatim copy of existing strings), `localize()` helper, cookie handling, hreflang skeleton | `config/app.php`, `routes/web.php`, new middleware, new helper, new `lang/en/`, `bootstrap/app.php` | 1 unit |
| **B. Blade conversion** | Every view uses `__()` + logical properties. English UI works identically. RTL markup in place but only English translated. | All 50 Blade files | 1.5 units |
| **C. Arabic** | Translate every key in `lang/ar/`. Visual RTL audit. Fix layout breakage. | `lang/ar/`, layout fixes in 5-10 Blade files | 2 units |
| **D. Turkish** | Translate every key in `lang/tr/`. LTR — same as English except copy. | `lang/tr/` only | 0.5 unit |
| **E. Filament** | Publish Filament translations, mirror to `ar/` + `tr/`, translate per-resource labels (12 resources), admin topbar switcher. | `lang/vendor/filament/`, all 12 Resource files, `AdminPanelProvider` | 1.5 units |
| **F. SEO + verification** | Hreflang, canonical, sitemap, Playwright visual baselines for en/ar/tr, full test suite green, deploy scripts updated. | layouts/, optional sitemap controller, Playwright config | 1 unit |

**Total: ~7.5 units / ~6.5 working days.**

## 12. Risks

1. **Arabic plural forms** — 6 plural categories easy to get wrong. Native speaker review required before Phase C ships.
2. **Filament plugin compatibility** — `mcamara/laravel-localization` may not yet support Laravel 13. Verified during Phase A; fall back to native middleware if needed.
3. **JS strings** — Alpine components in `resources/js/` likely have hardcoded English. Audit during Phase A; decide translate-now or defer.
4. **Email templates** — if any exist in `resources/views/emails/`, out of scope per §4.5. Audit during Phase A.
5. **Brutalist RTL aesthetics** — thick borders and asymmetric layouts may look wrong mirrored. Playwright baselines in Phase F catch this; expect 1-2 iterations.

## 13. Out of scope (explicit)

- Translating seeded DB content (Product names, Design titles, etc.)
- Adding `spatie/laravel-translatable` for content translation
- Translating email templates (separate task if needed)
- Translating JS strings in `resources/js/` (separate task if needed)
- Adding multi-currency support (separate task)
- RTL flipping of admin data values (chrome-only per §6.4)

## 14. References

- [POD platform buildout design](2026-08-23-pod-platform-buildout-design.md) — locked tech decisions (Laravel 13, Filament v4, Action classes, Sanctum, Tailwind v4 + Alpine)
- [Theme Sand Coral memory](../../../../C:/Users/Crist/.claude/projects/c--Users-Crist-Desktop-ADISC-POD/memory/theme-sand-coral.md) — Brutalist design tokens used by the switcher component
- Laravel 13 docs — middleware registration via `withMiddleware()`, optional route parameters
- Filament v4 docs — translation publishing, per-resource label overrides
