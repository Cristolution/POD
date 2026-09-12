# Profile Editor + OTP — Handoff Plan

> **For:** Less-intelligent AI model continuing work in a new session.
> **Goal:** Add profile editor (all user info, not just bio) + OTP-verified password reset + OTP-verified account deletion. Wire to existing User, DesignerProfile, PrinterProviderProfile, Customer models.

---

## Context

- **Repo:** `c:/Users/Crist/Desktop/ADISC/POD`
- **Branch:** `local-smart-shot`
- **Stack:** Laravel 13, PHP 8.5, PHPUnit, Filament v4, Tailwind v4
- **Existing routes:** `routes/web.php` has two parallel groups for `/account` and `/{locale}/account` (unprefixed + localized) — both must be updated
- **User model:** `app/Models/User.php` has `name`, `email`, `password`, `phone`, `role` fillable; `SoftDeletes`, `HasUuids` already
- **DesignerProfile model:** has `bio` + `user_id` — needs more fields (see §3)
- **PrinterProviderProfile model:** has `company_name` + `user_id`
- **Customer:** plain `User` with `role='customer'` (no separate profile table)
- **Existing i18n:** All UI strings must use `{{ __('key') }}`. Add new keys to `lang/en.json`, `lang/ar.json`, `lang/tr.json`. Translation is approximate Arabic/Turkish; user will refine.
- **Existing media:** `storage/app/public/avatars/*.png` already populated by prior seeder (16 PNGs). User model has NO `media` relationship yet (avatar UI is a separate plan).

---

## What to build (6 tasks)

### Task 1: Expand profile tables

**Files:**
- New migration: `database/migrations/<TIMESTAMP>_expand_profile_fields.php`
- Modify: `app/Models/DesignerProfile.php` (add new fillable)
- Modify: `app/Models/PrinterProviderProfile.php` (add new fillable)
- Modify: `database/factories/DesignerProfileFactory.php` (add new fake()s)
- Modify: `database/factories/PrinterProviderProfileFactory.php` (add new fake()s)

**New columns:**
- `designer_profiles`:
  - `display_name` (string, nullable) — public-facing name
  - `tagline` (string, nullable, max 120)
  - `location` (string, nullable)
  - `website_url` (string, nullable)
  - `instagram_handle` (string, nullable)
  - `specialties` (json, nullable) — e.g. `["illustration","typography"]`
  - `years_experience` (unsignedTinyInteger, nullable)
- `printer_provider_profiles`:
  - `company_email` (string, nullable) — distinct from user email
  - `company_phone` (string, nullable)
  - `company_website` (string, nullable)
  - `address_line1`, `address_line2`, `city`, `state`, `postal_code`, `country` (all strings nullable)
  - `description` (text, nullable)
  - `min_order_value` (decimal 8,2, nullable)

**Migration example:**
```php
Schema::table('designer_profiles', function (Blueprint $t) {
    $t->string('display_name')->nullable();
    $t->string('tagline', 120)->nullable();
    $t->string('location')->nullable();
    $t->string('website_url')->nullable();
    $t->string('instagram_handle')->nullable();
    $t->json('specialties')->nullable();
    $t->unsignedTinyInteger('years_experience')->nullable();
});
// repeat for printer_provider_profiles
```

Update factories to fill new columns with faker data. Don't change existing fields.

---

### Task 2: Build account settings page

**Files:**
- Modify: `resources/views/account/dashboard.blade.php` — add "Edit my information", "Change password", "Delete account" links/buttons
- Modify: `app/Http/Controllers/Web/AccountController.php` — add `editProfile()` (GET) and `updateProfile()` (POST) methods
- New: `resources/views/account/edit-profile.blade.php` — form with all user-editable fields

**Form fields:**
- All users: `name`, `email`, `phone`
- Designers: bio + new designer fields (display_name, tagline, location, website, instagram, specialties multi-select, years_experience)
- Printer providers: company_name + new printer fields (company_email, company_phone, company_website, address_*, description, min_order_value)
- Customers: just name, email, phone

**Routes (add to BOTH groups in routes/web.php):**
```php
Route::get('/profile/edit', [AccountController::class, 'editProfile'])->name('profile.edit');
Route::patch('/profile/edit', [AccountController::class, 'updateProfile'])->name('profile.update');
```

Form posts to PATCH endpoint; uses `FormRequest` for validation (`StoreProfileRequest`, `UpdateProfileRequest`).

