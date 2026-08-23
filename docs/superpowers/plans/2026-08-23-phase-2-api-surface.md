# POD Platform — Phase 2 Implementation Plan (API Surface)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate the 85 OpenAPI annotations from `app/Http/Controllers/Api/OpenApi.php` (Phase 1) into real Laravel controllers, FormRequests, Resources, Action classes, Policies, and Sanctum-protected `routes/api.php`. End state: every route in the Phase 1 spec returns real JSON, validation passes, authorization is enforced, and the generated `swagger.json` is byte-equivalent to Phase 1's output.

**Architecture:** Controllers are thin — they parse the FormRequest, delegate to a single Action class in `app/Actions/`, and shape the response via an Eloquent Resource. Authorization lives in Policies invoked via `$this->authorize()` in the controller (or a constructor call). Sanctum bearer tokens (`Authorization: Bearer …`) gate every `/api/*` route except the public reads (categories/tags/designs/templates/variants/mappings) and the two `auth/*` routes.

**Tech Stack:** Laravel 13.8, PHP 8.3, Laravel Sanctum, Spatie permissions-free role check (`User::is*` helpers), Action classes (`app/Actions/`), Eloquent API Resources, Form Requests, soft-deletes, `Str::uuid()`, `Illuminate\Notifications`, `Illuminate\Pagination\LengthAwarePaginator`.

**Phase boundary:** This phase **does not** build the Blade UI (Phase 3) or Filament admin (Phase 4). It builds the API only — every route is testable via `php artisan test` and a curl loop.

---

## File Map (Phase 2 output)

| Layer            | Path                                          | Count | Purpose                                                              |
| ---------------- | --------------------------------------------- | ----- | -------------------------------------------------------------------- |
| Routes           | `routes/api.php`                              | 1     | All 85 endpoints, grouped + middleware                               |
| Middleware       | `app/Http/Middleware/{EnsureUserRole,EnsureOwnership,…}.php` | 3–5 | Role/ownership gates                                                 |
| Controllers      | `app/Http/Controllers/Api/{Name}Controller.php` | 15  | Thin HTTP layer                                                      |
| Form Requests    | `app/Http/Requests/{Store,Update}{Name}Request.php` | ~35 | Validation                                                            |
| Resources        | `app/Http/Resources/{Name}Resource.php`       | 19    | JSON shape                                                           |
| Actions          | `app/Actions/{Name}/{Verb}{Name}Action.php`   | ~25   | Business logic                                                       |
| Policies         | `app/Policies/{Name}Policy.php`               | 9      | Authorization                                                        |
| Notifications    | `app/Notifications/{Event}Notification.php`   | 5      | Mail + DB + broadcast channel                                        |
| Listeners        | `app/Listeners/{Event}Listener.php`           | ~8     | Side effects (search index, ledger, audit)                           |
| Events           | `app/Events/{Event}.php`                      | 5      | Domain events                                                        |
| Service classes  | `app/Services/{Name}Service.php`              | ~3    | Cross-cutting (cart pricing, shipment eligibility)                   |
| Tests (Feature)  | `tests/Feature/Api/*`                         | ~300   | Per-endpoint happy + sad paths                                       |

Total: **~450 files**.

---

## Task 1: Sanity check Phase 1 hand-off

**Files:** none — verification only.

- [ ] **Step 1: Confirm Phase 1 is committed and tagged**

```bash
git tag -l | grep phase-1-foundation
git log --oneline -5
```

Expected: tag present; most recent commit is the Phase 1 admin gate.

- [ ] **Step 2: Re-run Phase 1 tests to confirm green start**

```bash
php artisan test --compact tests/Feature/Api/DocsTest.php tests/Feature/Api/DocsProductionGateTest.php
```

Expected: 5 passed.

- [ ] **Step 3: Confirm OpenApi aggregator still generates cleanly**

```bash
composer test:api-docs
```

Expected: `OK` exit, no diff in `storage/api-docs/swagger.json`.

- [ ] **Step 4: Capture the current `swagger.json` as the Phase 2 regression baseline**

```bash
cp storage/api-docs/swagger.json storage/api-docs/swagger.phase1-baseline.json
git add storage/api-docs/swagger.phase1-baseline.json
git commit -m "chore(api): snapshot phase 1 swagger.json as Phase 2 baseline"
```

---

## Task 2: Install Sanctum and publish its config

**Files:**
- Modify: `composer.json`
- Modify: `config/sanctum.php` (after publish)
- Modify: `app/Models/User.php` (add `HasApiTokens` trait)

- [ ] **Step 1: Confirm Sanctum is already published by the framework installer**

```bash
ls config/sanctum.php 2>/dev/null && echo "already published" || composer require laravel/sanctum --no-interaction
```

If the file does not exist, run:

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --no-interaction
php artisan migrate
```

Expected: `config/sanctum.php` exists, `personal_access_tokens` table created.

- [ ] **Step 2: Add the `HasApiTokens` trait to the User model**

Modify `app/Models/User.php` so the `use` line at the top becomes:

```php
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
```

…and the `use` block inside the class becomes:

```php
    use HasApiTokens, HasFactory, HasUuids, Notifiable, SoftDeletes;
```

- [ ] **Step 3: Verify the trait is wired**

```bash
php artisan tinker --execute 'echo (new App\Models\User)->creatableToken() ? "ok" : "fail";'
```

Expected: `ok`.

- [ ] **Step 4: Commit**

```bash
git add composer.json composer.lock config/sanctum.php app/Models/User.php database/migrations/*_create_personal_access_tokens_table.php
git commit -m "feat(auth): install Sanctum and add HasApiTokens to User"
```

---

## Task 3: Configure Sanctum — token abilities, expiration, and guards

**Files:**
- Modify: `config/sanctum.php`
- Create: `app/Support/TokenAbility.php` (enum)

- [ ] **Step 1: Create the TokenAbility enum**

Create `app/Support/TokenAbility.php`:

```php
<?php

declare(strict_types=1);

namespace App\Support;

enum TokenAbility: string
{
    case Customer = 'customer';
    case Designer = 'designer';
    case Printer = 'printer';
    case Admin = 'admin';

    /** @return array<int,string> */
    public static function forRole(string $role): array
    {
        return match ($role) {
            'admin' => [self::Admin->value, self::Customer->value],
            'designer' => [self::Designer->value, self::Customer->value],
            'printer_provider' => [self::Printer->value, self::Customer->value],
            default => [self::Customer->value],
        };
    }
}
```

- [ ] **Step 2: Add the `expiration` and `tokenPrefix` settings**

Modify `config/sanctum.php`:

```php
'expiration' => (int) env('SANCTUM_EXPIRATION', 60 * 24 * 30),  // 30 days
'token_prefix' => 'pod_',
```

- [ ] **Step 3: Commit**

```bash
git add app/Support/TokenAbility.php config/sanctum.php
git commit -m "feat(auth): define TokenAbility enum and Sanctum expiration config"
```

---

## Task 4: Build the base API controller + JSON error renderer

**Files:**
- Create: `app/Http/Controllers/Api/Controller.php`
- Modify: `bootstrap/app.php` (register `api` middleware group + JSON exception render)

- [ ] **Step 1: Create the base Api Controller**

Create `app/Http/Controllers/Api/Controller.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /** Wrap a paginated query in the standard `{data, links, meta}` envelope. */
    protected function paginated(mixed $query, int $perPage = 25): mixed
    {
        return $query->paginate(min($perPage, 100));
    }
}
```

- [ ] **Step 2: Register the API middleware group + JSON exception renderer**

Modify `bootstrap/app.php` — replace the `->withMiddleware(...)` and `->withExceptions(...)` chains so they include:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'admin'  => \App\Http\Middleware\EnsureAdmin::class,
        'role'   => \App\Http\Middleware\EnsureUserRole::class,
        'owner'  => \App\Http\Middleware\EnsureOwnership::class,
    ]);

    $middleware->statefulApi();
})
->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*'));

    $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
        if ($request->is('api/*')) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors'  => $e->errors(),
            ], 422);
        }
    });

    $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
        if ($request->is('api/*')) {
            return response()->json(['message' => $e->getMessage() ?: 'This action is unauthorized.'], 403);
        }
    });

    $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
        if ($request->is('api/*')) {
            return response()->json(['message' => 'Resource not found.'], 404);
        }
    });

    $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
        if ($request->is('api/*')) {
            return response()->json(['message' => $e->getMessage() ?: 'HTTP error.'], $e->getStatusCode());
        }
    });
})
```

- [ ] **Step 3: Smoke test**

```bash
php artisan route:list --path=api --except-vendor
```

Expected: no errors thrown.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/Api/Controller.php bootstrap/app.php
git commit -m "feat(api): base Api controller + JSON exception rendering for /api/*"
```

---

## Task 5: Create the EnsureUserRole and EnsureOwnership middleware

**Files:**
- Create: `app/Http/Middleware/EnsureUserRole.php`
- Create: `app/Http/Middleware/EnsureOwnership.php`
- Test: `tests/Feature/Api/Middleware/EnsureUserRoleTest.php`

- [ ] **Step 1: Write the failing middleware tests**

Create `tests/Feature/Api/Middleware/EnsureUserRoleTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureUserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_blocks_anonymous_request(): void
    {
        $this->getJson('/api/admin/dashboard')->assertStatus(401);
    }

    public function test_blocks_wrong_role(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/admin/dashboard')
            ->assertForbidden();
    }

    public function test_passes_correct_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonStructure(['total_customers', 'total_designers']);
    }
}
```

(The `/api/admin/dashboard` route doesn't exist yet — the test will fail in Step 2 because the route 404s, not because the middleware blocks. We'll come back to this after Task 22 wires the admin route. If you want the test to run independently, stub the route in `bootstrap/app.php` with `Route::get('/api/admin/dashboard', fn () => response()->json(['total_customers' => 0, 'total_designers' => 0]))->middleware(['auth:sanctum', 'role:admin']);` and remove it after Task 22.)

- [ ] **Step 2: Create EnsureUserRole middleware**

Create `app/Http/Middleware/EnsureUserRole.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /** Usage: `->middleware('role:admin,designer')`. Admin always passes. */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        if (! in_array($user->role, $roles, true)) {
            return response()->json([
                'message' => "Role '{$user->role}' is not permitted on this endpoint.",
            ], 403);
        }

        return $next($request);
    }
}
```

- [ ] **Step 3: Create EnsureOwnership middleware**

Create `app/Http/Middleware/EnsureOwnership.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOwnership
{
    /**
     * Usage: `->middleware('owner:design')` — resolves the route binding
     * whose parameter name matches the model short-name and confirms the
     * authenticated user is the owner. Admin always passes.
     */
    public function handle(Request $request, Closure $next, string $modelShortName): Response
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        if ($user->isAdmin()) {
            return $next($request);
        }

        $routeKey = strtolower($modelShortName);
        /** @var Model|null $model */
        $model = $request->route($routeKey);

        if ($model === null) {
            return response()->json(['message' => "Route binding '{$routeKey}' missing."], 500);
        }

        $ownerColumn = match (true) {
            property_exists($model, 'user_id') => 'user_id',
            property_exists($model, 'designer_id') => 'designer_id',
            property_exists($model, 'printer_provider_id') => 'printer_provider_id',
            default => null,
        };

        if ($ownerColumn === null || (string) $model->{$ownerColumn} !== (string) $user->id) {
            return response()->json(['message' => 'You do not own this resource.'], 403);
        }

        return $next($request);
    }
}
```

- [ ] **Step 4: Commit (skip running tests until Task 22 wires the routes)**

```bash
git add app/Http/Middleware/EnsureUserRole.php app/Http/Middleware/EnsureOwnership.php tests/Feature/Api/Middleware/EnsureUserRoleTest.php
git commit -m "feat(middleware): role + ownership gates for /api/*"
```

---

## Task 6: Create all 19 Eloquent Resources

**Files:**
- Create: `app/Http/Resources/{User,Address,DesignerProfile,PrinterProfile,Category,Tag,Design,ProductTemplate,ProductVariant,DesignProductMapping,CartItem,Order,OrderItem,DeliveryCompany,Shipment,Payment,Media,Notification,Setting}Resource.php`

These 19 files mirror the schemas in `app/Http/Controllers/Api/OpenApi.php`. They share a thin shape:

- `id` (UUID), then `$this->whenLoaded(...)` for every relation
- `$this->whenCounted(...)` for any `withCount` aggregate
- Dates cast to ISO 8601
- No SQL or authorization logic — pure JSON shaping

- [ ] **Step 1: Write a base Resource helper**

Create `app/Http/Resources/Concerns/FormatsDates.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\Concerns;

trait FormatsDates
{
    protected function iso(mixed $value): ?string
    {
        return $value?->toIso8601String();
    }
}
```

- [ ] **Step 2: Write the UserResource**

Create `app/Http/Resources/UserResource.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsDates;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    use FormatsDates;

    public function toArray($request): array
    {
        $showEmail = $request->user()?->isAdmin()
            || $request->user()?->id === $this->id;

        return [
            'id'         => $this->id,
            'role'       => $this->role,
            'name'       => $this->name,
            'email'      => $showEmail ? $this->email : null,
            'phone'      => $this->phone,
            'created_at' => $this->iso($this->created_at),
            'updated_at' => $this->iso($this->updated_at),

            'designer_profile' => $this->whenLoaded('designerProfile',
                fn () => new DesignerProfileResource($this->designerProfile)),
            'printer_profile'  => $this->whenLoaded('printerProfile',
                fn () => new PrinterProfileResource($this->printerProfile)),
            'addresses'        => $this->whenLoaded('addresses',
                fn () => AddressResource::collection($this->addresses)),
            'designs_count'    => $this->whenCounted('designs'),
            'orders_count'     => $this->whenCounted('orders'),
        ];
    }
}
```

- [ ] **Step 3: Write the AddressResource**

Create `app/Http/Resources/AddressResource.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsDates;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Address */
class AddressResource extends JsonResource
{
    use FormatsDates;

    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'user_id'     => $this->user_id,
            'label'       => $this->label,
            'line1'       => $this->line1,
            'line2'       => $this->line2,
            'city'        => $this->city,
            'state'       => $this->state,
            'postal_code' => $this->postal_code,
            'country'     => $this->country,
            'phone'       => $this->phone,
            'is_default'  => $this->is_default,
            'created_at'  => $this->iso($this->created_at),
            'updated_at'  => $this->iso($this->updated_at),
        ];
    }
}
```

- [ ] **Step 4: Write the DesignerProfileResource and PrinterProfileResource**

Create `app/Http/Resources/DesignerProfileResource.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsDates;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\DesignerProfile */
class DesignerProfileResource extends JsonResource
{
    use FormatsDates;

    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'user_id'       => $this->user_id,
            'display_name'  => $this->display_name,
            'bio'           => $this->bio,
            'avatar_url'    => $this->avatar_url,
            'social_links'  => $this->social_links,
            'is_verified'   => $this->is_verified,
            'created_at'    => $this->iso($this->created_at),
            'updated_at'    => $this->iso($this->updated_at),
            'user'          => $this->whenLoaded('user',
                fn () => new UserResource($this->user)),
            'designs_count' => $this->whenCounted('designs'),
        ];
    }
}
```

Create `app/Http/Resources/PrinterProfileResource.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsDates;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PrinterProfile */
class PrinterProfileResource extends JsonResource
{
    use FormatsDates;

