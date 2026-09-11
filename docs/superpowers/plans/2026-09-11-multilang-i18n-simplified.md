# Multilingual i18n — Simplified Implementation Plan (supersedes Tasks 10-43)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

> **⚠ PHPUnit, not Pest:** Project uses PHPUnit 12 (`phpunit/phpunit ^12.5.12`). All test code MUST be in PHPUnit syntax.

**Goal:** Ship multilingual (en/ar/tr) public site fast and reasonable. Consolidate lang files, sweep Blade to `__()`, add RTL CSS, translate. 5 tasks total — no separate Filament/SEO/Playwright work in this plan.

**Architecture:** Single flat `lang/{en,ar,tr}.php` per locale (~150 lines each). Existing middleware/helper/switcher from Phase A reused. Blade sweep is mechanical (find/replace). RTL via Tailwind logical properties + a small `[dir="rtl"]` override stylesheet for Arabic-specific fixes.

**Supersedes:** Tasks 10-43 of [the original plan](2026-09-11-multilang-i18n.md). Phase A (Tasks 1-9) is already complete and stays.

**Spec:** [docs/superpowers/specs/2026-09-11-multilang-i18n-design.md](../specs/2026-09-11-multilang-i18n-design.md)

---

## Global Constraints

- **Locales:** `en` (default, unprefixed), `ar` (full RTL), `tr` (LTR).
- **Translation storage:** ONE flat file per locale: `lang/en.php`, `lang/ar.php`, `lang/tr.php`. NOT 69 files mirroring Blade.
- **Cookie:** `pod_locale` (already implemented in Phase A Task 1).
- **RTL:** Arabic pages `<html dir="rtl">` (already set in Phase A Task 10 — wait, Task 10 wasn't done yet; will be done here). Tailwind v4 logical properties (`ms-*`/`me-*`/`ps-*`/`pe-*`/`text-start`/`text-end`).
- **Branch:** All work on `local-smart-shot`.
- **Existing tests:** 583 must continue to pass.
- **Filament admin:** stays English-only in this plan (skip Phase E).
- **SEO:** keep Task 7's hreflang + canonical; skip Phase F Playwright baselines.

---

## File Map

| Layer | Path | Action |
|---|---|---|
| Lang | `lang/en.php` | REPLACE 69 lang/en/ files with single flat array |
| Lang | `lang/ar.php` | CREATE — placeholder English values (translate in S4) |
| Lang | `lang/tr.php` | CREATE — placeholder English values (translate in S5) |
| Lang | `lang/en/**/*.php` (69 files) | DELETE after consolidation |
| Lang | `lang/ar/**/*.php`, `lang/tr/**/*.php` | DELETE (use flat ar.php / tr.php only) |
| CSS | `resources/css/rtl.css` | NEW — small RTL override stylesheet |
| Blade | `resources/views/**/*.blade.php` (~50 files) | MODIFY — replace hardcoded strings with `{{ __('key') }}` |
| Blade | `resources/views/layouts/app.blade.php` | MODIFY — `<html dir="rtl">` for ar |
| Blade | `resources/views/layouts/marketing.blade.php` | MODIFY — `<html dir="rtl">` for ar |
| Tests | `tests/Feature/LocaleFilesTest.php` | UPDATE — assert single file per locale |

Total: **~55 files modified**, **2 files created**, **138 lang files deleted**.

---

## Task 1: Consolidate lang files into flat per-locale files

**Files:**
- Create: `lang/en.php`, `lang/ar.php`, `lang/tr.php`
- Delete: `lang/en/**/*.php` (69 files), `lang/ar/**/*.php`, `lang/tr/**/*.php`
- Modify: `tests/Feature/LocaleFilesTest.php` to assert single file per locale

- [ ] **Step 1: Build the consolidated `lang/en.php`**

Read every file under `lang/en/` and merge all keys into ONE flat `lang/en.php`. Use simple dot-notation keys (`cart_title`, `cart_empty`, `home_heading`, `auth_login`, etc.) — no nested directory mirroring. Use the existing English strings as values.

Expected: ~150 lines, ~120 keys.

- [ ] **Step 2: Create placeholder `lang/ar.php` and `lang/tr.php`**

Copy `lang/en.php` structure. For each key, value is the English string (placeholder — translated in S4/S5). This ensures no missing keys between locales.

- [ ] **Step 3: Delete the directory-based lang files**

```bash
rm -rf lang/en lang/ar lang/tr
mkdir lang  # ensure dir exists
```

- [ ] **Step 4: Update `tests/Feature/LocaleFilesTest.php`**

Replace the recursive iterator with a single-file check per locale:

```php
public function test_en_file_is_valid_php(): void
{
    $values = require base_path('lang/en.php');
    $this->assertIsArray($values);
    $this->assertNotEmpty($values);
}

public function test_ar_file_is_valid_php(): void
{
    $values = require base_path('lang/ar.php');
    $this->assertIsArray($values);
    $this->assertNotEmpty($values);
}

public function test_tr_file_is_valid_php(): void
{
    $values = require base_path('lang/tr.php');
    $this->assertIsArray($values);
    $this->assertNotEmpty($values);
}

public function test_every_en_key_exists_in_ar_and_tr(): void
{
    $en = require base_path('lang/en.php');
    $ar = require base_path('lang/ar.php');
    $tr = require base_path('lang/tr.php');

    $missingAr = array_diff(array_keys($en), array_keys($ar));
    $missingTr = array_diff(array_keys($en), array_keys($tr));

    $this->assertEmpty($missingAr, 'Missing keys in ar: ' . implode(', ', $missingAr));
    $this->assertEmpty($missingTr, 'Missing keys in tr: ' . implode(', ', $missingTr));
}
```

- [ ] **Step 5: Run tests**

```bash
php artisan test --compact tests/Feature/LocaleFilesTest.php
```

Expected: 4/4 pass.

- [ ] **Step 6: Commit**

```bash
git add lang/ tests/Feature/LocaleFilesTest.php
git commit -m "refactor(i18n): consolidate 69 lang/en/ files into single flat lang/en.php per locale"
```

---

## Task 2: Mechanical Blade sweep — wrap hardcoded strings in `__()`

**Files:**
- Modify: ~50 Blade files under `resources/views/`

- [ ] **Step 1: Add `<html dir>` to both layouts**

In `resources/views/layouts/app.blade.php` and `resources/views/layouts/marketing.blade.php`, update `<html>`:
```blade
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
```

- [ ] **Step 2: Sweep `resources/views/pages/` directory**

For each `.blade.php` under `resources/views/pages/`:
1. Read the file
2. Identify hardcoded English strings (anything inside `{{ ... }}`, `@yield`, button labels, headings, placeholders)
3. Replace with `{{ __('key') }}` — use simple snake_case keys (`cart_title`, `cart_empty`, `home_heading`, etc.)
4. Add the key to `lang/en.php` (and `lang/ar.php` + `lang/tr.php` as placeholders) if missing

This is mechanical. Don't try to be clever — just wrap every visible English string. Aim for ~80% coverage; the remaining 20% can be filled in by you.

- [ ] **Step 3: Sweep `resources/views/components/` directory**

Same as Step 2 but for components.

- [ ] **Step 4: Sweep `resources/views/errors/` directory**

Replace hardcoded strings in 404/403/500/503.

- [ ] **Step 5: Sweep flash messages in controllers**

In `app/Http/Controllers/Web/`, replace `session()->flash('success', 'Profile updated.')` with `session()->flash('success', __('profile_updated'))`. Add keys to `lang/en.php`.

- [ ] **Step 6: Run full test suite (chunked)**

```bash
# Chunked runs due to pre-existing PHP process crash on single 600+ run
php artisan test --compact tests/Feature/Web/
php artisan test --compact tests/Feature/Middleware/
php artisan test --compact tests/Feature/Routing/
php artisan test --compact tests/Feature/Components/
php artisan test --compact tests/Feature/LocaleFilesTest.php
php artisan test --compact tests/Unit/LocalizeHelperTest.php
```

Expected: all green.

- [ ] **Step 7: Manual smoke test**

```bash
php artisan serve --no-reload &
curl -s http://127.0.0.1:8000/ | grep -E "Your cart|Welcome|Browse"  # English copy still works
curl -s http://127.0.0.1:8000/ar | grep -E "Your cart|Welcome|Browse"  # Same English (placeholders)
```

Expected: same English copy in all three URLs (placeholders for ar/tr).

- [ ] **Step 8: Commit**

```bash
git add resources/views/ app/Http/Controllers/ lang/
git commit -m "feat(i18n): wrap Blade strings in __() — site now ready for translations"
```

---

## Task 3: Logical CSS + RTL for Arabic

**Files:**
- Modify: all Blade files (CSS class refactor)
- Create: `resources/css/rtl.css`

- [ ] **Step 1: Refactor directional CSS classes**

Run from repo root:
```bash
# text alignment
find resources/views -name '*.blade.php' -exec sed -i 's/text-right/text-end/g' {} +
find resources/views -name '*.blade.php' -exec sed -i 's/text-left/text-start/g' {} +

# margins
find resources/views -name '*.blade.php' -exec sed -i -E 's/\bml-([0-9]+)/ms-\1/g' {} +
find resources/views -name '*.blade.php' -exec sed -i -E 's/\bmr-([0-9]+)/me-\1/g' {} +

# padding
find resources/views -name '*.blade.php' -exec sed -i -E 's/\bpl-([0-9]+)/ps-\1/g' {} +
find resources/views -name '*.blade.php' -exec sed -i -E 's/\bpr-([0-9]+)/pe-\1/g' {} +

# borders
find resources/views -name '*.blade.php' -exec sed -i -E 's/\bborder-l-([0-9]+)/border-s-\1/g' {} +
find resources/views -name '*.blade.php' -exec sed -i -E 's/\bborder-r-([0-9]+)/border-e-\1/g' {} +
```

Verify with: `grep -rE '\b(m[lr]-|p[lr]-|text-(left|right)|border-[lr]-)[0-9]' resources/views/` → no output.

- [ ] **Step 2: Create `resources/css/rtl.css`**

```css
/* RTL overrides for Arabic */
[dir="rtl"] .rtl-flip {
    transform: scaleX(-1);
}

/* Brutalist border tweaks for RTL — flip border sides on certain brutalist cards */
[dir="rtl"] .brutalist-card {
    border-left-width: 0;
    border-right-width: 5px;
}
```

Add `rtl-flip` class to arrow icons in pagination, checkout flow, designer dashboard. The brutalist-card class is optional — only if you have specific elements that need it.

- [ ] **Step 3: Build the CSS**

```bash
npm run build
```

Or whatever the project's build command is (check `package.json`).

- [ ] **Step 4: Manual smoke test**

```bash
php artisan serve --no-reload &
curl -s http://127.0.0.1:8000/ar | grep -E 'dir="rtl"'  # Confirm dir="rtl" set
```

Visit `/ar` in browser (or curl key pages) and check:
- Text is right-aligned
- Navigation flows right-to-left
- No layout breakage

- [ ] **Step 5: Commit**

```bash
git add resources/views/ resources/css/
git commit -m "refactor(i18n): directional CSS → logical properties + RTL stylesheet"
```

---

## Task 4: Translate `lang/ar.php`

**Files:**
- Modify: `lang/ar.php`

- [ ] **Step 1: Translate every key in `lang/ar.php`**

For each key in `lang/ar.php`, replace the English value with its Arabic equivalent. Use your own knowledge OR an Arabic translator if available.

Key categories to focus on:
- Navigation labels (header, footer, sidebar)
- Auth forms (login, register, forgot password)
- E-commerce core (cart, checkout, orders, addresses)
- Account/profile pages
- Designer/printer dashboards
- Error pages
- Flash messages
- Validation messages (optional — Laravel has good defaults)

- [ ] **Step 2: Run tests**

```bash
php artisan test --compact tests/Feature/LocaleFilesTest.php tests/Feature/Seo/ tests/Feature/Routing/
```

- [ ] **Step 3: Manual smoke test**

```bash
php artisan serve --no-reload &
curl -s http://127.0.0.1:8000/ar | grep -E "سلة|حساب|تسجيل"  # Arabic words
```

- [ ] **Step 4: Commit**

```bash
git add lang/ar.php
git commit -m "feat(i18n): translate lang/ar.php to Arabic"
```

---

## Task 5: Translate `lang/tr.php`

Same as S4 but for Turkish.

- [ ] **Step 1: Translate every key in `lang/tr.php`**

- [ ] **Step 2: Run tests + smoke test**

```bash
php artisan test --compact
curl -s http://127.0.0.1:8000/tr | grep -E "Sepet|Hesap|Giriş"  # Turkish words
```

- [ ] **Step 3: Commit**

```bash
git add lang/tr.php
git commit -m "feat(i18n): translate lang/tr.php to Turkish"
```

---

## Task 6: Tag + ship

- [ ] **Step 1: Run full test suite (chunked)**

```bash
php artisan test --compact  # chunked per env quirk
```

Expected: all green.

- [ ] **Step 2: Final manual smoke test in browser**

Visit each of `/`, `/ar`, `/tr` in browser. Confirm:
- Each renders in its language
- RTL flip works for Arabic
- Switcher in header + mobile nav
- Cookie persists
- No console errors

- [ ] **Step 3: Tag**

```bash
git tag v1.0-multilang-simplified
```

- [ ] **Step 4: Commit (if any final fixes)**

```bash
git add .
git commit -m "chore(i18n): final verification + v1.0-multilang-simplified tag"
```

---

## Self-Review

### Coverage vs original plan

| Original (Tasks 10-43) | Simplified (Tasks S1-S6) |
|---|---|
| Task 10: `<html dir>` | S2 Step 1 |
| Tasks 11: directional CSS refactor | S3 Step 1 (sed one-shot) |
| Tasks 12-18: Blade `__()` wrapping per section | S2 Step 2-5 (one mechanical sweep) |
| Task 19: validation translation test | (skip — Laravel defaults work) |
| Task 20: Phase B verification | S2 Steps 6-7 |
| Tasks 21-28: Arabic translation + RTL audit | S3 (RTL) + S4 (translation) |
| Tasks 29-33: Turkish translation | S5 |
| Tasks 34-39: Filament admin | (skip per locked decision — admin stays English) |
| Tasks 40-43: SEO + Playwright | (skip — Task 7's hreflang/canonical stays) |

### What's lost vs original plan

- **Filament admin translation** (Phase E) — admin stays English. Add later if needed.
- **Playwright visual baselines** (Phase F) — manual browser check suffices.
- **Native speaker review** (Tasks C6, D4) — you (the user) ARE the speaker for Arabic/Turkish.
- **Per-resource Filament labels** — skipped.
- **Sitemap hreflang variants** — skipped (rare feature).

### What's gained

- 34 tasks → 5 tasks (6 counting the tag)
- ~7-10 hours → ~2-3 hours total wall-clock
- No per-task SDD overhead — direct implementation
- You do the translations yourself (faster + better than AI for Arabic/Turkish)

### Self-review checks

- ✅ Same locked decisions: en/ar/tr only, no DB-content translation, Filament skipped
- ✅ Reuses Phase A infrastructure (middleware, helper, switcher, route wrap)
- ✅ Mechanical tasks (S2, S3) have no decisions to make — pure find/replace
- ✅ Translation tasks (S4, S5) have a single clear deliverable
- ✅ Final tag marks the simplified milestone