Validation: name required, email required + valid + unique (except current user), phone nullable but format-validated.

---

### Task 3: OTP table + send/verify helpers

**Files:**
- New migration: `database/migrations/<TIMESTAMP>_create_otps_table.php`
- New model: `app/Models/Otp.php`
- New service: `app/Services/OtpService.php` (generate, send, verify)

**Schema:**
```php
Schema::create('otps', function (Blueprint $t) {
    $t->uuid('id')->primary();
    $t->foreignUuid('user_id')->constrained()->cascadeOnDelete();
    $t->string('purpose', 32); // 'password_reset' | 'account_deletion' | 'email_change'
    $t->string('code_hash', 64); // sha256 of 6-digit code
    $t->timestamp('expires_at');
    $t->timestamp('used_at')->nullable();
    $t->timestamp('created_at')->useCurrent();
    $t->index(['user_id', 'purpose', 'used_at']);
});
```

**OtpService methods:**
- `send(User $user, string $purpose): void` — generates 6-digit code, hashes it (sha256), saves with `expires_at = now() + 10min`, sends email via `Mail::to()` using `App\Mail\OtpMail` mailable.
- `verify(User $user, string $purpose, string $code): bool` — looks up latest unused+unexpired OTP for user+purpose, hashes the supplied code, compares. Marks `used_at = now()` if match. Returns true/false.
- Limits: max 3 OTPs per user per purpose per hour (rate-limit). One active OTP per user+purpose at a time (invalidate older ones when sending new).

**Mailable:** `app/Mail/OtpMail.php` with view `resources/views/emails/otp.blade.php`. Plain text alternative. Include code + 10-minute expiry note.

**Mail config:** use `MAIL_MAILER=log` in `.env.testing` so tests don't send real emails. Use `Notification::fake()` in tests.

---

### Task 4: Password reset with OTP

**Files:**
- New: `app/Http/Controllers/Web/PasswordResetOtpController.php`
- New: `resources/views/auth/password-reset-request.blade.php`
- New: `resources/views/auth/password-reset-verify.blade.php`
- New: `resources/views/auth/password-reset-new.blade.php`

**Flow:**
1. GET `/password/reset` → email input form → POST `/password/reset` → sends OTP via OtpService → redirect to `/password/verify`
2. GET `/password/verify` → 6-digit code input → POST `/password/verify` → calls OtpService::verify → on success, store `password_reset_user_id` in session → redirect to `/password/new`
3. GET `/password/new` → new password + confirm → POST `/password/new` → update User password, clear session key, login user → redirect to home
4. Add corresponding `/{locale}/password/...` localized routes

**Routes (mirror in both groups):**
```php
Route::middleware('guest')->group(function () {
    Route::get('/password/reset', [PasswordResetOtpController::class, 'showRequestForm'])->name('password.reset.request');
    Route::post('/password/reset', [PasswordResetOtpController::class, 'sendOtp'])->name('password.reset.send');
    Route::get('/password/verify', [PasswordResetOtpController::class, 'showVerifyForm'])->name('password.reset.verify');
    Route::post('/password/verify', [PasswordResetOtpController::class, 'verifyOtp'])->name('password.reset.check');
    Route::get('/password/new', [PasswordResetOtpController::class, 'showNewForm'])->name('password.reset.new');
    Route::post('/password/new', [PasswordResetOtpController::class, 'reset'])->name('password.reset.complete');
});
```

The default Laravel password reset routes (`/password/reset`) — keep them for backwards compat OR remove if you're sure no one uses them. Recommend keeping.

---

### Task 5: Account deletion with OTP

**Files:**
- New: `app/Http/Controllers/Web/AccountDeletionController.php`
- New: `resources/views/account/delete-confirm.blade.php`
- New: `resources/views/account/delete-verify.blade.php`

**Flow:**
1. GET `/account/delete` → warning page explaining what gets deleted → click "Send code" → POST → sends OTP via OtpService::send($user, 'account_deletion') → redirect to `/account/delete/verify`
2. GET `/account/delete/verify` → 6-digit code input → POST → calls OtpService::verify → on success, soft-deletes user + related data → logout → redirect to `/account-deleted`
3. The existing `/account-deleted` page (already routed) shows a confirmation

**Cascading effects:**
- Use `User->delete()` (soft delete via `SoftDeletes` trait). This nulls tokens, invalidates sessions, marks `deleted_at`.
- For related records (designer designs, orders), they should remain in DB but hidden from public browse (already filtered via `whereNull('deleted_at')` on Design).