    public function toArray($request): array
    {
        return [
            'id'                 => $this->id,
            'user_id'            => $this->user_id,
            'company_name'       => $this->company_name,
            'bio'                => $this->bio,
            'capabilities'       => $this->capabilities,
            'coverage_zones'     => $this->coverage_zones,
            'is_verified'        => $this->is_verified,
            'created_at'         => $this->iso($this->created_at),
            'updated_at'         => $this->iso($this->updated_at),
            'user'               => $this->whenLoaded('user',
                fn () => new UserResource($this->user)),
            'templates_count'    => $this->whenCounted('productTemplates'),
        ];
    }
}
```

- [ ] **Step 5: Write the 15 remaining resources**

Create each of these files using the same `FormatsDates` trait + `whenLoaded`/`whenCounted` shape. The full bodies:

```php
// app/Http/Resources/CategoryResource.php
/** @mixin \App\Models\Category */
class CategoryResource extends JsonResource
{
    use FormatsDates;

    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'slug'       => $this->slug,
            'parent_id'  => $this->parent_id,
            'created_at' => $this->iso($this->created_at),
            'updated_at' => $this->iso($this->updated_at),
            'parent'     => $this->whenLoaded('parent', fn () => new self($this->parent)),
            'children'   => $this->whenLoaded('children', fn () => self::collection($this->children)),
            'designs_count' => $this->whenCounted('designs'),
        ];
    }
}

// app/Http/Resources/TagResource.php
/** @mixin \App\Models\Tag */
class TagResource extends JsonResource
{
    use FormatsDates;

    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'slug'          => $this->slug,
            'created_at'    => $this->iso($this->created_at),
            'updated_at'    => $this->iso($this->updated_at),
            'designs_count' => $this->whenCounted('designs'),
        ];
    }
}

// app/Http/Resources/DesignResource.php
/** @mixin \App\Models\Design */
class DesignResource extends JsonResource
{
    use FormatsDates;

    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'designer_id'   => $this->designer_id,
            'category_id'   => $this->category_id,
            'title'         => $this->title,
            'description'   => $this->description,
            'status'        => $this->status,
            'base_price'    => $this->base_price,
            'created_at'    => $this->iso($this->created_at),
            'updated_at'    => $this->iso($this->updated_at),
            'designer'      => $this->whenLoaded('designer', fn () => new UserResource($this->designer)),
            'category'      => $this->whenLoaded('category', fn () => new CategoryResource($this->category)),
            'tags'          => $this->whenLoaded('tags', fn () => TagResource::collection($this->tags)),
            'media'         => $this->whenLoaded('media', fn () => MediaResource::collection($this->media)),
            'mappings_count'=> $this->whenCounted('mappings'),
        ];
    }
}

// app/Http/Resources/ProductTemplateResource.php
/** @mixin \App\Models\ProductTemplate */
class ProductTemplateResource extends JsonResource
{
    use FormatsDates;

    public function toArray($request): array
    {
        return [
            'id'                 => $this->id,
            'printer_provider_id'=> $this->printer_provider_id,
            'name'               => $this->name,
            'type'               => $this->type,
            'base_cost'          => $this->base_cost,
            'specs'              => $this->specs,
            'is_active'          => $this->is_active,
            'created_at'         => $this->iso($this->created_at),
            'updated_at'         => $this->iso($this->updated_at),
            'printer'            => $this->whenLoaded('printer', fn () => new UserResource($this->printer)),
            'variants'           => $this->whenLoaded('variants', fn () => ProductVariantResource::collection($this->variants)),
            'variants_count'     => $this->whenCounted('variants'),
        ];
    }
}

// app/Http/Resources/ProductVariantResource.php
/** @mixin \App\Models\ProductVariant */
class ProductVariantResource extends JsonResource
{
    use FormatsDates;

    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'template_id'  => $this->template_id,
            'sku'          => $this->sku,
            'attributes'   => $this->attributes,
            'price_delta'  => $this->price_delta,
            'is_active'    => $this->is_active,
            'created_at'   => $this->iso($this->created_at),
            'updated_at'   => $this->iso($this->updated_at),
            'template'     => $this->whenLoaded('template', fn () => new ProductTemplateResource($this->template)),
        ];
    }
}

// app/Http/Resources/DesignProductMappingResource.php
/** @mixin \App\Models\DesignProductMapping */
class DesignProductMappingResource extends JsonResource
{
    use FormatsDates;

    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'design_id'           => $this->design_id,
            'product_template_id' => $this->product_template_id,
            'preferred_printer_id'=> $this->preferred_printer_id,
            'final_price'         => $this->final_price,
            'is_active'           => $this->is_active,
            'created_at'          => $this->iso($this->created_at),
            'updated_at'          => $this->iso($this->updated_at),
            'design'              => $this->whenLoaded('design', fn () => new DesignResource($this->design)),
            'template'            => $this->whenLoaded('template', fn () => new ProductTemplateResource($this->template)),
            'preferred_printer'   => $this->whenLoaded('preferredPrinter', fn () => new UserResource($this->preferredPrinter)),
        ];
    }
}

// app/Http/Resources/CartItemResource.php
/** @mixin \App\Models\CartItem */
class CartItemResource extends JsonResource
{
    use FormatsDates;

    public function toArray($request): array
    {
        $lineTotal = $this->line_total !== null
            ? (float) $this->line_total
            : (float) $this->mapping?->final_price * $this->quantity;

        return [
            'id'                  => $this->id,
            'user_id'             => $this->user_id,
            'design_product_mapping_id' => $this->design_product_mapping_id,
            'product_variant_id'  => $this->product_variant_id,
            'quantity'            => $this->quantity,
            'variant_key'         => $this->variant_key,
            'unit_price'          => $this->mapping?->final_price,
            'line_total'          => $lineTotal,
            'created_at'          => $this->iso($this->created_at),
            'updated_at'          => $this->iso($this->updated_at),
            'mapping'             => $this->whenLoaded('mapping', fn () => new DesignProductMappingResource($this->mapping)),
            'variant'             => $this->whenLoaded('variant', fn () => new ProductVariantResource($this->variant)),
        ];
    }
}

// app/Http/Resources/OrderResource.php
/** @mixin \App\Models\Order */
class OrderResource extends JsonResource
{
    use FormatsDates;

    public function toArray($request): array
    {
        return [
            'id'                   => $this->id,
            'order_number'         => $this->order_number,
            'user_id'              => $this->user_id,
            'status'               => $this->status,
            'subtotal'             => $this->subtotal,
            'shipping_total'       => $this->shipping_total,
            'tax_total'            => $this->tax_total,
            'total_amount'         => $this->total_amount,
            'currency'             => $this->currency,
            'shipping_address_id'  => $this->shipping_address_id,
            'billing_address_id'   => $this->billing_address_id,
            'placed_at'            => $this->iso($this->placed_at),
            'created_at'           => $this->iso($this->created_at),
            'updated_at'           => $this->iso($this->updated_at),
            'user'                 => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'items'                => $this->whenLoaded('items', fn () => OrderItemResource::collection($this->items)),
            'shipping_address'     => $this->whenLoaded('shippingAddress', fn () => new AddressResource($this->shippingAddress)),
            'billing_address'      => $this->whenLoaded('billingAddress', fn () => new AddressResource($this->billingAddress)),
            'payment'              => $this->whenLoaded('payment', fn () => new PaymentResource($this->payment)),
            'shipments'            => $this->whenLoaded('shipments', fn () => ShipmentResource::collection($this->shipments)),
        ];
    }
}

// app/Http/Resources/OrderItemResource.php
/** @mixin \App\Models\OrderItem */
class OrderItemResource extends JsonResource
{
    use FormatsDates;

    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'order_id'            => $this->order_id,
            'design_product_mapping_id' => $this->design_product_mapping_id,
            'product_variant_id'  => $this->product_variant_id,
            'printer_provider_id' => $this->printer_provider_id,
            'design_title_snapshot'=> $this->design_title_snapshot,
            'variant_snapshot'    => $this->variant_snapshot,
            'unit_price'          => $this->unit_price,
            'quantity'            => $this->quantity,
            'line_total'          => $this->line_total,
            'status'              => $this->status,
            'created_at'          => $this->iso($this->created_at),
            'updated_at'          => $this->iso($this->updated_at),
            'mapping'             => $this->whenLoaded('mapping', fn () => new DesignProductMappingResource($this->mapping)),
            'variant'             => $this->whenLoaded('variant', fn () => new ProductVariantResource($this->variant)),
            'printer'             => $this->whenLoaded('printer', fn () => new UserResource($this->printer)),
            'order'               => $this->whenLoaded('order', fn () => new OrderResource($this->order)),
        ];
    }
}

// app/Http/Resources/DeliveryCompanyResource.php
/** @mixin \App\Models\DeliveryCompany */
class DeliveryCompanyResource extends JsonResource
{
    use FormatsDates;

    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'coverage_zones'      => $this->coverage_zones,
            'tracking_url_pattern'=> $this->tracking_url_pattern,
            'is_active'           => $this->is_active,
            'created_at'          => $this->iso($this->created_at),
            'updated_at'          => $this->iso($this->updated_at),
            'shipments_count'     => $this->whenCounted('shipments'),
        ];
    }
}

// app/Http/Resources/ShipmentResource.php
/** @mixin \App\Models\Shipment */
class ShipmentResource extends JsonResource
{
    use FormatsDates;

    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'order_id'            => $this->order_id,
            'order_item_id'       => $this->order_item_id,
            'delivery_company_id' => $this->delivery_company_id,
            'tracking_number'     => $this->tracking_number,
            'status'              => $this->status,
            'shipped_at'          => $this->iso($this->shipped_at),
            'delivered_at'        => $this->iso($this->delivered_at),
            'created_at'          => $this->iso($this->created_at),
            'updated_at'          => $this->iso($this->updated_at),
            'order'               => $this->whenLoaded('order', fn () => new OrderResource($this->order)),
            'delivery_company'    => $this->whenLoaded('deliveryCompany', fn () => new DeliveryCompanyResource($this->deliveryCompany)),
        ];
    }
}

// app/Http/Resources/PaymentResource.php
/** @mixin \App\Models\Payment */
class PaymentResource extends JsonResource
{
    use FormatsDates;

    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'order_id'        => $this->order_id,
            'method'          => $this->method,
            'status'          => $this->status,
            'amount'          => $this->amount,
            'currency'        => $this->currency,
            'provider'        => $this->provider,
            'provider_charge_id' => $this->provider_charge_id,
            'confirmed_at'    => $this->iso($this->confirmed_at),
            'created_at'      => $this->iso($this->created_at),
            'updated_at'      => $this->iso($this->updated_at),
            'order'           => $this->whenLoaded('order', fn () => new OrderResource($this->order)),
        ];
    }
}

// app/Http/Resources/MediaResource.php
/** @mixin \App\Models\Media */
class MediaResource extends JsonResource
{
    use FormatsDates;

    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'mediable_type'       => $this->mediable_type,
            'mediable_id'         => $this->mediable_id,
            'collection'          => $this->collection,
            'disk'                => $this->disk,
            'path'                => $this->path,
            'mime_type'           => $this->mime_type,
            'size_bytes'          => $this->size_bytes,
            'is_primary'          => $this->is_primary,
            'created_at'          => $this->iso($this->created_at),
            'updated_at'          => $this->iso($this->updated_at),
            'url'                 => $this->when(true, fn () => \Storage::disk($this->disk)->url($this->path)),
            'mediable'            => $this->whenLoaded('mediable', fn () => match (true) {
                $this->mediable instanceof \App\Models\Design => new DesignResource($this->mediable),
                $this->mediable instanceof \App\Models\ProductTemplate => new ProductTemplateResource($this->mediable),
                $this->mediable instanceof \App\Models\User => new UserResource($this->mediable),
                default => null,
            }),
        ];
    }
}

// app/Http/Resources/NotificationResource.php
/** @mixin \App\Models\Notification */
class NotificationResource extends JsonResource
{
    use FormatsDates;

    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'type'            => $this->type,
            'notifiable_type' => $this->notifiable_type,
            'notifiable_id'   => $this->notifiable_id,
            'data'            => $this->data,
            'read_at'         => $this->iso($this->read_at),
            'created_at'      => $this->iso($this->created_at),
        ];
    }
}

// app/Http/Resources/SettingResource.php
/** @mixin \App\Models\Setting */
class SettingResource extends JsonResource
{
    use FormatsDates;

    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'key'        => $this->key,
            'value'      => $this->value,
            'is_public'  => $this->is_public,
            'namespace'  => $this->namespace,
            'created_at' => $this->iso($this->created_at),
            'updated_at' => $this->iso($this->updated_at),
        ];
    }
}
```

- [ ] **Step 6: Smoke test**

```bash
php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->boot(); foreach (glob("app/Http/Resources/*Resource.php") as $f) { require_once $f; } echo count(glob("app/Http/Resources/*Resource.php"))." resources loaded\n";'
```

Expected: `19 resources loaded`.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Resources/
git commit -m "feat(resources): add 19 Eloquent API resources matching Phase 1 schemas"
```

---

## Task 7: Create the Auth + Me controllers, FormRequests, and the Login/Register Action

**Files:**
- Create: `app/Http/Controllers/Api/AuthController.php`
- Create: `app/Http/Controllers/Api/MeController.php`
- Create: `app/Actions/Auth/RegisterAction.php`
- Create: `app/Actions/Auth/LoginAction.php`
- Create: `app/Actions/Auth/LogoutAction.php`
- Create: `app/Actions/User/UpdateMeAction.php`
- Create: `app/Actions/User/UpdatePasswordAction.php`
- Create: `app/Actions/User/DeleteMeAction.php`
- Create: `app/Http/Requests/Auth/RegisterRequest.php`
- Create: `app/Http/Requests/Auth/LoginRequest.php`
- Create: `app/Http/Requests/User/UpdateMeRequest.php`
- Create: `app/Http/Requests/User/UpdatePasswordRequest.php`
- Test: `tests/Feature/Api/Auth/RegisterTest.php`
- Test: `tests/Feature/Api/Auth/LoginTest.php`
- Test: `tests/Feature/Api/Me/ShowTest.php`
- Test: `tests/Feature/Api/Me/UpdateTest.php`

- [ ] **Step 1: Create the RegisterRequest**

Create `app/Http/Requests/Auth/RegisterRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:120'],
            'email'    => ['required', 'email', 'max:180', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'max:32'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'role'     => ['required', 'in:customer,designer,printer_provider'],
        ];
    }
}
```

- [ ] **Step 2: Create the LoginRequest**

Create `app/Http/Requests/Auth/LoginRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'email'       => ['required', 'email'],
            'password'    => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ];
    }
}
```

- [ ] **Step 3: Create the UpdateMeRequest and UpdatePasswordRequest**

Create `app/Http/Requests/User/UpdateMeRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMeRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        $userId = $this->user()->id;
        return [
            'name'  => ['sometimes', 'string', 'max:120'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32'],
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($userId, 'id')],
            'current_password' => ['required_with:password', 'string'],
            'password' => ['sometimes', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)->mixedCase()->numbers()],
        ];
    }
}
```

