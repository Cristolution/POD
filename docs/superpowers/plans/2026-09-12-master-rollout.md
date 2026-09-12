# Master Rollout Plan — Profile, Editor, Avatars

> **For:** Less-intelligent AI model continuing work in a new session.
> **Goal:** Execute the three feature plans in order. Commit each as you finish. Don't break existing 583+ tests.

---

## Order of execution

### Plan 1: [Avatar UI Wiring](./2026-09-12-avatar-ui.md) — **START HERE**
- Smallest scope (3 tasks)
- Foundation for other plans (User model gets `media()` relationship that Plan 2 uses)
- Self-contained — won't conflict with Plans 2/3

### Plan 2: [Profile Editor + OTP](./2026-09-12-profile-editor-otp.md) — second
- 6 tasks (schema + controllers + views + tests)
- Builds on Plan 1's User model changes (the existing `media()` accessor pattern is fine; no actual conflict)
- Touches routes/web.php twice (add groups)

### Plan 3: [Design Editor (Canvas)](./2026-09-12-design-editor-canvas.md) — third
- Biggest scope (5 tasks; canvas JS is the hard part)
- Independent of Plans 1 and 2 except that designers use the existing auth middleware

**Do them in this order.** Each plan ends with a commit. After all 3 are committed, run the full test suite once more.

---

## Global constraints (apply to all 3 plans)

- **Branch:** `local-smart-shot`. All commits land here.
- **Stack:** Laravel 13, PHP 8.5, PHPUnit 12 (NOT Pest), Tailwind v4, Alpine.js, Filament v4.
- **Don't break existing tests.** `php artisan test --compact` must stay green after every plan.
- **Don't touch gitignored files** — the `storage/app/public/` mockups and avatars are gitignored. They exist on this machine but won't be in your fresh clone. If you need to test, copy them from `C:\Users\Crist\Desktop\enhanced mockup data assets\` first.
- **Working tree has many pre-existing uncommitted files.** Always use `git add <specific paths>`. Never `git add .` or `git add -A`.
- **All UI strings via `__()`** — never hardcode English in Blade. Add new keys to all 3 lang JSON files (`en`, `ar`, `tr`).
- **Brutalist design tokens** — `border-ink-800`, `bg-coral-500`, `bg-sand-100`, `font-display`, `font-mono`. Match existing styling.
- **Pint formatting** — run `vendor/bin/pint --dirty --format agent` after every commit.

---

## Per-plan workflow

For each plan (1, 2, 3):
1. Read the plan file from top to bottom
2. Execute tasks in order (1, 2, 3, ...)
3. After each task: `php artisan test --compact` to ensure no regressions
4. After all tasks in plan: run full `php artisan test --compact`
5. After full green: `vendor/bin/pint --dirty --format agent`
6. Commit using the exact commit message at the bottom of the plan file
7. Move to next plan

---

## Pre-flight check (start of session)

```bash
git checkout local-smart-shot
git pull
php artisan test --compact  # must be green before you start
ls "C:/Users/Crist/Desktop/enhanced mockup data assets/base/"  # must exist for editor plan
```

If `php artisan test --compact` fails on session start, **stop and tell the user** — the previous session may have left broken state.

---

## Final verification (after all 3 plans)

```bash
php artisan test --compact           # must be green
vendor/bin/pint --format agent       # must pass
git log --oneline -10                # review your commits
git status                           # working tree clean (excluding pre-existing files)
```

**Manual smoke test** — log in as `lana@designstudio.test` (password: `password`):
1. Header shows Lana's avatar (or initials)
2. `/account` dashboard shows Lana's avatar
3. `/designer/edit` lets you edit display_name, tagline, location, website, instagram, specialties, years_experience → save → reload → data persists
4. `/designer/designs/new` opens the canvas editor → pick a mockup → drag/scale → save → design appears in `/designer/dashboard`
5. `/password/reset` (logged out) → enter `lana@designstudio.test` → check email (in dev, `MAIL_MAILER=log` writes to `storage/logs/laravel.log`) → copy 6-digit code → enter on verify form → set new password → log in works
6. `/account/delete` → check email → enter code → soft-deleted, can't log in anymore

If all 6 work, you're done.

---

## Done when

- [ ] All 3 plans executed and committed
- [ ] `php artisan test --compact` green
- [ ] `vendor/bin/pint --format agent` passes
- [ ] Manual smoke test (6 steps) passes
- [ ] Working tree status shows ONLY the new plan files changed (not the pre-existing uncommitted files)

**If you hit any blocker that isn't in the plan (route conflict, missing file, env var issue), STOP and ask the user. Do not invent solutions outside the plan.**