**Routes (add to BOTH groups):**
```php
Route::middleware('auth')->group(function () {
    Route::get('/account/delete', [AccountDeletionController::class, 'showConfirmForm'])->name('account.delete.confirm');
    Route::post('/account/delete/otp', [AccountDeletionController::class, 'sendOtp'])->name('account.delete.send');
    Route::get('/account/delete/verify', [AccountDeletionController::class, 'showVerifyForm'])->name('account.delete.verify');
    Route::post('/account/delete/verify', [AccountDeletionController::class, 'verifyAndDelete'])->name('account.delete.complete');
});
```

---

### Task 6: i18n keys + tests + commit

**i18n keys to add** (to all 3 lang JSON files):
```json
"account_edit_profile": "Edit profile" / "تعديل الملف الشخصي" / "Profili düzenle",
"account_change_password": "Change password" / "تغيير كلمة المرور" / "Şifre değiştir",
"account_delete": "Delete account" / "حذف الحساب" / "Hesabı sil",
"profile_save": "Save changes" / "حفظ التغييرات" / "Değişiklikleri kaydet",
"password_reset_title": "Reset password" / ...,
"password_reset_sent": "If that email exists, a code was sent." / ...,
"password_otp_label": "6-digit code" / ...,
"password_otp_help": "Check your email. Code expires in 10 minutes." / ...,
"password_otp_invalid": "Invalid or expired code." / ...,
"delete_account_warning": "This permanently deletes your account." / ...,
"delete_account_otp_help": "Enter the code we sent to your email." / ...,
```

**Tests to add:**
- `tests/Feature/Account/ProfileEditTest.php` — update name/email/phone/profile fields
- `tests/Feature/Account/PasswordResetOtpTest.php` — full reset flow (request → otp → new password)
- `tests/Feature/Account/AccountDeletionOtpTest.php` — deletion flow, user soft-deleted after verification
- `tests/Unit/OtpServiceTest.php` — generate, send, verify, rate-limit

Use `Notification::fake()` for email tests. Use `Hash::shouldReceive('make')` if you mock password hashing.

---

## Test commands

```bash
php artisan test --compact tests/Feature/Account/ tests/Unit/OtpServiceTest.php
php artisan test --compact  # full suite — must remain green
vendor/bin/pint --dirty --format agent
```

---

## Commit message (single commit at end)

```bash
git add app/ database/ resources/ routes/ lang/
git commit -m "feat(profile): expand profile fields + OTP password reset + OTP account deletion

- New columns on designer_profiles (display_name, tagline, location, etc.)
- New columns on printer_provider_profiles (company_email, phone, address, etc.)
- AccountController::editProfile + updateProfile (full profile form, role-aware)
- PasswordResetOtpController (request -> email -> verify -> new password)
- AccountDeletionController (confirm -> email -> verify -> soft delete)
- Otp model + OtpService (sha256-hashed 6-digit codes, 10min expiry, rate limit)
- New mailable OtpMail + emails/otp.blade.php
- Lang keys added to en/ar/tr.json for new UI
- Tests cover all flows + soft-delete + rate limiting"
```

---

## Constraints (read before starting)

- **Don't break existing 419+ tests.** Run full suite after each task.
- **Mirror every new route in both** `auth` group and `/{locale}/account` group.
- **Don't translate the emails** — `OtpMail` uses `en` only. (Emails are utilitarian.)
- **OTP code is 6 digits.** sha256-hash before storing. Compare with `hash_equals()`.
- **Rate limit:** max 3 OTP requests per user per purpose per hour. In `OtpService::send()`, count existing OTPs from past hour for that user+purpose and throw 429 if exceeded.
- **Don't auto-login after password reset** — just show success page with link to login. (Login is separate concern.)
- **OTP expires after 10 minutes.** `now()->addMinutes(10)`.
- **All UI strings go through `__()`** — never hardcode English.
- **Working tree has many pre-existing uncommitted files.** Use `git add <specific paths>` only for files in this plan. Never `git add .`.

---

## Done when

- [ ] All 6 tasks complete
- [ ] `php artisan test --compact` is green
- [ ] `vendor/bin/pint --format agent` passes
- [ ] Single commit pushed to `local-smart-shot`
- [ ] Manually verify in browser: edit profile → save → reload → data persists; request password reset → email (logged in dev) → enter code → new password → login works; delete account → email → enter code → soft-deleted, can't login