Create `app/Http/Requests/User/UpdatePasswordRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ];
    }
}
```

- [ ] **Step 4: Create the RegisterAction**

Create `app/Actions/Auth/RegisterAction.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use App\Support\TokenAbility;
use Illuminate\Support\Facades\Hash;

class RegisterAction
{
    /** @param array<string,mixed> $data */
    public function execute(array $data): array
    {
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'    => $data['phone'] ?? null,
            'role'     => $data['role'],
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken(
            name: $data['device_name'] ?? 'web',
            abilities: TokenAbility::forRole($user->role),
        );

        return [$user, $token->plainTextToken];
    }
}
```

- [ ] **Step 5: Create the LoginAction**

Create `app/Actions/Auth/LoginAction.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use App\Support\TokenAbility;
use Illuminate\Support\Facades\Hash;

class LoginAction
{
    /** @return array{0: User, 1: string}|null Returns null on bad credentials. */
    public function execute(string $email, string $password, ?string $deviceName): ?array
    {
        $user = User::where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            return null;
        }

        $token = $user->createToken(
            name: $deviceName ?? 'web',
            abilities: TokenAbility::forRole($user->role),
        );

        return [$user, $token->plainTextToken];
    }
}
```

- [ ] **Step 6: Create the LogoutAction**

Create `app/Actions/Auth/LogoutAction.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;

class LogoutAction
{
    public function execute(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
```

- [ ] **Step 7: Create the UpdateMeAction, UpdatePasswordAction, DeleteMeAction**

Create `app/Actions/User/UpdateMeAction.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdateMeAction
{
    /** @param array<string,mixed> $data */
    public function execute(User $user, array $data): User
    {
        if (isset($data['password'])) {
            abort_unless(Hash::check($data['current_password'], $user->password), 422, 'Current password is incorrect.');
            $user->password = Hash::make($data['password']);
            unset($data['current_password'], $data['password']);
        }

        $user->fill($data)->save();
        return $user->refresh();
    }
}
```

Create `app/Actions/User/UpdatePasswordAction.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdatePasswordAction
{
    public function execute(User $user, string $current, string $new): void
    {
        abort_unless(Hash::check($current, $user->password), 422, 'Current password is incorrect.');
        $user->password = Hash::make($new);
        $user->save();
    }
}
```

Create `app/Actions/User/DeleteMeAction.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;

class DeleteMeAction
{
    public function execute(User $user): void
    {
        $hasActiveDesigns = $user->designs()->exists();
        $hasActiveTemplates = $user->productTemplates()->exists();

        abort_if($hasActiveDesigns || $hasActiveTemplates, 409,
            'Account cannot be deleted while active designs or templates exist.');
        $user->delete();
    }
}
```

- [ ] **Step 8: Create the AuthController**

Create `app/Http/Controllers/Api/AuthController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Auth\LoginAction;
use App\Actions\Auth\LogoutAction;
use App\Actions\Auth\RegisterAction;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        private readonly RegisterAction $register,
        private readonly LoginAction $login,
        private readonly LogoutAction $logout,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        [$user, $token] = $this->register->execute($request->validated());

        return response()->json([
            'user'  => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->login->execute(
            $request->string('email')->lower()->value(),
            $request->string('password')->value(),
            $request->string('device_name')->value() ?: null,
        );

        abort_if($result === null, 401, 'Invalid credentials.');

        [$user, $token] = $result;

        return response()->json([
            'user'  => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function logout(): JsonResponse
    {
        $this->logout->execute($this->request->user());
        return response()->json(null, 204);
    }
}
```

Note: Add a `protected \Illuminate\Http\Request $request;` constructor injection in `Controller` — or inject Request into the action call. The simplest path: use auth helper:

```php
public function logout(): JsonResponse
{
    $this->logout->execute(auth()->user());
    return response()->json(null, 204);
}
```

- [ ] **Step 9: Create the MeController**

Create `app/Http/Controllers/Api/MeController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\User\DeleteMeAction;
use App\Actions\User\UpdateMeAction;
use App\Actions\User\UpdatePasswordAction;
use App\Http\Requests\User\UpdateMeRequest;
use App\Http\Requests\User\UpdatePasswordRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

class MeController extends Controller
{
    public function __construct(
        private readonly UpdateMeAction $updateMe,
        private readonly UpdatePasswordAction $updatePassword,
        private readonly DeleteMeAction $deleteMe,
    ) {}

    public function show(): JsonResponse
    {
        return response()->json([
            'data' => new UserResource(auth()->user()->load(['designerProfile', 'printerProfile'])),
        ]);
    }

    public function update(UpdateMeRequest $request): JsonResponse
    {
        $user = $this->updateMe->execute(auth()->user(), $request->validated());
        return response()->json(['data' => new UserResource($user)]);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $this->updatePassword->execute(
            auth()->user(),
            $request->string('current_password')->value(),
            $request->string('password')->value(),
        );
        return response()->json(null, 204);
    }

    public function destroy(): JsonResponse
    {
        $this->deleteMe->execute(auth()->user());
        return response()->json(null, 204);
    }
}
```

- [ ] **Step 10: Write the failing feature tests**

Create `tests/Feature/Api/Auth/RegisterTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register_and_receive_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Ada Lovelace',
            'email'                 => 'ada@example.com',
            'password'              => 'SecretPass1!',
            'password_confirmation' => 'SecretPass1!',
            'role'                  => 'customer',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'email', 'role'], 'token']);

        $this->assertDatabaseHas('users', ['email' => 'ada@example.com', 'role' => 'customer']);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_register_validation_rejects_duplicate_email(): void
    {
        \App\Models\User::factory()->create(['email' => 'dup@example.com']);

        $this->postJson('/api/auth/register', [
            'name'                  => 'X',
            'email'                 => 'dup@example.com',
            'password'              => 'SecretPass1!',
            'password_confirmation' => 'SecretPass1!',
            'role'                  => 'customer',
        ])->assertStatus(422)
          ->assertJsonValidationErrors('email');
    }

    public function test_admin_role_cannot_be_self_assigned(): void
    {
        $this->postJson('/api/auth/register', [
            'name'                  => 'X',
            'email'                 => 'x@example.com',
            'password'              => 'SecretPass1!',
            'password_confirmation' => 'SecretPass1!',
            'role'                  => 'admin',
        ])->assertStatus(422)
          ->assertJsonValidationErrors('role');
    }
}
```

Create `tests/Feature/Api/Auth/LoginTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_for_valid_credentials(): void
    {
        User::factory()->create([
            'email'    => 'login@example.com',
            'password' => bcrypt('SecretPass1!'),
            'role'     => 'customer',
        ]);

        $this->postJson('/api/auth/login', [
            'email'       => 'login@example.com',
            'password'    => 'SecretPass1!',
            'device_name' => 'phpunit',
        ])
            ->assertOk()
            ->assertJsonStructure(['user' => ['id'], 'token']);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $this->postJson('/api/auth/login', [
            'email'    => 'nope@example.com',
            'password' => 'wrong',
        ])->assertStatus(401);
    }
}
```

Create `tests/Feature/Api/Me/ShowTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Me;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_fetch_self(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_anonymous_is_rejected(): void
    {
        $this->getJson('/api/me')->assertStatus(401);
    }
}
```

Create `tests/Feature/Api/Me/UpdateTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Me;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_name_and_phone(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/me', [
                'name'  => 'New Name',
                'phone' => '+15551234567',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.phone', '+15551234567');
    }

    public function test_password_update_requires_current_password(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'password' => Hash::make('OldPass1!'),
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/me', [
                'current_password' => 'WRONG',
                'password'         => 'NewPass2!',
                'password_confirmation' => 'NewPass2!',
            ])
            ->assertStatus(422);
    }
}
```

- [ ] **Step 11: Mount the routes**

Append to `routes/api.php`:

```php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MeController;

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login',    [AuthController::class, 'login'])->name('auth.login');
    Route::post('/logout',   [AuthController::class, 'logout'])
        ->middleware('auth:sanctum')->name('auth.logout');
});

Route::middleware('auth:sanctum')->prefix('me')->group(function (): void {
    Route::get('/',         [MeController::class, 'show'])->name('me.show');
    Route::patch('/',       [MeController::class, 'update'])->name('me.update');
    Route::patch('/password', [MeController::class, 'updatePassword'])->name('me.updatePassword');
    Route::delete('/',      [MeController::class, 'destroy'])->name('me.destroy');
});
```

- [ ] **Step 12: Run the tests**

```bash
php artisan test --compact tests/Feature/Api/Auth tests/Feature/Api/Me
```

Expected: 9 passed.

- [ ] **Step 13: Regenerate the spec to confirm annotations still produce the same JSON**

```bash
composer test:api-docs
```

Expected: `OK`. (The annotations live in `OpenApi.php`, not the controllers — this is the regression check.)

- [ ] **Step 14: Commit**

```bash
git add app/Http/Controllers/Api/AuthController.php app/Http/Controllers/Api/MeController.php \
  app/Actions/Auth app/Actions/User app/Http/Requests/Auth app/Http/Requests/User \
  tests/Feature/Api/Auth tests/Feature/Api/Me routes/api.php
git commit -m "feat(api): Auth + Me controllers with FormRequests, Actions, and Sanctum token issuance"
```

---

## Task 8: User + Designer + Printer + Address controllers + policies

**Files:**
- Create: `app/Http/Controllers/Api/UserController.php`
- Create: `app/Http/Controllers/Api/DesignerController.php`
- Create: `app/Http/Controllers/Api/PrinterController.php`
- Create: `app/Http/Controllers/Api/AddressController.php`
- Create: `app/Policies/UserPolicy.php`
- Create: `app/Policies/DesignerProfilePolicy.php`
- Create: `app/Policies/PrinterProfilePolicy.php`
- Create: `app/Policies/AddressPolicy.php`
- Create: `app/Actions/Admin/{UpdateUser,DeleteUser,RestoreUser}Action.php`
- Create: `app/Actions/Designer/{StoreDesignerProfile,UpdateDesignerProfile,DeleteDesignerProfile}Action.php`
- Create: `app/Actions/Printer/{StorePrinterProfile,UpdatePrinterProfile,DeletePrinterProfile}Action.php`
- Create: `app/Actions/Address/{StoreAddress,UpdateAddress,DeleteAddress}Action.php`
- Create: `app/Http/Requests/User/UpdateUserRequest.php` (admin)
- Create: `app/Http/Requests/Designer/{Store,Update}DesignerProfileRequest.php`
- Create: `app/Http/Requests/Printer/{Store,Update}PrinterProfileRequest.php`
- Create: `app/Http/Requests/Address/{Store,Update}AddressRequest.php`
- Test: `tests/Feature/Api/Users/*Test.php` (3 files)
- Test: `tests/Feature/Api/Designers/*Test.php` (3 files)
- Test: `tests/Feature/Api/Printers/*Test.php` (3 files)
- Test: `tests/Feature/Api/Addresses/*Test.php` (3 files)

- [ ] **Step 1: Create the four policies**

Each policy follows the pattern: admin always passes, owner passes, else deny.

Create `app/Policies/UserPolicy.php`:

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool { return $user->isAdmin(); }
    public function update(User $user, User $target): bool { return $user->id === $target->id; }
    public function delete(User $user, User $target): bool { return $user->id === $target->id; }
    public function restore(User $user): bool { return $user->isAdmin(); }
}
```

Create `app/Policies/DesignerProfilePolicy.php`:

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DesignerProfile;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DesignerProfilePolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool { return $user->isAdmin() ? true : null; }

    public function view(?User $user, DesignerProfile $profile): bool { return true; }
    public function create(User $user): bool { return $user->isDesigner(); }
    public function update(User $user, DesignerProfile $profile): bool { return $profile->user_id === $user->id; }
    public function delete(User $user, DesignerProfile $profile): bool { return $profile->user_id === $user->id; }
}
```

Create `app/Policies/PrinterProfilePolicy.php`:

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PrinterProfile;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PrinterProfilePolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool { return $user->isAdmin() ? true : null; }

    public function view(?User $user, PrinterProfile $profile): bool { return true; }
    public function create(User $user): bool { return $user->isPrinterProvider(); }
    public function update(User $user, PrinterProfile $profile): bool { return $profile->user_id === $user->id; }
    public function delete(User $user, PrinterProfile $profile): bool { return $profile->user_id === $user->id; }
}
```

Create `app/Policies/AddressPolicy.php`:

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Address;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AddressPolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool { return $user->isAdmin() ? true : null; }

    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Address $address): bool { return $address->user_id === $user->id; }
    public function create(User $user): bool { return true; }
    public function update(User $user, Address $address): bool { return $address->user_id === $user->id; }
    public function delete(User $user, Address $address): bool { return $address->user_id === $user->id; }
}
```

- [ ] **Step 2: Register the policies**

In `app/Providers/AppServiceProvider.php`, inside `boot()`:

```php
Gate::policy(\App\Models\User::class, \App\Policies\UserPolicy::class);
Gate::policy(\App\Models\DesignerProfile::class, \App\Policies\DesignerProfilePolicy::class);
Gate::policy(\App\Models\PrinterProfile::class, \App\Policies\PrinterProfilePolicy::class);
Gate::policy(\App\Models\Address::class, \App\Policies\AddressPolicy::class);
```

Add `use Illuminate\Support\Facades\Gate;` to the imports.

- [ ] **Step 3: Create the seven admin Actions**

Create `app/Actions/Admin/UpdateUserAction.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdateUserAction
{
    /** @param array<string,mixed> $data */
    public function execute(User $target, array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $target->fill($data)->save();
        return $target->refresh();
    }
}
```

Create `app/Actions/Admin/DeleteUserAction.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\User;

class DeleteUserAction
{
    public function execute(User $target): void { $target->delete(); }
}
```

Create `app/Actions/Admin/RestoreUserAction.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\User;

class RestoreUserAction
{
    public function execute(User $target): User
    {
        $target->restore();
        return $target->refresh();
    }
}
```

Create the designer profile actions:

```php
// app/Actions/Designer/StoreDesignerProfileAction.php
namespace App\Actions\Designer;

use App\Models\DesignerProfile;
use App\Models\User;

class StoreDesignerProfileAction
{
    /** @param array<string,mixed> $data */
    public function execute(User $user, array $data): DesignerProfile
    {
        return $user->designerProfile()->create($data);
    }
}

// app/Actions/Designer/UpdateDesignerProfileAction.php
namespace App\Actions\Designer;

use App\Models\DesignerProfile;

class UpdateDesignerProfileAction
{
    /** @param array<string,mixed> $data */
    public function execute(DesignerProfile $profile, array $data): DesignerProfile
    {
        $profile->fill($data)->save();
        return $profile->refresh();
    }
}

// app/Actions/Designer/DeleteDesignerProfileAction.php
namespace App\Actions\Designer;

use App\Models\DesignerProfile;

class DeleteDesignerProfileAction
{
    public function execute(DesignerProfile $profile): void
    {
        abort_if($profile->designs()->exists(), 409, 'Designer profile still has active designs.');
        $profile->delete();
    }
}
```

(Repeat the same shape for Printer: `StorePrinterProfileAction`, `UpdatePrinterProfileAction`, `DeletePrinterProfileAction`.)

Create the address actions:

```php
// app/Actions/Address/StoreAddressAction.php
namespace App\Actions\Address;

use App\Models\Address;
use App\Models\User;

class StoreAddressAction
{
    /** @param array<string,mixed> $data */
    public function execute(User $user, array $data): Address
    {
        if (! empty($data['is_default'])) {
            $user->addresses()->update(['is_default' => false]);
        }
        return $user->addresses()->create($data);
    }
}

// app/Actions/Address/UpdateAddressAction.php
namespace App\Actions\Address;

use App\Models\Address;

class UpdateAddressAction
{
    /** @param array<string,mixed> $data */
    public function execute(Address $address, array $data): Address
    {
        if (! empty($data['is_default'])) {
            $address->user->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }
        $address->fill($data)->save();
        return $address->refresh();
    }
}

// app/Actions/Address/DeleteAddressAction.php
namespace App\Actions\Address;

use App\Models\Address;

class DeleteAddressAction
{
    public function execute(Address $address): void { $address->delete(); }
}
```

- [ ] **Step 4: Create the FormRequests**

Create `app/Http/Requests/User/UpdateUserRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->isAdmin(); }

    public function rules(): array
    {
        $target = $this->route('user');
        return [
            'name'     => ['sometimes', 'string', 'max:120'],
            'phone'    => ['sometimes', 'nullable', 'string', 'max:32'],
            'email'    => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($target?->id, 'id')],
            'role'     => ['sometimes', 'in:admin,designer,printer_provider,customer'],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
        ];
    }
}
```

Create `app/Http/Requests/Designer/StoreDesignerProfileRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Designer;

use Illuminate\Foundation\Http\FormRequest;

class StoreDesignerProfileRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->isDesigner(); }

    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:120'],
            'bio'          => ['nullable', 'string', 'max:2048'],
            'avatar_url'   => ['nullable', 'url', 'max:512'],
            'social_links' => ['nullable', 'array'],
            'social_links.*' => ['nullable', 'url'],
        ];
    }
}
```

(Repeat for `UpdateDesignerProfileRequest` with `sometimes` instead of `required`.)

Create `app/Http/Requests/Printer/StorePrinterProfileRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Printer;

use Illuminate\Foundation\Http\FormRequest;

class StorePrinterProfileRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->isPrinterProvider(); }

    public function rules(): array
    {
        return [
            'company_name'    => ['required', 'string', 'max:160'],
            'bio'             => ['nullable', 'string', 'max:2048'],
            'capabilities'    => ['nullable', 'array'],
            'capabilities.*'  => ['string', 'max:80'],
            'coverage_zones'  => ['nullable', 'array'],
            'coverage_zones.*'=> ['string', 'max:8'],
        ];
    }
}
```

(Repeat for `UpdatePrinterProfileRequest` with `sometimes`.)

Create `app/Http/Requests/Address/StoreAddressRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'label'       => ['nullable', 'string', 'max:60'],
            'line1'       => ['required', 'string', 'max:200'],
            'line2'       => ['nullable', 'string', 'max:200'],
            'city'        => ['required', 'string', 'max:120'],
            'state'       => ['nullable', 'string', 'max:120'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country'     => ['required', 'string', 'size:2'],  // ISO-3166 alpha-2
            'phone'       => ['nullable', 'string', 'max:32'],
            'is_default'  => ['nullable', 'boolean'],
        ];
    }
}
```

(Repeat for `UpdateAddressRequest` with all `sometimes`.)

- [ ] **Step 5: Create the four controllers**

Create `app/Http/Controllers/Api/UserController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Admin\DeleteUserAction;
use App\Actions\Admin\RestoreUserAction;
use App\Actions\Admin\UpdateUserAction;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private readonly UpdateUserAction $update,
        private readonly DeleteUserAction $delete,
        private readonly RestoreUserAction $restore,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $query = User::query()->with(['designerProfile', 'printerProfile']);
        if ($role = $request->string('role')->value()) {
            $query->where('role', $role);
        }
        if ($search = $request->string('search')->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return response()->json($this->paginated($query));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);
        $updated = $this->update->execute($user, $request->validated());
        return response()->json(['data' => new UserResource($updated)]);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);
        $this->delete->execute($user);
        return response()->json(null, 204);
    }

    public function restore(User $user): JsonResponse
    {
        $this->authorize('restore', $user);
        $restored = $this->restore->execute($user);
        return response()->json(['data' => new UserResource($restored)]);
    }
}
```

Create `app/Http/Controllers/Api/DesignerController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Designer\DeleteDesignerProfileAction;
use App\Actions\Designer\StoreDesignerProfileAction;
use App\Actions\Designer\UpdateDesignerProfileAction;
use App\Http\Requests\Designer\StoreDesignerProfileRequest;
use App\Http\Requests\Designer\UpdateDesignerProfileRequest;
use App\Http\Resources\DesignerProfileResource;
use App\Models\DesignerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DesignerController extends Controller
{
    public function __construct(
        private readonly StoreDesignerProfileAction $store,
        private readonly UpdateDesignerProfileAction $update,
        private readonly DeleteDesignerProfileAction $delete,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = DesignerProfile::query()->with(['user', 'designs']);

        if ($search = $request->string('search')->value()) {
            $query->where('display_name', 'like', "%{$search}%");
        }

        return response()->json($this->paginated($query));
    }

    public function show(DesignerProfile $designer): JsonResponse
    {
        return response()->json(['data' => new DesignerProfileResource($designer->load(['user', 'designs']))]);
    }

    public function store(StoreDesignerProfileRequest $request): JsonResponse
    {
        $profile = $this->store->execute($request->user(), $request->validated());
        return response()->json(['data' => new DesignerProfileResource($profile)], 201);
    }

    public function updateMe(UpdateDesignerProfileRequest $request): JsonResponse
    {
        $profile = $request->user()->designerProfile ?? abort(404, 'Designer profile not found.');
        $this->authorize('update', $profile);
        $updated = $this->update->execute($profile, $request->validated());
        return response()->json(['data' => new DesignerProfileResource($updated)]);
    }

    public function destroyMe(Request $request): JsonResponse
    {
        $profile = $request->user()->designerProfile ?? abort(404, 'Designer profile not found.');
        $this->authorize('delete', $profile);
        $this->delete->execute($profile);
        return response()->json(null, 204);
    }
}
```

(`PrinterController` mirrors `DesignerController` 1:1.)

Create `app/Http/Controllers/Api/AddressController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Address\DeleteAddressAction;
use App\Actions\Address\StoreAddressAction;
use App\Actions\Address\UpdateAddressAction;
use App\Http\Requests\Address\StoreAddressRequest;
use App\Http\Requests\Address\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use Illuminate\Http\JsonResponse;

class AddressController extends Controller
{
    public function __construct(
        private readonly StoreAddressAction $store,
        private readonly UpdateAddressAction $update,
        private readonly DeleteAddressAction $delete,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Address::class);

        return response()->json(
            $this->paginated(auth()->user()->addresses()->orderByDesc('is_default')->orderBy('id'))
        );
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $address = $this->store->execute($request->user(), $request->validated());
        return response()->json(['data' => new AddressResource($address)], 201);
    }

    public function show(Address $address): JsonResponse
    {
        $this->authorize('view', $address);
        return response()->json(['data' => new AddressResource($address)]);
    }

    public function update(UpdateAddressRequest $request, Address $address): JsonResponse
    {
        $this->authorize('update', $address);
        $updated = $this->update->execute($address, $request->validated());
        return response()->json(['data' => new AddressResource($updated)]);
    }

    public function destroy(Address $address): JsonResponse
    {
        $this->authorize('delete', $address);
        $this->delete->execute($address);
        return response()->json(null, 204);
    }
}
```

- [ ] **Step 6: Mount the routes**

Append to `routes/api.php`:

```php
use App\Http\Controllers\Api\{UserController, DesignerController, PrinterController, AddressController};

// Admin user management
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin/users')->group(function (): void {
    Route::get('/',          [UserController::class, 'index'])->name('admin.users.index');
    Route::patch('/{user}',  [UserController::class, 'update'])->name('admin.users.update');
    Route::delete('/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
    Route::post('/{user}/restore', [UserController::class, 'restore'])->name('admin.users.restore');
});

// Designer profile (own + admin)
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/designers',                        [DesignerController::class, 'index'])->name('designers.index');
    Route::get('/designers/{designer}',             [DesignerController::class, 'show'])->name('designers.show');
    Route::post('/me/designer-profile',             [DesignerController::class, 'store'])->name('me.designer-profile.store');
    Route::patch('/me/designer-profile',            [DesignerController::class, 'updateMe'])->name('me.designer-profile.update');
    Route::delete('/me/designer-profile',           [DesignerController::class, 'destroyMe'])->name('me.designer-profile.destroy');

    Route::get('/printers',                         [PrinterController::class, 'index'])->name('printers.index');
    Route::get('/printers/{printer}',               [PrinterController::class, 'show'])->name('printers.show');
    Route::post('/me/printer-profile',              [PrinterController::class, 'store'])->name('me.printer-profile.store');
    Route::patch('/me/printer-profile',             [PrinterController::class, 'updateMe'])->name('me.printer-profile.update');
    Route::delete('/me/printer-profile',            [PrinterController::class, 'destroyMe'])->name('me.printer-profile.destroy');

    Route::get('/me/addresses',          [AddressController::class, 'index'])->name('me.addresses.index');
    Route::post('/me/addresses',         [AddressController::class, 'store'])->name('me.addresses.store');
    Route::get('/me/addresses/{address}',[AddressController::class, 'show'])->name('me.addresses.show');
    Route::patch('/me/addresses/{address}',[AddressController::class, 'update'])->name('me.addresses.update');
    Route::delete('/me/addresses/{address}',[AddressController::class, 'destroy'])->name('me.addresses.destroy');
});
```

- [ ] **Step 7: Write the 12 feature tests**

Create the test files following the same pattern as Task 7. Examples:

`tests/Feature/Api/Users/IndexTest.php`:

```php
public function test_admin_can_list_users_filtered_by_role(): void
{
    User::factory()->count(3)->create(['role' => 'customer']);
    User::factory()->count(2)->create(['role' => 'designer']);
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/admin/users?role=designer')
        ->assertOk()
        ->assertJsonCount(2, 'data');
}
```

(Repeat for `UpdateTest`, `DeleteTest`, `RestoreTest` — happy + sad paths.)

Designer tests:
- `index` returns paginated list
- `show` returns profile with user + design count
- `store` requires designer role + validation
- `updateMe` enforces ownership (403 for other user)
- `destroyMe` returns 409 when designs exist

Printer tests mirror designer tests.

Address tests:
- Anonymous rejected
- User can CRUD own addresses
- Other user's address returns 403 on show/update/delete
- `is_default=true` clears other defaults

- [ ] **Step 8: Run the tests**

```bash
php artisan test --compact tests/Feature/Api/Users tests/Feature/Api/Designers tests/Feature/Api/Printers tests/Feature/Api/Addresses
```

Expected: all green (~30+ tests).

- [ ] **Step 9: Commit**

```bash
git add app/Http/Controllers/Api/{User,Designer,Printer,Address}Controller.php \
  app/Policies/{User,DesignerProfile,PrinterProfile,Address}Policy.php \
  app/Actions/Admin app/Actions/Designer app/Actions/Printer app/Actions/Address \
  app/Http/Requests/User app/Http/Requests/Designer app/Http/Requests/Printer app/Http/Requests/Address \
  app/Providers/AppServiceProvider.php \
  tests/Feature/Api/Users tests/Feature/Api/Designers tests/Feature/Api/Printers tests/Feature/Api/Addresses \
  routes/api.php
git commit -m "feat(api): users + designers + printers + addresses controllers, policies, and tests"
```

---

## Task 9: Catalog — Categories, Tags, Designs, Templates, Variants, Mappings

**Files:**
- Create: `app/Http/Controllers/Api/{Category,Tag,Design,ProductTemplate,ProductVariant,DesignProductMapping}Controller.php`
- Create: `app/Policies/{Category,Tag,Design,ProductTemplate,ProductVariant,DesignProductMapping}Policy.php`
- Create: `app/Actions/Catalog/*` (≈ 14 action classes)
- Create: `app/Http/Requests/Catalog/*` (≈ 12 FormRequests)
- Test: `tests/Feature/Api/Catalog/*Test.php` (≈ 14 test files)

This is the largest single task in Phase 2. The shape is identical to Task 8 — the only novelty is that catalog **reads** are public (`auth:sanctum` is **not** applied to `GET` routes) while writes are admin/owner-gated.

- [ ] **Step 1: Boot the catalog task structure with one fully-built example (CategoryController)**

Create `app/Policies/CategoryPolicy.php`:

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CategoryPolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool { return $user->isAdmin() ? true : null; }
    public function viewAny(?User $user): bool { return true; }
    public function view(?User $user, Category $category): bool { return true; }
    public function create(User $user): bool { return $user->isAdmin(); }
    public function update(User $user, Category $category): bool { return false; }
    public function delete(User $user, Category $category): bool
    {
        return $category->designs()->doesntExist();
    }
}
```

Create `app/Http/Requests/Catalog/StoreCategoryRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->isAdmin() ?? false; }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:120'],
            'slug'      => ['required', 'string', 'max:140', 'unique:categories,slug'],
            'parent_id' => ['nullable', 'string', 'exists:categories,id'],
        ];
    }
}
```

Create `app/Actions/Catalog/{StoreCategory,UpdateCategory,DeleteCategory}Action.php` (each takes validated data + returns model, or throws 409 if designs attached).

Create `app/Http/Controllers/Api/CategoryController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Catalog\{DeleteCategoryAction, StoreCategoryAction, UpdateCategoryAction};
use App\Http\Requests\Catalog\{StoreCategoryRequest, UpdateCategoryRequest};
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(
        private readonly StoreCategoryAction $store,
        private readonly UpdateCategoryAction $update,
        private readonly DeleteCategoryAction $delete,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Category::query()->withCount('designs');
        if ($parent = $request->string('parent')->value()) {
            $query->where('parent_id', $parent);
        }
        return response()->json($this->paginated($query));
    }

    public function show(Category $category): JsonResponse
    {
        return response()->json([
            'data' => new CategoryResource($category->load(['parent', 'children'])->loadCount('designs')),
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->store->execute($request->validated());
        return response()->json(['data' => new CategoryResource($category)], 201);
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $this->authorize('update', $category);
        $updated = $this->update->execute($category, $request->validated());
        return response()->json(['data' => new CategoryResource($updated)]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->authorize('delete', $category);
        $this->delete->execute($category);
        return response()->json(null, 204);
    }
}
```

- [ ] **Step 2: Build the TagController (mirrors Category, simpler)**

Tag has no parent/child. Admin-only writes. Public reads.

- [ ] **Step 3: Build the DesignController + admin transfer**

Designs are owned by designers. Reads are public if `status='published'`, owner/admin if other statuses.

Create `app/Policies/DesignPolicy.php`:

```php
public function before(User $user): ?bool { return $user->isAdmin() ? true : null; }

public function viewAny(?User $user): bool { return true; }

public function view(?User $user, Design $design): bool
{
    return $design->status === 'published' || $design->designer_id === $user?->id;
}

public function create(User $user): bool { return $user->isDesigner(); }
public function update(User $user, Design $design): bool { return $design->designer_id === $user->id; }
public function delete(User $user, Design $design): bool { return $design->designer_id === $user->id; }
public function transfer(User $user, Design $design): bool { return $user->isAdmin(); }
```

Create `app/Actions/Catalog/TransferDesignAction.php`:

```php
namespace App\Actions\Catalog;

use App\Models\Design;
use Illuminate\Support\Facades\Log;

class TransferDesignAction
{
    public function execute(Design $design, string $newDesignerId): Design
    {
        $oldDesigner = $design->designer_id;
        $design->designer_id = $newDesignerId;
        $design->save();
        Log::channel('audit')->warning('design.transferred', [
            'design_id'       => $design->id,
            'old_designer_id' => $oldDesigner,
            'new_designer_id' => $newDesignerId,
            'by_user_id'      => auth()->id(),
        ]);
        return $design->refresh();
    }
}
```

(Add the `audit` channel in `config/logging.php` if not already there — a single `single` driver writing to `storage/logs/audit.log` is sufficient.)

Create `app/Http/Controllers/Api/DesignController.php` mirroring the CategoryController pattern, with:
- `index` (filters: `designer_id`, `category_id`, `tag`, `status` (public→forces `published`), `search`)
- `show` (calls policy view)
- `store` / `update` / `destroy` (designer own)
- `transfer` (admin only)

Create `app/Http/Requests/Catalog/{Store,Update}DesignRequest.php` with rules:

```php
// StoreDesignRequest
'category_id' => ['nullable', 'string', 'exists:categories,id'],
'title'       => ['required', 'string', 'max:160'],
'description' => ['nullable', 'string', 'max:4096'],
'status'      => ['nullable', 'in:draft,in_review,published,rejected'],
'base_price'  => ['required', 'numeric', 'min:0'],
'tag_ids'     => ['nullable', 'array'],
'tag_ids.*'   => ['string', 'exists:tags,id'],
'media'       => ['nullable', 'array'],
'media.*'     => ['file', 'mimes:jpg,jpeg,png,webp,svg', 'max:10240'],  // 10MB max
```

- [ ] **Step 4: Build the ProductTemplate + ProductVariant controllers**

Templates are owned by printers. Public reads if `is_active`. Variants are nested under templates.

Create the controllers mirroring `DesignController`/`CategoryController`.

`ProductTemplateController` actions:
- `index` (filters: `printer_provider_id`, `type`, `is_active`)
- `show`
- `store` (printer, own)
- `update` (printer, own)
- `destroy` (printer, own; 409 if order_items reference)

`ProductVariantController`:
- Nested routes under `/api/me/templates/{template}/variants`
- `index` (returns variants for template)
- `store` / `update` / `destroy` (printer own)

Create the policies + FormRequests + Actions following the same pattern.

- [ ] **Step 5: Build the DesignProductMapping controller**

Mappings link a design to a template. Owned by designer.

Create `app/Http/Controllers/Api/DesignProductMappingController.php` with:
- `index` (filters: `design_id`, `product_template_id`, `is_active`, `printer_id`)
- `store` (designer, own design)
- `update` (designer, own mapping; only `final_price`, `preferred_printer_id`)
- `destroy` (designer, own; 409 if order_items reference)

- [ ] **Step 6: Mount the catalog routes**

Append to `routes/api.php`:

```php
use App\Http\Controllers\Api\{
    CategoryController, TagController, DesignController,
    ProductTemplateController, ProductVariantController, DesignProductMappingController
};

// Public catalog reads
Route::get('/categories',               [CategoryController::class, 'index']);
Route::get('/categories/{category}',    [CategoryController::class, 'show']);
Route::get('/tags',                     [TagController::class, 'index']);
Route::get('/tags/{tag}',               [TagController::class, 'show']);
Route::get('/designs',                  [DesignController::class, 'index']);
Route::get('/designs/{design}',         [DesignController::class, 'show']);
Route::get('/templates',                [ProductTemplateController::class, 'index']);
Route::get('/templates/{template}',     [ProductTemplateController::class, 'show']);
Route::get('/templates/{template}/variants', [ProductVariantController::class, 'index']);
Route::get('/mappings',                 [DesignProductMappingController::class, 'index']);
Route::get('/mappings/{mapping}',       [DesignProductMappingController::class, 'show']);

// Admin writes for catalog foundations
Route::middleware(['auth:sanctum', 'role:admin'])->group(function (): void {
    Route::post('/admin/categories',           [CategoryController::class, 'store']);
    Route::patch('/admin/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/admin/categories/{category}',[CategoryController::class, 'destroy']);
    Route::post('/admin/tags',                 [TagController::class, 'store']);
    Route::patch('/admin/tags/{tag}',          [TagController::class, 'update']);
    Route::delete('/admin/tags/{tag}',         [TagController::class, 'destroy']);
});

// Designer + admin writes
Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/me/designs',                  [DesignController::class, 'store']);
    Route::patch('/me/designs/{design}',        [DesignController::class, 'updateMe']);
    Route::delete('/me/designs/{design}',       [DesignController::class, 'destroyMe']);
});

// Admin-only design transfer
Route::middleware(['auth:sanctum', 'role:admin'])->post(
    '/admin/designs/{design}/transfer',
    [DesignController::class, 'transfer']
);

// Printer + admin templates
Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/me/templates',                              [ProductTemplateController::class, 'store']);
    Route::patch('/me/templates/{template}',                  [ProductTemplateController::class, 'updateMe']);
    Route::delete('/me/templates/{template}',                 [ProductTemplateController::class, 'destroyMe']);
    Route::post('/me/templates/{template}/variants',          [ProductVariantController::class, 'store']);
    Route::patch('/me/variants/{variant}',                    [ProductVariantController::class, 'update']);
    Route::delete('/me/variants/{variant}',                   [ProductVariantController::class, 'destroy']);

    Route::post('/me/mappings',                  [DesignProductMappingController::class, 'store']);
    Route::patch('/me/mappings/{mapping}',       [DesignProductMappingController::class, 'update']);
    Route::delete('/me/mappings/{mapping}',      [DesignProductMappingController::class, 'destroy']);
});
```

- [ ] **Step 7: Write the catalog feature tests**

For each controller write `IndexTest`, `StoreTest`, `UpdateTest`, `DeleteTest`. Cover:
- Anonymous user can `GET` published designs / active templates
- Designer can CRUD own designs; cannot edit other's (403)
- Admin can transfer a design to another designer
- 409 on delete-template that has order_items
- Tags CRUD is admin-only (403 for designer)
- Mappings are unique on `(design_id, product_template_id)` — second insert returns 409

Test files live in:
```
tests/Feature/Api/Catalog/Categories/{Index,Store,Update,Destroy}Test.php
tests/Feature/Api/Catalog/Tags/{...}Test.php
tests/Feature/Api/Catalog/Designs/{...}Test.php
tests/Feature/Api/Catalog/Templates/{...}Test.php
tests/Feature/Api/Catalog/Variants/{...}Test.php
tests/Feature/Api/Catalog/Mappings/{...}Test.php
```

- [ ] **Step 8: Run the catalog tests**

```bash
php artisan test --compact tests/Feature/Api/Catalog
```

Expected: ~80+ tests pass.

- [ ] **Step 9: Commit**

```bash
git add app/Http/Controllers/Api/{Category,Tag,Design,ProductTemplate,ProductVariant,DesignProductMapping}Controller.php \
  app/Policies/{Category,Tag,Design,ProductTemplate,ProductVariant,DesignProductMapping}Policy.php \
  app/Actions/Catalog \
  app/Http/Requests/Catalog \
  tests/Feature/Api/Catalog \
  routes/api.php
git commit -m "feat(api): catalog controllers (categories, tags, designs, templates, variants, mappings)"
```

---

## Task 10: Cart controller — pricing, upsert, line totals

**Files:**
- Create: `app/Http/Controllers/Api/CartController.php`
- Create: `app/Services/CartPricingService.php`
- Create: `app/Actions/Cart/{UpsertCartItem,UpdateCartItemQuantity,DeleteCartItem,ClearCart}Action.php`
- Create: `app/Http/Requests/Cart/{Store,Update}CartItemRequest.php`
- Test: `tests/Feature/Api/Cart/*Test.php`

- [ ] **Step 1: Build CartPricingService**

Create `app/Services/CartPricingService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CartItem;
use App\Models\DesignProductMapping;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

class CartPricingService
{
    /**
     * Recompute `variant_key` (STORED generated column seed) + `line_total` for one cart item.
     * The DB also computes `variant_key` from `product_variant_id` via a generated column,
     * but we set it explicitly so the application logic and DB stay in lockstep.
     */
    public function priceItem(DesignProductMapping $mapping, ?ProductVariant $variant, int $quantity): array
    {
        $unit = (float) $mapping->final_price + (float) ($variant->price_delta ?? 0);
        $line = round($unit * $quantity, 2);

        return [
            'unit_price' => $unit,
            'line_total' => $line,
            'variant_key' => $variant?->sku ?? 'no-variant',
        ];
    }

    /** @param Collection<int,CartItem> $items */
    public function grandTotal(Collection $items): float
    {
        return round($items->sum('line_total'), 2);
    }
}
```

- [ ] **Step 2: Create the four cart actions**

```php
// app/Actions/Cart/UpsertCartItemAction.php
namespace App\Actions\Cart;

use App\Models\CartItem;
use App\Models\DesignProductMapping;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CartPricingService;

class UpsertCartItemAction
{
    public function __construct(private readonly CartPricingService $pricing) {}

    public function execute(User $user, string $mappingId, ?string $variantId, int $quantity): CartItem
    {
        $mapping = DesignProductMapping::findOrFail($mappingId);
        abort_if(! $mapping->is_active, 409, 'Mapping is no longer active.');

        $variant = $variantId ? ProductVariant::findOrFail($variantId) : null;
        abort_if($variant && ! $variant->is_active, 409, 'Variant is no longer active.');

        $priced = $this->pricing->priceItem($mapping, $variant, $quantity);

        return CartItem::updateOrCreate(
            [
                'user_id'                   => $user->id,
                'design_product_mapping_id' => $mapping->id,
                'product_variant_id'        => $variant?->id,
            ],
            [
                'quantity'    => $quantity,
                'unit_price'  => $priced['unit_price'],
                'line_total'  => $priced['line_total'],
                'variant_key' => $priced['variant_key'],
            ]
        )->refresh();
    }
}

// app/Actions/Cart/UpdateCartItemQuantityAction.php
namespace App\Actions\Cart;

use App\Models\CartItem;
use App\Services\CartPricingService;

class UpdateCartItemQuantityAction
{
    public function __construct(private readonly CartPricingService $pricing) {}

    public function execute(CartItem $item, int $quantity): CartItem
    {
        abort_unless($quantity >= 1 && $quantity <= 100, 422, 'Quantity must be between 1 and 100.');
        $priced = $this->pricing->priceItem($item->mapping, $item->variant, $quantity);
        $item->update([
            'quantity'   => $quantity,
            'unit_price' => $priced['unit_price'],
            'line_total' => $priced['line_total'],
        ]);
        return $item->refresh();
    }
}

// app/Actions/Cart/DeleteCartItemAction.php
namespace App\Actions\Cart;

use App\Models\CartItem;

class DeleteCartItemAction
{
    public function execute(CartItem $item): void { $item->delete(); }
}

// app/Actions/Cart/ClearCartAction.php
namespace App\Actions\Cart;

use App\Models\User;

class ClearCartAction
{
    public function execute(User $user): void { $user->cartItems()->delete(); }
}
```

- [ ] **Step 3: Create the FormRequests**

```php
// app/Http/Requests/Cart/StoreCartItemRequest.php
namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

class StoreCartItemRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'design_product_mapping_id' => ['required', 'string', 'exists:design_product_mappings,id'],
            'product_variant_id'        => ['nullable', 'string', 'exists:product_variants,id'],
            'quantity'                  => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }
}

// app/Http/Requests/Cart/UpdateCartItemRequest.php
namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->id === $this->route('item')->user_id; }

    public function rules(): array
    {
        return [['quantity' => ['required', 'integer', 'min:1', 'max:100']]];
    }
}
```

- [ ] **Step 4: Create the CartController**

Create `app/Http/Controllers/Api/CartController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Cart\{ClearCartAction, DeleteCartItemAction, UpdateCartItemQuantityAction, UpsertCartItemAction};
use App\Http\Requests\Cart\{StoreCartItemRequest, UpdateCartItemRequest};
use App\Http\Resources\CartItemResource;
use App\Models\CartItem;
use App\Services\CartPricingService;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    public function __construct(
        private readonly UpsertCartItemAction $upsert,
        private readonly UpdateCartItemQuantityAction $updateQty,
        private readonly DeleteCartItemAction $delete,
        private readonly ClearCartAction $clear,
        private readonly CartPricingService $pricing,
    ) {}

    public function index(): JsonResponse
    {
        $items = auth()->user()->cartItems()->with(['mapping', 'variant'])->get();

        return response()->json([
            'data'        => CartItemResource::collection($items),
            'grand_total' => $this->pricing->grandTotal($items),
        ]);
    }

    public function store(StoreCartItemRequest $request): JsonResponse
    {
        $item = $this->upsert->execute(
            user: $request->user(),
            mappingId: $request->string('design_product_mapping_id')->value(),
            variantId: $request->string('product_variant_id')->value() ?: null,
            quantity: $request->integer('quantity'),
        );

        return response()->json(['data' => new CartItemResource($item->load(['mapping', 'variant']))], 201);
    }

    public function update(UpdateCartItemRequest $request, CartItem $item): JsonResponse
    {
        $updated = $this->updateQty->execute($item, $request->integer('quantity'));
        return response()->json(['data' => new CartItemResource($updated)]);
    }

    public function destroy(CartItem $item): JsonResponse
    {
        abort_unless($item->user_id === auth()->id(), 403);
        $this->delete->execute($item);
        return response()->json(null, 204);
    }

    public function clear(): JsonResponse
    {
        $this->clear->execute(auth()->user());
        return response()->json(null, 204);
    }
}
```

- [ ] **Step 5: Mount the cart routes**

```php
use App\Http\Controllers\Api\CartController;

Route::middleware('auth:sanctum')->prefix('me/cart')->group(function (): void {
    Route::get('/',          [CartController::class, 'index'])->name('me.cart.index');
    Route::post('/items',    [CartController::class, 'store'])->name('me.cart.items.store');
    Route::patch('/items/{item}', [CartController::class, 'update'])->name('me.cart.items.update');
    Route::delete('/items/{item}', [CartController::class, 'destroy'])->name('me.cart.items.destroy');
    Route::delete('/',       [CartController::class, 'clear'])->name('me.cart.clear');
});
```

- [ ] **Step 6: Write the cart feature tests**

`tests/Feature/Api/Cart/StoreTest.php`:

```php
public function test_user_can_add_item_to_cart(): void
{
    $user = User::factory()->create();
    $mapping = DesignProductMapping::factory()->create(['final_price' => 25.00]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/me/cart/items', [
            'design_product_mapping_id' => $mapping->id,
            'quantity'                  => 2,
        ])
        ->assertCreated()
        ->assertJsonPath('data.line_total', 50.00);
}

public function test_user_cannot_add_inactive_mapping(): void
{
    $user = User::factory()->create();
    $mapping = DesignProductMapping::factory()->create(['is_active' => false]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/me/cart/items', [
            'design_product_mapping_id' => $mapping->id,
            'quantity'                  => 1,
        ])
        ->assertStatus(409);
}

public function test_user_cannot_edit_other_users_cart_item(): void
{
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $item = CartItem::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($other, 'sanctum')
        ->patchJson("/api/me/cart/items/{$item->id}", ['quantity' => 5])
        ->assertForbidden();
}
```

(Add `QuantityValidationTest`, `ClearTest`, `VariantKeyRecomputeTest` following the same shape.)

- [ ] **Step 7: Run the cart tests**

```bash
php artisan test --compact tests/Feature/Api/Cart
```

Expected: 6+ tests pass.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/Api/CartController.php \
  app/Services/CartPricingService.php app/Actions/Cart \
  app/Http/Requests/Cart \
  tests/Feature/Api/Cart routes/api.php
git commit -m "feat(api): cart endpoints with pricing, upsert, and ownership checks"
```

---

## Task 11: Orders — checkout, status transitions, soft delete + restore

**Files:**
- Create: `app/Http/Controllers/Api/OrderController.php`
- Create: `app/Http/Controllers/Api/OrderItemController.php` (admin printer reassignment + status transitions)
- Create: `app/Services/OrderPlacementService.php`
- Create: `app/Events/OrderPlaced.php`
- Create: `app/Actions/Order/{PlaceOrderFromCart,CancelOrder,UpdateOrderItemStatus,ReassignPrinter,RestoreOrder,DeleteOrder}Action.php`
- Create: `app/Http/Requests/Order/{Store,Cancel,UpdateStatus,ReassignPrinter}OrderRequest.php`
- Create: `app/Policies/OrderPolicy.php`
- Create: `app/Policies/OrderItemPolicy.php`
- Create: `app/Notifications/OrderPlacedNotification.php`
- Create: `app/Listeners/OrderPlaced/CreatePaymentRecord.php`
- Test: `tests/Feature/Api/Orders/*Test.php`

- [ ] **Step 1: Create the OrderPlaced event + notification**

Create `app/Events/OrderPlaced.php`:

```php
<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderPlaced
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Order $order) {}
}
```

Create `app/Notifications/OrderPlacedNotification.php`:

```php
<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderPlacedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Order $order) {}

    /** @return array<int,string> */
    public function via(mixed $notifiable): array
    {
        return ['database', 'mail'];
    }

    /** @return array<string,mixed> */
    public function toArray(mixed $notifiable): array
    {
        return [
            'event'         => 'order.placed',
            'order_id'      => $this->order->id,
            'order_number'  => $this->order->order_number,
            'total_amount'  => $this->order->total_amount,
        ];
    }
}
```

- [ ] **Step 2: Create the 6 order Actions**

```php
// app/Actions/Order/PlaceOrderFromCartAction.php
namespace App\Actions\Order;

use App\Events\OrderPlaced;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Notifications\OrderPlacedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlaceOrderFromCartAction
{
    public function execute(User $user, string $shippingAddressId, string $billingAddressId): Order
    {
        return DB::transaction(function () use ($user, $shippingAddressId, $billingAddressId) {
            $items = $user->cartItems()->with(['mapping', 'variant'])->get();
            abort_if($items->isEmpty(), 422, 'Cart is empty.');

            $shipping = Address::where('user_id', $user->id)->findOrFail($shippingAddressId);
            $billing  = Address::where('user_id', $user->id)->findOrFail($billingAddressId);

            $subtotal = round($items->sum('line_total'), 2);
            $shippingTotal = 0.00;  // flat-rate v1.2 — replace with table lookup in Phase 4
            $taxTotal      = round($subtotal * 0.0, 2);  // no tax in MVP
            $total         = round($subtotal + $shippingTotal + $taxTotal, 2);

            $order = Order::create([
                'order_number'        => 'POD-' . strtoupper(Str::random(10)),
                'user_id'             => $user->id,
                'status'              => 'pending',
                'subtotal'            => $subtotal,
                'shipping_total'      => $shippingTotal,
                'tax_total'           => $taxTotal,
                'total_amount'        => $total,
                'currency'            => 'USD',
                'shipping_address_id' => $shipping->id,
                'billing_address_id'  => $billing->id,
                'placed_at'           => now(),
            ]);

            foreach ($items as $cartItem) {
                OrderItem::create([
                    'order_id'                  => $order->id,
                    'design_product_mapping_id' => $cartItem->design_product_mapping_id,
                    'product_variant_id'        => $cartItem->product_variant_id,
                    'printer_provider_id'       => $cartItem->mapping->template->printer_provider_id,
                    'design_title_snapshot'     => $cartItem->mapping->design->title,
                    'variant_snapshot'          => $cartItem->variant?->attributes,
                    'unit_price'                => $cartItem->unit_price,
                    'quantity'                  => $cartItem->quantity,
                    'line_total'                => $cartItem->line_total,
                    'status'                    => 'pending',
                ]);
            }

            $user->cartItems()->delete();  // clear cart on success

            OrderPlaced::dispatch($order);
            $user->notify(new OrderPlacedNotification($order));

            return $order->refresh()->load(['items', 'shippingAddress', 'billingAddress']);
        });
    }
}

// app/Actions/Order/CancelOrderAction.php
namespace App\Actions\Order;

use App\Models\Order;

class CancelOrderAction
{
    public function execute(Order $order): Order
    {
        abort_if($order->status === 'cancelled', 409, 'Order is already cancelled.');
        abort_if($order->status === 'delivered', 409, 'Delivered orders cannot be cancelled.');
        $order->status = 'cancelled';
        $order->save();
        return $order->refresh();
    }
}

// app/Actions/Order/UpdateOrderItemStatusAction.php
namespace App\Actions\Order;

use App\Models\OrderItem;

class UpdateOrderItemStatusAction
{
    private const TRANSITIONS = [
        'pending'    => ['accepted', 'rejected'],
        'accepted'   => ['in_production', 'rejected'],
        'in_production' => ['handed_off'],
        'handed_off' => ['shipped', 'delivered'],
        'shipped'    => ['delivered'],
        'rejected'   => [],
    ];

    public function execute(OrderItem $item, string $newStatus): OrderItem
    {
        $allowed = self::TRANSITIONS[$item->status] ?? [];
        abort_unless(in_array($newStatus, $allowed, true), 409,
            "Cannot transition from '{$item->status}' to '{$newStatus}'.");

        $item->status = $newStatus;
        $item->save();
        return $item->refresh();
    }
}

// app/Actions/Order/ReassignPrinterAction.php
namespace App\Actions\Order;

use App\Models\OrderItem;

class ReassignPrinterAction
{
    public function execute(OrderItem $item, string $newPrinterId): OrderItem
    {
        abort_unless($item->status === 'pending', 409,
            'Only pending order items can be reassigned.');
        $item->printer_provider_id = $newPrinterId;
        $item->save();
        return $item->refresh();
    }
}

// app/Actions/Order/RestoreOrderAction.php
namespace App\Actions\Order;

use App\Models\Order;

class RestoreOrderAction
{
    public function execute(Order $order): Order
    {
        $order->restore();
        return $order->refresh();
    }
}

// app/Actions/Order/DeleteOrderAction.php
namespace App\Actions\Order;

use App\Models\Order;

class DeleteOrderAction
{
    public function execute(Order $order): void { $order->delete(); }
}
```

- [ ] **Step 3: Create the policies + FormRequests**

Create `app/Policies/OrderPolicy.php`:

```php
public function before(User $user): ?bool { return $user->isAdmin() ? true : null; }
public function viewAny(User $user): bool { return $user->isAdmin(); }
public function view(User $user, Order $order): bool { return $order->user_id === $user->id; }
public function create(User $user): bool { return true; }
public function cancel(User $user, Order $order): bool { return $order->user_id === $user->id; }
public function delete(User $user, Order $order): bool { return $user->isAdmin(); }
public function restore(User $user): bool { return $user->isAdmin(); }
```

Create `app/Policies/OrderItemPolicy.php`:

```php
public function before(User $user): ?bool { return $user->isAdmin() ? true : null; }
public function view(User $user, OrderItem $item): bool
{
    if ($user->isPrinterProvider()) {
        return $item->printer_provider_id === $user->id;
    }
    return $item->order->user_id === $user->id;
}
public function update(User $user, OrderItem $item): bool
{
    return $user->isPrinterProvider() && $item->printer_provider_id === $user->id;
}
public function reassign(User $user, OrderItem $item): bool { return $user->isAdmin(); }
```

FormRequests:

```php
// app/Http/Requests/Order/StoreOrderRequest.php
public function rules(): array
{
    return [
        'shipping_address_id' => ['required', 'string', 'exists:addresses,id'],
        'billing_address_id'  => ['required', 'string', 'exists:addresses,id'],
    ];
}

// app/Http/Requests/Order/CancelOrderRequest.php  (empty ruleset, just an authorize token)

// app/Http/Requests/Order/UpdateStatusRequest.php
public function rules(): array
{
    return [['status' => ['required', 'in:accepted,rejected,in_production,handed_off,shipped,delivered']]];
}

// app/Http/Requests/Order/ReassignPrinterRequest.php
public function rules(): array
{
    return [['printer_provider_id' => ['required', 'string', 'exists:users,id']]];
}
```

- [ ] **Step 4: Create the OrderController + OrderItemController**

Create `app/Http/Controllers/Api/OrderController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Order\{CancelOrderAction, DeleteOrderAction, PlaceOrderFromCartAction, RestoreOrderAction};
use App\Http\Requests\Order\{CancelOrderRequest, StoreOrderRequest};
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly PlaceOrderFromCartAction $place,
        private readonly CancelOrderAction $cancel,
        private readonly DeleteOrderAction $delete,
        private readonly RestoreOrderAction $restore,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::query()->with(['items', 'shippingAddress', 'billingAddress', 'payment']);
        if ($status = $request->string('status')->value()) {
            $query->where('status', $status);
        }
        return response()->json($this->paginated($query));
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->place->execute(
            user: $request->user(),
            shippingAddressId: $request->string('shipping_address_id')->value(),
            billingAddressId: $request->string('billing_address_id')->value(),
        );
        return response()->json(['data' => new OrderResource($order)], 201);
    }

    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);
        return response()->json(['data' => new OrderResource($order->load(['items', 'payment', 'shipments']))]);
    }

    public function cancel(CancelOrderRequest $request, Order $order): JsonResponse
    {
        $this->authorize('cancel', $order);
        $updated = $this->cancel->execute($order);
        return response()->json(['data' => new OrderResource($updated)]);
    }

    public function destroy(Order $order): JsonResponse
    {
        $this->authorize('delete', $order);
        $this->delete->execute($order);
        return response()->json(null, 204);
    }

    public function restore(Order $order): JsonResponse
    {
        $this->authorize('restore', $order);
        $restored = $this->restore->execute($order);
        return response()->json(['data' => new OrderResource($restored)]);
    }
}
```

Create `app/Http/Controllers/Api/OrderItemController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Order\{ReassignPrinterAction, UpdateOrderItemStatusAction};
use App\Http\Requests\Order\{ReassignPrinterRequest, UpdateStatusRequest};
use App\Http\Resources\OrderItemResource;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;

class OrderItemController extends Controller
{
    public function __construct(
        private readonly UpdateOrderItemStatusAction $updateStatus,
        private readonly ReassignPrinterAction $reassign,
    ) {}

    public function show(OrderItem $item): JsonResponse
    {
        $this->authorize('view', $item);
        return response()->json(['data' => new OrderItemResource($item)]);
    }

    public function updateStatus(UpdateStatusRequest $request, OrderItem $item): JsonResponse
    {
        $this->authorize('update', $item);
        $updated = $this->updateStatus->execute($item, $request->string('status')->value());
        return response()->json(['data' => new OrderItemResource($updated)]);
    }

    public function reassignPrinter(ReassignPrinterRequest $request, OrderItem $item): JsonResponse
    {
        $this->authorize('reassign', $item);
        $updated = $this->reassign->execute($item, $request->string('printer_provider_id')->value());
        return response()->json(['data' => new OrderItemResource($updated)]);
    }
}
```

- [ ] **Step 5: Mount the order routes**

```php
use App\Http\Controllers\Api\{OrderController, OrderItemController};

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/orders',                  [OrderController::class, 'index'])->name('orders.index');  // admin
    Route::post('/orders',                 [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{order}',          [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/cancel',  [OrderController::class, 'cancel'])->name('orders.cancel');
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function (): void {
    Route::delete('/admin/orders/{order}',          [OrderController::class, 'destroy'])->name('admin.orders.destroy');
    Route::post('/admin/orders/{order}/restore',    [OrderController::class, 'restore'])->name('admin.orders.restore');
    Route::patch('/admin/orders/{order}/items/{item}/printer', [OrderItemController::class, 'reassignPrinter'])
        ->name('admin.orders.items.reassign-printer');
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/order-items/{item}',                       [OrderItemController::class, 'show'])->name('order-items.show');
    Route::patch('/order-items/{item}/status',              [OrderItemController::class, 'updateStatus'])->name('order-items.update-status');
});
```

- [ ] **Step 6: Write the order tests**

Cover:
- `orders.store` happy: cart → order with snapshot fields, cart cleared, notification sent
- `orders.store` empty cart → 422
- `orders.cancel` by owner → status `cancelled`; double-cancel → 409
- `orders.cancel` of delivered order → 409
- `orders.show` by non-owner → 403; admin can see any
- `admin.orders.destroy` soft-deletes; `restore` returns it
- `order-items.update-status` valid transition `pending → accepted` → 200; invalid transition → 409
- `admin.orders.items.reassign-printer` only when item is `pending`; otherwise 409

- [ ] **Step 7: Run the order tests**

```bash
php artisan test --compact tests/Feature/Api/Orders
```

Expected: 12+ tests pass.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/Api/{Order,OrderItem}Controller.php \
  app/Services app/Events app/Notifications app/Listeners \
  app/Actions/Order app/Policies/{Order,OrderItem}Policy.php \
  app/Http/Requests/Order \
  tests/Feature/Api/Orders routes/api.php
git commit -m "feat(api): order placement, cancellation, status transitions, and reassignment"
```

---

## Task 12: DeliveryCompanies, Shipments, Payments — admin CRUD + transition triggers

**Files:**
- Create: `app/Http/Controllers/Api/{DeliveryCompanyController,ShipmentController,PaymentController}Controller.php`
- Create: `app/Actions/{Delivery,Shipment,Payment}/*Action.php`
- Create: `app/Policies/{DeliveryCompany,Shipment,Payment}Policy.php`
- Create: `app/Http/Requests/{Delivery,Shipment,Payment}/*Request.php`
- Create: `app/Events/{OrderShipped,OrderDelivered}.php`
- Create: `app/Notifications/{OrderShippedNotification,OrderDeliveredNotification,PaymentConfirmedNotification,OrderCancelledNotification}.php`
- Test: `tests/Feature/Api/{DeliveryCompanies,Shipments,Payments}/*Test.php`

- [ ] **Step 1: Create the 3 DeliveryCompany actions + controller**

```php
// app/Actions/Delivery/{StoreCompany,UpdateCompany,DeleteCompany}Action.php
// (Same shape as the Category actions in Task 9.)
```

`DeliveryCompanyController`:
- `index` (public read) — paginated, optional `?is_active=true`
- `show` (public)
- `store` / `update` / `destroy` — admin only; `destroy` returns 409 if shipments reference

Mount:
```php
Route::get('/delivery-companies', [DeliveryCompanyController::class, 'index']);
Route::get('/delivery-companies/{company}', [DeliveryCompanyController::class, 'show']);
Route::middleware(['auth:sanctum', 'role:admin'])->group(function (): void {
    Route::post('/admin/delivery-companies', [DeliveryCompanyController::class, 'store']);
    Route::patch('/admin/delivery-companies/{company}', [DeliveryCompanyController::class, 'update']);
    Route::delete('/admin/delivery-companies/{company}', [DeliveryCompanyController::class, 'destroy']);
});
```

- [ ] **Step 2: Create the 5 Shipment actions + controller**

```php
// app/Actions/Shipment/CreateShipmentAction.php
namespace App\Actions\Shipment;

use App\Models\OrderItem;
use App\Models\Shipment;
use App\Notifications\OrderShippedNotification;
use Illuminate\Support\Facades\DB;

class CreateShipmentAction
{
    public function execute(OrderItem $item, int $deliveryCompanyId, ?string $trackingNumber): Shipment
    {
        abort_unless($item->status === 'handed_off', 409,
            'Order item must be handed_off before a shipment can be created.');

        return DB::transaction(function () use ($item, $deliveryCompanyId, $trackingNumber) {
            $shipment = Shipment::create([
                'order_id'            => $item->order_id,
                'order_item_id'       => $item->id,
                'delivery_company_id' => $deliveryCompanyId,
                'tracking_number'     => $trackingNumber,
                'status'              => 'pending',
            ]);
            $item->status = 'shipped';
            $item->save();

            $item->order->user->notify(new OrderShippedNotification($item->order, $shipment));
            return $shipment;
        });
    }
}

// app/Actions/Shipment/MarkShippedAction.php
namespace App\Actions\Shipment;

use App\Models\Shipment;
use App\Notifications\OrderShippedNotification;
use Illuminate\Support\Carbon;

class MarkShippedAction
{
    public function execute(Shipment $shipment): Shipment
    {
        abort_unless($shipment->status === 'pending', 409, 'Shipment is not pending.');
        $shipment->status = 'shipped';
        $shipment->shipped_at = Carbon::now();
        $shipment->save();
        $shipment->order->user->notify(new OrderShippedNotification($shipment->order, $shipment));
        return $shipment->refresh();
    }
}

// app/Actions/Shipment/MarkDeliveredAction.php
namespace App\Actions\Shipment;

use App\Models\Shipment;
use App\Notifications\OrderDeliveredNotification;
use Illuminate\Support\Carbon;

class MarkDeliveredAction
{
    public function execute(Shipment $shipment): Shipment
    {
        abort_unless($shipment->status === 'shipped', 409, 'Shipment has not been shipped yet.');
        $shipment->status = 'delivered';
        $shipment->delivered_at = Carbon::now();
        $shipment->save();
        $shipment->order->user->notify(new OrderDeliveredNotification($shipment->order, $shipment));
        return $shipment->refresh();
    }
}

// app/Actions/Shipment/UpdateTrackingAction.php — admin/printer/owner updates tracking_number
// app/Actions/Shipment/DeleteShipmentAction.php — admin soft-delete
```

`ShipmentController`:
- `index` (admin/printer scope)
- `show`
- `store` (printer/admin)
- `markShipped` / `markDelivered` (printer/admin)
- `updateTracking` (admin/printer)
- `destroy` (admin)

- [ ] **Step 3: Create the 4 Payment actions + controller**

```php
// app/Actions/Payment/CreatePaymentForOrderAction.php
namespace App\Actions\Payment;

use App\Models\Order;
use App\Models\Payment;

class CreatePaymentForOrderAction
{
    public function execute(Order $order, string $method, string $provider): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'method'   => $method,
            'provider' => $provider,
            'amount'   => $order->total_amount,
            'currency' => $order->currency,
            'status'   => 'pending',
        ]);
    }
}

// app/Actions/Payment/ConfirmPaymentAction.php
namespace App\Actions\Payment;

use App\Models\Payment;
use App\Notifications\PaymentConfirmedNotification;
use Illuminate\Support\Carbon;

class ConfirmPaymentAction
{
    public function execute(Payment $payment, ?string $providerChargeId): Payment
    {
        abort_unless($payment->status === 'pending', 409, 'Payment is not pending.');
        $payment->status = 'confirmed';
        $payment->provider_charge_id = $providerChargeId;
        $payment->confirmed_at = Carbon::now();
        $payment->save();
        $payment->order->user->notify(new PaymentConfirmedNotification($payment->order, $payment));
        return $payment->refresh();
    }
}

// app/Actions/Payment/FailPaymentAction.php — set status='failed'
// app/Actions/Payment/RefundPaymentAction.php — set status='refunded'
```

`PaymentController`:
- `index` (customer own / admin all; `?status=`)
- `show`
- `store` (admin creates pending payment)
- `confirm` / `fail` / `refund` (admin or system via webhook — webhook endpoint lives outside `/api/*` and is added in Phase 4)
- `destroy` (admin soft-delete)

- [ ] **Step 4: Create the 3 notifications + 2 events**

`OrderShippedNotification`, `OrderDeliveredNotification`, `PaymentConfirmedNotification`, `OrderCancelledNotification` — mirror `OrderPlacedNotification`'s shape (`via('database', 'mail')` + `toArray` payload).

Events `OrderShipped` and `OrderDelivered` mirror `OrderPlaced`.

- [ ] **Step 5: Mount the routes**

```php
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/me/payments',                   [PaymentController::class, 'index']);
    Route::get('/order-items/{item}/shipments',  [ShipmentController::class, 'index']);
});

Route::middleware(['auth:sanctum'])->group(function (): void {
    Route::get('/shipments/{shipment}', [ShipmentController::class, 'show']);
    Route::patch('/shipments/{shipment}/mark-shipped',   [ShipmentController::class, 'markShipped']);
    Route::patch('/shipments/{shipment}/mark-delivered', [ShipmentController::class, 'markDelivered']);
    Route::patch('/shipments/{shipment}/tracking',       [ShipmentController::class, 'updateTracking']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function (): void {
    Route::post('/order-items/{item}/shipments', [ShipmentController::class, 'store']);
    Route::delete('/admin/payments/{payment}', [PaymentController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/orders/{order}/payments', [PaymentController::class, 'store']);
    Route::patch('/payments/{payment}/confirm', [PaymentController::class, 'confirm']);
    Route::patch('/payments/{payment}/fail',    [PaymentController::class, 'fail']);
    Route::patch('/payments/{payment}/refund',  [PaymentController::class, 'refund']);
});
```

- [ ] **Step 6: Write the tests**

Cover for each controller: index/store/show/update/destroy plus the policy boundaries (admin vs. owner vs. printer scope).

Specific scenarios:
- Customer cannot view another customer's payments → 403
- Printer can only see shipments for items assigned to them
- Mark-delivered on a non-shipped shipment → 409
- Confirming a payment twice → 409

- [ ] **Step 7: Run the tests**

```bash
php artisan test --compact tests/Feature/Api/DeliveryCompanies tests/Feature/Api/Shipments tests/Feature/Api/Payments
```

Expected: 25+ tests pass.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/Api/{DeliveryCompany,Shipment,Payment}Controller.php \
  app/Actions/{Delivery,Shipment,Payment} app/Events app/Notifications \
  app/Policies/{DeliveryCompany,Shipment,Payment}Policy.php \
  app/Http/Requests/{Delivery,Shipment,Payment} \
  tests/Feature/Api/{DeliveryCompanies,Shipments,Payments} routes/api.php
git commit -m "feat(api): delivery companies, shipments, payments + 4 notification types"
```

---

## Task 13: Media + Notifications + Settings controllers

**Files:**
- Create: `app/Http/Controllers/Api/{Media,Notification,Setting}Controller.php`
- Create: `app/Actions/{Media,Notification,Setting}/*Action.php`
- Create: `app/Policies/{Media,Notification,Setting}Policy.php`
- Create: `app/Http/Requests/{Media,Notification,Setting}/*Request.php`
- Test: `tests/Feature/Api/{Media,Notifications,Settings}/*Test.php`

- [ ] **Step 1: Media — polymorphic uploads via Spatie MediaLibrary or a custom implementation**

The PRD uses polymorphic media. Spatie's `medialibrary` is heavy — the simpler v1.2 path is to roll our own `Media` row + store files on the configured `media` disk (local in dev, S3 in prod).

Create `app/Actions/Media/UploadMediaAction.php`:

```php
namespace App\Actions\Media;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class UploadMediaAction
{
    public function execute(UploadedFile $file, string $mediableType, string $mediableId, string $collection): Media
    {
        $disk = config('filesystems.media_disk', 'public');
        $path = $file->store("media/{$collection}/" . substr($mediableId, 0, 2), $disk);

        return Media::create([
            'mediable_type' => $mediableType,
            'mediable_id'   => $mediableId,
            'collection'    => $collection,
            'disk'          => $disk,
            'path'          => $path,
            'mime_type'     => $file->getMimeType(),
            'size_bytes'    => $file->getSize(),
            'is_primary'    => false,
        ]);
    }
}
```

`MediaController`:
- `show(Media $media)` — public if `mediable` is published/active; otherwise owner/admin only
- `destroy(Media $media)` — owner (of mediable) or admin

`StoreMediaRequest`:

```php
public function rules(): array
{
    return [
        'file'           => ['required', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:10240'],
        'mediable_type'  => ['required', 'string'],
        'mediable_id'    => ['required', 'string'],
        'collection'     => ['required', 'string', 'max:60'],
    ];
}
```

(Files are POSTed to a dedicated `media.store` route — mounted under `auth:sanctum` with a custom policy that checks mediable ownership.)

- [ ] **Step 2: Notifications — own + admin broadcast**

`NotificationController`:
- `index(Request $request)` — `?unread=true`; returns paginated own notifications
- `unreadCount()` — `{ "unread_count": N }`
- `markRead(Notification $notification)` — PATCH `/api/me/notifications/{id}/read`
- `markAllRead()` — POST `/api/me/notifications/mark-all-read`
- `destroy(Notification $notification)` — DELETE own
- `store(StoreNotificationRequest)` — admin broadcast (creates N rows for each target)

Create `app/Actions/Notification/MarkAllReadAction.php`:

```php
namespace App\Actions\Notification;

use App\Models\User;

class MarkAllReadAction
{
    public function execute(User $user): int
    {
        return $user->notifications()->whereNull('read_at')->update(['read_at' => now()]);
    }
}
```

- [ ] **Step 3: Settings — public reads, admin writes**

`SettingController`:
- `index(Request $request)` — public; filter by `?namespace=platform.` (forces `is_public=true`)
- `show(Setting $setting)` — public if `is_public=true`, else admin
- `store(StoreSettingRequest)` / `update(UpdateSettingRequest, Setting $setting)` — admin only

- [ ] **Step 4: Mount routes**

```php
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/media/{media}', [MediaController::class, 'show']);
    Route::delete('/media/{media}', [MediaController::class, 'destroy']);
    Route::post('/media', [MediaController::class, 'store']);

    Route::get('/me/notifications',                   [NotificationController::class, 'index']);
    Route::get('/me/notifications/unread-count',      [NotificationController::class, 'unreadCount']);
    Route::patch('/me/notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::post('/me/notifications/mark-all-read',    [NotificationController::class, 'markAllRead']);
    Route::delete('/me/notifications/{notification}', [NotificationController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->post(
    '/admin/notifications',
    [NotificationController::class, 'store']
);

Route::get('/settings',                  [SettingController::class, 'index']);
Route::get('/settings/{setting}',        [SettingController::class, 'show']);
Route::middleware(['auth:sanctum', 'role:admin'])->group(function (): void {
    Route::post('/admin/settings',           [SettingController::class, 'store']);
    Route::patch('/admin/settings/{setting}', [SettingController::class, 'update']);
});
```

- [ ] **Step 5: Write the tests**

- Media: owner can upload, other user cannot delete (403), admin can delete any
- Notifications: `unreadCount` returns 0 when no unread; `markAllRead` returns `{marked: N}`
- Settings: `?namespace=admin.` without admin role returns 403 (or 404 — pick one and document)
- Admin broadcast creates one row per target user

- [ ] **Step 6: Run + commit**

```bash
php artisan test --compact tests/Feature/Api/Media tests/Feature/Api/Notifications tests/Feature/Api/Settings
git add app/Http/Controllers/Api/{Media,Notification,Setting}Controller.php \
  app/Actions/{Media,Notification,Setting} app/Policies/{Media,Notification,Setting}Policy.php \
  app/Http/Requests/{Media,Notification,Setting} \
  tests/Feature/Api/{Media,Notifications,Settings} routes/api.php
git commit -m "feat(api): media uploads + notifications + settings"
```

---

## Task 14: Admin controller — dashboard, audit, integrity reports

**Files:**
- Create: `app/Http/Controllers/Api/AdminController.php`
- Create: `app/Actions/Admin/{ComputePlatformOverview,ListDeletedAudit,ProfileMismatches,OrphanedMedia,ItemsWithoutShipment,StuckCartItems}Action.php`
- Test: `tests/Feature/Api/Admin/*Test.php`

- [ ] **Step 1: Create the platform overview action**

Create `app/Actions/Admin/ComputePlatformOverviewAction.php`:

```php
namespace App\Actions\Admin;

use App\Models\{CartItem, Design, OrderItem, Payment, ProductTemplate, Shipment, User};
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ComputePlatformOverviewAction
{
    public function execute(): array
    {
        return Cache::remember('platform_overview_v1', 60, function () {
            $today = now()->toDateString();
            $thisMonth = now()->month;

            return [
                'total_customers'      => User::where('role', 'customer')->whereNull('deleted_at')->count(),
                'total_designers'      => User::where('role', 'designer')->whereNull('deleted_at')->count(),
                'total_printers'       => User::where('role', 'printer_provider')->whereNull('deleted_at')->count(),
                'total_published_designs' => Design::where('status', 'published')->whereNull('deleted_at')->count(),
                'total_active_templates'  => ProductTemplate::whereNull('deleted_at')->where('is_active', true)->count(),
                'orders_today'         => DB::table('orders')->whereDate('created_at', $today)->count(),
                'orders_this_month'    => DB::table('orders')->whereMonth('created_at', $thisMonth)->count(),
                'revenue_today'        => (float) DB::table('payments')
                    ->join('orders', 'orders.id', '=', 'payments.order_id')
                    ->where('payments.status', 'confirmed')
                    ->whereDate('payments.confirmed_at', $today)
                    ->sum('orders.total_amount'),
                'revenue_this_month'   => (float) DB::table('payments')
                    ->join('orders', 'orders.id', '=', 'payments.order_id')
                    ->where('payments.status', 'confirmed')
                    ->whereMonth('payments.confirmed_at', $thisMonth)
                    ->sum('orders.total_amount'),
                'pending_payments'     => Payment::where('status', 'pending')->count(),
                'pending_order_items'  => OrderItem::where('status', 'pending')->whereNull('deleted_at')->count(),
                'in_flight_shipments'  => Shipment::whereIn('status', ['pending', 'shipped'])->count(),
                'abandoned_carts_24h'  => CartItem::where('updated_at', '>=', now()->subDay())
                    ->distinct('user_id')->count('user_id'),
            ];
        });
    }
}
```

- [ ] **Step 2: Create the remaining 5 admin actions**

Each is a thin query wrapped in a class — same shape:

```php
namespace App\Actions\Admin;

class ListDeletedAuditAction
{
    public function execute(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return \App\Models\User::onlyTrashed()
            ->select('id', 'name', 'email', 'role', 'deleted_at')
            ->orderByDesc('deleted_at')
            ->paginate(50);
    }
}
```

(Repeat for ProfileMismatches — users with role designer/printer_provider lacking their profile row; OrphanedMedia — media with missing mediable; ItemsWithoutShipment — order_items with `handed_off` lacking a shipment; StuckCartItems — cart rows pointing to inactive variants.)

- [ ] **Step 3: Create the AdminController**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Actions\Admin\{ComputePlatformOverviewAction, /* ... */};

class AdminController extends Controller
{
    public function __construct(
        private readonly ComputePlatformOverviewAction $overview,
        private readonly ListDeletedAuditAction $audit,
        // ...
    ) {}

    public function dashboard() { return response()->json($this->overview->execute()); }
    public function auditDeleted() { return response()->json($this->audit->execute()); }
    public function profileMismatches() { /* ... */ }
    // ...
}
```

- [ ] **Step 4: Mount routes**

```php
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function (): void {
    Route::get('/dashboard',                  [AdminController::class, 'dashboard']);
    Route::get('/audit/deleted',              [AdminController::class, 'auditDeleted']);
    Route::get('/integrity/profile-mismatches',     [AdminController::class, 'profileMismatches']);
    Route::get('/integrity/orphaned-media',         [AdminController::class, 'orphanedMedia']);
    Route::get('/integrity/items-without-shipment', [AdminController::class, 'itemsWithoutShipment']);
    Route::get('/integrity/stuck-cart-items',      [AdminController::class, 'stuckCartItems']);
});
```

- [ ] **Step 5: Tests + commit**

Cover each endpoint with at least one happy-path test. Commit with `feat(api): admin dashboard, audit, and integrity endpoints`.

---

## Task 15: Report controller — 15 reports with date filters + CSV streaming

**Files:**
- Create: `app/Http/Controllers/Api/ReportController.php`
- Create: `app/Reports/*` (15 query classes, one per report)
- Create: `app/Reports/Contracts/Report.php` (interface)
- Create: `app/Reports/CsvExporter.php`
- Test: `tests/Feature/Api/Reports/*Test.php`

- [ ] **Step 1: Define the Report contract**

Create `app/Reports/Contracts/Report.php`:

```php
namespace App\Reports\Contracts;

use Illuminate\Http\Request;

interface Report
{
    /** @return iterable<array<string,mixed>>|Illuminate\Contracts\Pagination\LengthAwarePaginator */
    public function run(Request $request): mixed;

    /** @return array<int,string> */
    public function csvHeaders(): array;
}
```

- [ ] **Step 2: Create the CsvExporter**

Create `app/Reports/CsvExporter.php`:

```php
namespace App\Reports;

use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExporter
{
    public function stream(array $headers, iterable $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($v) => is_array($v) ? json_encode($v) : (string) $v, $row));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
```

- [ ] **Step 3: Create the 15 Report query classes**

Examples — the rest follow the same pattern:

```php
// app/Reports/Admin/PlatformOverviewReport.php
namespace App\Reports\Admin;

use App\Actions\Admin\ComputePlatformOverviewAction;
use App\Reports\Contracts\Report;
use Illuminate\Http\Request;

class PlatformOverviewReport implements Report
{
    public function __construct(private readonly ComputePlatformOverviewAction $compute) {}

    public function run(Request $request): mixed { return $this->compute->execute(); }

    public function csvHeaders(): array
    {
        return ['metric', 'value'];
    }
}
```

```php
// app/Reports/Admin/RevenueByDayReport.php
namespace App\Reports\Admin;

use App\Reports\Contracts\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RevenueByDayReport implements Report
{
    public function run(Request $request): mixed
    {
        $from = $request->date('from') ?? now()->subDays(30);
        $to   = $request->date('to')   ?? now();

        $query = DB::table('payments')
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->where('payments.status', 'confirmed')
            ->whereBetween('payments.confirmed_at', [$from, $to])
            ->groupByRaw('DATE(payments.confirmed_at)')
            ->orderByRaw('DATE(payments.confirmed_at)')
            ->selectRaw('DATE(payments.confirmed_at) as date, SUM(orders.total_amount) as revenue');

        if ($country = $request->string('country')->value()) {
            $query->where('orders.shipping_country', $country);
        }

        return $query->get();
    }

    public function csvHeaders(): array { return ['date', 'revenue']; }
}
```

Build the remaining 13 reports:
- `RevenueByCategoryReport`, `RevenueByDesignerReport`, `RevenueByPrinterReport`
- `ConversionFunnelReport`, `OrderStatusDistributionReport`, `OrderItemStatusDistributionReport`
- `PaymentMethodDistributionReport`, `ShippingCountryDistributionReport`, `AverageOrderValueReport`
- `TopDesignsReport`, `TopProductTemplatesReport`, `MostUsedTagsReport`
- `CustomerLtvReport`, `RefundCancellationRateReport`, `DeliveryPerformanceReport`
- `Designer/DashboardReport`, `Designer/RevenueByDesignReport`, `Designer/PayoutReport`
- `Printer/WorkQueueReport`, `Printer/PayoutReport`, `Printer/RevenueByTemplateReport`
- `Customer/OrderHistoryReport`, `Customer/ActiveShipmentsReport`

The Reports catalog doc (`doc/Reports-v1.2.md`) defines the exact queries — port each one.

- [ ] **Step 4: Create the ReportController**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Reports\Contracts\Report;
use App\Reports\CsvExporter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReportController extends Controller
{
    /** @var array<string,array{class: class-string<Report>, auth: string}> */
    private const REPORTS = [
        'admin/overview'              => ['class' => \App\Reports\Admin\PlatformOverviewReport::class, 'auth' => 'admin'],
        'admin/revenue-by-day'        => ['class' => \App\Reports\Admin\RevenueByDayReport::class, 'auth' => 'admin'],
        'admin/revenue-by-designer'   => ['class' => \App\Reports\Admin\RevenueByDesignerReport::class, 'auth' => 'admin'],
        'admin/revenue-by-printer'    => ['class' => \App\Reports\Admin\RevenueByPrinterReport::class, 'auth' => 'admin'],
        'admin/customer-ltv'          => ['class' => \App\Reports\Admin\CustomerLtvReport::class, 'auth' => 'admin'],
        'admin/top-designs'           => ['class' => \App\Reports\Admin\TopDesignsReport::class, 'auth' => 'admin'],
        'printer/work-queue'          => ['class' => \App\Reports\Printer\WorkQueueReport::class, 'auth' => 'printer_provider'],
        'printer/payout'              => ['class' => \App\Reports\Printer\PayoutReport::class, 'auth' => 'printer_provider'],
        'designer/dashboard'          => ['class' => \App\Reports\Designer\DashboardReport::class, 'auth' => 'designer'],
        'designer/payout'             => ['class' => \App\Reports\Designer\PayoutReport::class, 'auth' => 'designer'],
        'ops/stuck-payments'          => ['class' => \App\Reports\Ops\StuckPaymentsReport::class, 'auth' => 'admin'],
        'ops/cart-abandonment'        => ['class' => \App\Reports\Ops\CartAbandonmentReport::class, 'auth' => 'admin'],
        'ops/stuck-shipments'         => ['class' => \App\Reports\Ops\StuckShipmentsReport::class, 'auth' => 'admin'],
        'me/orders'                   => ['class' => \App\Reports\Customer\OrderHistoryReport::class, 'auth' => 'customer'],
        'me/shipments/active'         => ['class' => \App\Reports\Customer\ActiveShipmentsReport::class, 'auth' => 'customer'],
    ];

    public function __construct(private readonly CsvExporter $csv) {}

    public function show(Request $request, string $reportKey): mixed
    {
        abort_unless(isset(self::REPORTS[$reportKey]), 404, 'Unknown report.');
        $config = self::REPORTS[$reportKey];

        $this->authorizeReport($config['auth']);

        /** @var Report $report */
        $report = app($config['class']);
        $result = $report->run($request);

        if ($request->string('format')->value() === 'csv') {
            return $this->csv->stream(
                $report->csvHeaders(),
                is_array($result) ? $result : collect($result)->toArray(),
                "{$reportKey}-" . now()->format('Ymd-His') . '.csv'
            );
        }

        return response()->json($result);
    }

    private function authorizeReport(string $requiredRole): void
    {
        $user = auth()->user();
        abort_if($user === null, 401);

        if ($user->isAdmin()) {
            return;
        }

        abort_unless($user->role === $requiredRole, 403,
            "Role '{$user->role}' cannot access this report.");
    }
}
```

- [ ] **Step 5: Mount routes**

```php
Route::middleware('auth:sanctum')->get('/reports/{reportKey}', [ReportController::class, 'show'])
    ->where('reportKey', '[a-z\-\/]+')
    ->name('reports.show');

Route::middleware('auth:sanctum')->get('/me/orders',             [ReportController::class, 'show'])->defaults('reportKey', 'me/orders');
Route::middleware('auth:sanctum')->get('/me/shipments/active',   [ReportController::class, 'show'])->defaults('reportKey', 'me/shipments/active');
```

- [ ] **Step 6: Tests + commit**

One happy-path test per report (15 tests). Plus one CSV test that asserts the `text/csv` content-type and the header row. Plus one 403 test asserting that a customer hitting `admin/overview` is rejected.

Commit with `feat(api): 15 reports with role-based authorization and CSV streaming`.

---

## Task 16: Rate limiting on `/api/*`

**Files:**
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `routes/api.php`

- [ ] **Step 1: Define rate limiters**

Add to `AppServiceProvider::boot()`:

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('api', function (Request $request) {
    $user = $request->user();
    return $user
        ? Limit::perMinute(120)->by($user->id)
        : Limit::perMinute(30)->by($request->ip());
});

RateLimiter::for('auth', function (Request $request) {
    return Limit::perMinute(10)->by($request->ip())->response(function () {
        return response()->json(['message' => 'Too many auth attempts.'], 429);
    });
});

RateLimiter::for('uploads', function (Request $request) {
    return $request->user()
        ? Limit::perMinute(30)->by($request->user()->id)
        : Limit::perMinute(5)->by($request->ip());
});
```

- [ ] **Step 2: Apply the limiters**

In `routes/api.php`:
```php
Route::middleware(['throttle:api'])->group(function (): void {
    // all existing api routes
});

Route::middleware(['throttle:auth'])->group(function (): void {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login',    [AuthController::class, 'login']);
});

Route::middleware(['auth:sanctum', 'throttle:uploads'])->post('/media', [MediaController::class, 'store']);
```

- [ ] **Step 3: Add a test**

`tests/Feature/Api/RateLimitTest.php`:

```php
public function test_login_is_rate_limited_after_10_attempts(): void
{
    for ($i = 0; $i < 10; $i++) {
        $this->postJson('/api/auth/login', ['email' => "wrong{$i}@x.com", 'password' => 'nope'])
            ->assertStatus(401);
    }
    $this->postJson('/api/auth/login', ['email' => 'wrong11@x.com', 'password' => 'nope'])
        ->assertStatus(429);
}
```

Note: you'll need to clear the rate-limiter cache between test runs — `RateLimiter::clear('auth|...')` in `setUp()` is fine, or use `Cache::flush()`.

- [ ] **Step 4: Run + commit**

```bash
php artisan test --compact tests/Feature/Api/RateLimitTest.php
git add app/Providers/AppServiceProvider.php routes/api.php tests/Feature/Api/RateLimitTest.php
git commit -m "feat(api): rate limiters (api 120/min, auth 10/min, uploads 30/min)"
```

---

## Task 17: Phase 2 checkpoint — regenerate swagger.json and verify no regression

**Files:** none — verification.

- [ ] **Step 1: Re-run the full API test suite**

```bash
php artisan test --compact
```

Expected: ~300 tests pass.

- [ ] **Step 2: Regenerate the API docs**

```bash
composer test:api-docs
```

Expected: exit 0, no diff between the freshly-generated `swagger.json` and `swagger.phase1-baseline.json`.

- [ ] **Step 3: Diff sanity-check**

```bash
diff storage/api-docs/swagger.phase1-baseline.json storage/api-docs/swagger.json | head -50
```

Expected: either an empty diff (annotations live in `OpenApi.php`, controllers inherit them via class-level `@OA\*` — but the `OpenApi` class is the canonical source for now) OR a tiny diff with field metadata that was added to schemas. If you see structural changes, debug: the controller-level `@OA\*` annotations may have collided with the aggregator's. Resolve by deleting the duplicate from `OpenApi.php`.

- [ ] **Step 4: Beautify + lint**

```bash
vendor/bin/pint --dirty --format=agent
```

Expected: clean.

- [ ] **Step 5: Tag the Phase 2 release**

```bash
git add storage/api-docs/swagger.phase1-baseline.json
git commit -m "chore(api): retire Phase 2 swagger baseline after parity check"
git tag -a phase-2-api-surface -m "Phase 2 complete — controllers, actions, policies, Sanctum, reports"
git push origin local-smart-shot --tags
```

---

## Phase 2 Summary

| Deliverable                            | Where                                            | Verification                              |
| -------------------------------------- | ------------------------------------------------ | ----------------------------------------- |
| Sanctum tokens                         | `app/Actions/Auth/*`                             | `auth.login` returns `{token: "pod_…"}`   |
| All 15 controllers                     | `app/Http/Controllers/Api/*Controller.php`       | `php artisan route:list --path=api`       |
| All 19 Resources                       | `app/Http/Resources/*Resource.php`               | resources return expected shape           |
| All 25 Action classes                  | `app/Actions/*/*Action.php`                      | actions are unit-tested via feature tests |
| 9 Policies                             | `app/Policies/*Policy.php`                       | admin bypass; ownership enforced          |
| 5 Notifications + 5 Events             | `app/Notifications/*`, `app/Events/*`            | dispatched on OrderPlaced, OrderShipped, etc. |
| 15 Reports                             | `app/Reports/*`                                  | `GET /api/reports/admin/overview` returns JSON/CSV |
| Rate limiting                          | `app/Providers/AppServiceProvider.php`           | 11th login attempt → 429                   |
| ~300 feature tests                     | `tests/Feature/Api/*`                            | `php artisan test --compact`              |
| `phase-2-api-surface` tag              | git tag                                          | `git tag -l`                              |

Phase 3 takes the API surface and builds the Blade UI on top of it — no new server logic, just pages.






