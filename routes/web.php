<?php

use App\Http\Controllers\Web\AccountController;
use App\Http\Controllers\Web\AccountOrderController;
use App\Http\Controllers\Web\AddressController;
use App\Http\Controllers\Web\BrowseController;
use App\Http\Controllers\Web\CartController;
use App\Http\Controllers\Web\CheckoutController;
use App\Http\Controllers\Web\ConfirmDeleteMeController;
use App\Http\Controllers\Web\DesignDetailController;
use App\Http\Controllers\Web\DesignerDesignController;
use App\Http\Controllers\Web\DesignerMappingController;
use App\Http\Controllers\Web\DesignerOrderController;
use App\Http\Controllers\Web\DesignerProfileController;
use App\Http\Controllers\Web\DesignReviewController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\LoginController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\OrderController;
use App\Http\Controllers\Web\PasswordResetController;
use App\Http\Controllers\Web\PrinterProviderController;
use App\Http\Controllers\Web\RegisterController;
use App\Http\Controllers\Web\SitemapController;
use App\Http\Controllers\Web\WishlistController;
use Illuminate\Support\Facades\Route;
use L5Swagger\Http\Controllers\SwaggerController;
use L5Swagger\Http\Middleware\Config;
use L5Swagger\L5SwaggerFacade;

// =============================================================================
// Public site routes — registered twice each (two-route convention).
//
// Each public route is declared in two sibling forms:
//
//   1. Unprefixed  → `Route::get('/cart', ...)->name('cart.show');`
//      Serves the English-default form. The `localize` middleware falls back
//      to the pod_locale cookie, then the Accept-Language header, then the
//      app default (en) when no {locale} is present in the URL.
//
//   2. Localized   → `Route::get('/{locale}/cart', ...)->where('locale', '(en|ar|tr)')->name('cart.show.localized');`
//      Serves the Arabic (/ar/...) and Turkish (/tr/...) forms. The .localized
//      name suffix is the contract that `localize()` and `LocalizedUrl::route()`
//      use to swap to the sibling route when generating cross-locale URLs.
//
// The `localize` middleware is idempotent and applied to the outer group so
// both forms get the same resolution pipeline. `share.cart` stays on the outer
// group so the cart contents follow the visitor across locale switches.
//
// Within each section, the unprefixed form is declared first, the localized
// sibling second — reviewers can scan the pair side by side.
//
// Routes that don't need localization (admin docs, etc.) stay OUTSIDE this
// group below.
// =============================================================================
Route::middleware(['share.cart', 'localize'])->group(function (): void {

    // ---------------------------------------------------------------------
    // Home
    // ---------------------------------------------------------------------
    Route::get('/', HomeController::class)->name('home');
    Route::get('/{locale}', HomeController::class)->where('locale', '(en|ar|tr)')->name('home.localized');

    // ---------------------------------------------------------------------
    // Browse pages
    // ---------------------------------------------------------------------
    Route::get('/browse/designs', [BrowseController::class, 'designs'])->name('browse.designs');
    Route::get('/browse/categories', [BrowseController::class, 'categories'])->name('browse.categories');
    Route::get('/browse/designers', [BrowseController::class, 'designers'])->name('browse.designers');

    Route::get('/{locale}/browse/designs', [BrowseController::class, 'designs'])->where('locale', '(en|ar|tr)')->name('browse.designs.localized');
    Route::get('/{locale}/browse/categories', [BrowseController::class, 'categories'])->where('locale', '(en|ar|tr)')->name('browse.categories.localized');
    Route::get('/{locale}/browse/designers', [BrowseController::class, 'designers'])->where('locale', '(en|ar|tr)')->name('browse.designers.localized');

    // ---------------------------------------------------------------------
    // Design detail
    // ---------------------------------------------------------------------
    Route::get('/designs/{design}', [DesignDetailController::class, 'show'])->name('design.show');
    Route::get('/{locale}/designs/{design}', [DesignDetailController::class, 'show'])->where('locale', '(en|ar|tr)')->name('design.show.localized');

    // ---------------------------------------------------------------------
    // Reviews on a design (customer submits, owner/admin deletes) — auth
    // ---------------------------------------------------------------------
    Route::middleware('auth')->group(function (): void {
        Route::post('/designs/{design}/reviews', [DesignReviewController::class, 'store'])->name('design.reviews.store');
        Route::delete('/designs/{design}/reviews/{review}', [DesignReviewController::class, 'destroy'])->name('design.reviews.destroy');

        Route::post('/{locale}/designs/{design}/reviews', [DesignReviewController::class, 'store'])->where('locale', '(en|ar|tr)')->name('design.reviews.store.localized');
        Route::delete('/{locale}/designs/{design}/reviews/{review}', [DesignReviewController::class, 'destroy'])->where('locale', '(en|ar|tr)')->name('design.reviews.destroy.localized');
    });

    // ---------------------------------------------------------------------
    // Wishlist (favourite designs) — customer only — auth + role:customer
    // ---------------------------------------------------------------------
    Route::middleware(['auth', 'role:customer'])->group(function (): void {
        Route::post('/designs/{design}/wishlist', [WishlistController::class, 'store'])->name('design.wishlist.store');
        Route::delete('/designs/{design}/wishlist', [WishlistController::class, 'destroy'])->name('design.wishlist.destroy');

        Route::post('/{locale}/designs/{design}/wishlist', [WishlistController::class, 'store'])->where('locale', '(en|ar|tr)')->name('design.wishlist.store.localized');
        Route::delete('/{locale}/designs/{design}/wishlist', [WishlistController::class, 'destroy'])->where('locale', '(en|ar|tr)')->name('design.wishlist.destroy.localized');
    });

    // ---------------------------------------------------------------------
    // Web auth (login / register / password reset) — guest
    // ---------------------------------------------------------------------
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [LoginController::class, 'create'])->name('login');
        Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:auth');
        Route::get('/register', [RegisterController::class, 'create'])->name('register');
        Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:auth');
        Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
        Route::post('/forgot-password', [PasswordResetController::class, 'email'])->name('password.email')->middleware('throttle:auth');
        Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
        Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.store')->middleware('throttle:auth');

        Route::get('/{locale}/login', [LoginController::class, 'create'])->where('locale', '(en|ar|tr)')->name('login.localized');
        Route::post('/{locale}/login', [LoginController::class, 'store'])->where('locale', '(en|ar|tr)')->middleware('throttle:auth');
        Route::get('/{locale}/register', [RegisterController::class, 'create'])->where('locale', '(en|ar|tr)')->name('register.localized');
        Route::post('/{locale}/register', [RegisterController::class, 'store'])->where('locale', '(en|ar|tr)')->middleware('throttle:auth');
        Route::get('/{locale}/forgot-password', [PasswordResetController::class, 'create'])->where('locale', '(en|ar|tr)')->name('password.request.localized');
        Route::post('/{locale}/forgot-password', [PasswordResetController::class, 'email'])->where('locale', '(en|ar|tr)')->name('password.email.localized')->middleware('throttle:auth');
        Route::get('/{locale}/reset-password/{token}', [PasswordResetController::class, 'edit'])->where('locale', '(en|ar|tr)')->name('password.reset.localized');
        Route::post('/{locale}/reset-password', [PasswordResetController::class, 'update'])->where('locale', '(en|ar|tr)')->name('password.store.localized')->middleware('throttle:auth');
    });

    // ---------------------------------------------------------------------
    // Cart + checkout + orders + logout — auth
    // ---------------------------------------------------------------------
    Route::middleware('auth')->group(function (): void {
        Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
        Route::post('/cart/items', [CartController::class, 'store'])->name('cart.items.store');
        Route::patch('/cart/items/{item}', [CartController::class, 'update'])->name('cart.items.update');
        Route::delete('/cart/items/{item}', [CartController::class, 'destroy'])->name('cart.items.destroy');
        Route::delete('/cart', [CartController::class, 'clearAll'])->name('cart.clear');

        Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
        Route::post('/checkout', [CheckoutController::class, 'place'])->name('checkout.place');

        // Note: the API owns the name `orders.show` for the Sanctum-protected
        // REST resource. The web confirmation page shares the URL pattern
        // but uses `orders.confirmation` so the named route resolves to the
        // session-auth web handler, not the API resource.
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.confirmation');

        Route::post('/logout', [LoginController::class, 'destroy'])->name('web.logout');

        Route::get('/{locale}/cart', [CartController::class, 'show'])->where('locale', '(en|ar|tr)')->name('cart.show.localized');
        Route::post('/{locale}/cart/items', [CartController::class, 'store'])->where('locale', '(en|ar|tr)')->name('cart.items.store.localized');
        Route::patch('/{locale}/cart/items/{item}', [CartController::class, 'update'])->where('locale', '(en|ar|tr)')->name('cart.items.update.localized');
        Route::delete('/{locale}/cart/items/{item}', [CartController::class, 'destroy'])->where('locale', '(en|ar|tr)')->name('cart.items.destroy.localized');
        Route::delete('/{locale}/cart', [CartController::class, 'clearAll'])->where('locale', '(en|ar|tr)')->name('cart.clear.localized');

        Route::get('/{locale}/checkout', [CheckoutController::class, 'show'])->where('locale', '(en|ar|tr)')->name('checkout.show.localized');
        Route::post('/{locale}/checkout', [CheckoutController::class, 'place'])->where('locale', '(en|ar|tr)')->name('checkout.place.localized');

        Route::get('/{locale}/orders/{order}', [OrderController::class, 'show'])->where('locale', '(en|ar|tr)')->name('orders.confirmation.localized');

        Route::post('/{locale}/logout', [LoginController::class, 'destroy'])->where('locale', '(en|ar|tr)')->name('web.logout.localized');
    });

    // ---------------------------------------------------------------------
    // Account dashboard + addresses + orders + notifications — auth
    // ---------------------------------------------------------------------
    Route::middleware('auth')->prefix('account')->name('account.')->group(function (): void {
        Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
        Route::patch('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
        Route::patch('/password', [AccountController::class, 'updatePassword'])->name('password.update');

        Route::get('/addresses', [AddressController::class, 'index'])->name('addresses.index');
        Route::get('/addresses/new', [AddressController::class, 'create'])->name('addresses.create');
        Route::post('/addresses', [AddressController::class, 'store'])->name('addresses.store');
        Route::get('/addresses/{address}/edit', [AddressController::class, 'edit'])->name('addresses.edit');
        Route::patch('/addresses/{address}', [AddressController::class, 'update'])->name('addresses.update');
        Route::delete('/addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');

        Route::get('/orders', [AccountOrderController::class, 'index'])->name('orders');

        Route::get('/wishlist', [AccountController::class, 'wishlist'])->name('wishlist');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
        Route::patch('/notifications/{notification}', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    });

    Route::middleware('auth')->prefix('{locale}/account')->where(['locale' => '(en|ar|tr)'])->name('account.')->group(function (): void {
        Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard.localized');
        Route::patch('/profile', [AccountController::class, 'updateProfile'])->name('profile.update.localized');
        Route::patch('/password', [AccountController::class, 'updatePassword'])->name('password.update.localized');

        Route::get('/addresses', [AddressController::class, 'index'])->name('addresses.index.localized');
        Route::get('/addresses/new', [AddressController::class, 'create'])->name('addresses.create.localized');
        Route::post('/addresses', [AddressController::class, 'store'])->name('addresses.store.localized');
        Route::get('/addresses/{address}/edit', [AddressController::class, 'edit'])->name('addresses.edit.localized');
        Route::patch('/addresses/{address}', [AddressController::class, 'update'])->name('addresses.update.localized');
        Route::delete('/addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy.localized');

        Route::get('/orders', [AccountOrderController::class, 'index'])->name('orders.localized');

        Route::get('/wishlist', [AccountController::class, 'wishlist'])->name('wishlist.localized');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.localized');
        Route::patch('/notifications/{notification}', [NotificationController::class, 'markRead'])->name('notifications.read.localized');
        Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy.localized');
    });

    // ---------------------------------------------------------------------
    // Public designer / printer profiles
    // ---------------------------------------------------------------------
    Route::get('/designers/{designer}', [DesignerProfileController::class, 'show'])->name('designer.show');
    Route::get('/printers/{printer}', [PrinterProviderController::class, 'show'])->name('printer.show');

    Route::get('/{locale}/designers/{designer}', [DesignerProfileController::class, 'show'])->where('locale', '(en|ar|tr)')->name('designer.show.localized');
    Route::get('/{locale}/printers/{printer}', [PrinterProviderController::class, 'show'])->where('locale', '(en|ar|tr)')->name('printer.show.localized');

    // ---------------------------------------------------------------------
    // Designer self-service — auth + role:designer
    // ---------------------------------------------------------------------
    Route::middleware(['auth', 'role:designer'])->prefix('designer')->name('designer.')->group(function (): void {
        Route::get('/', [DesignerProfileController::class, 'dashboard'])->name('dashboard');
        Route::get('/edit', [DesignerProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [DesignerProfileController::class, 'update'])->name('update');

        Route::get('/designs/create', [DesignerDesignController::class, 'create'])->name('designs.create');
        Route::post('/designs', [DesignerDesignController::class, 'store'])->name('designs.store');
        Route::get('/designs/{design}', [DesignerDesignController::class, 'show'])->name('designs.show');
        Route::get('/designs/{design}/edit', [DesignerDesignController::class, 'edit'])->name('designs.edit');
        Route::patch('/designs/{design}', [DesignerDesignController::class, 'update'])->name('designs.update');
        Route::delete('/designs/{design}', [DesignerDesignController::class, 'destroy'])->name('designs.destroy');

        Route::get('/orders', [DesignerOrderController::class, 'index'])->name('orders');
        Route::get('/mappings', [DesignerMappingController::class, 'index'])->name('mappings');

        // Designer self-service mapping CRUD. Static /mappings/create must be
        // declared before any /mappings/{mapping} route so the literal path
        // matches first.
        Route::get('/mappings/create', [DesignerMappingController::class, 'create'])->name('mappings.create');
        Route::post('/mappings', [DesignerMappingController::class, 'store'])->name('mappings.store');
        Route::get('/mappings/{mapping}/edit', [DesignerMappingController::class, 'edit'])->name('mappings.edit');
        Route::patch('/mappings/{mapping}', [DesignerMappingController::class, 'update'])->name('mappings.update');
        Route::delete('/mappings/{mapping}', [DesignerMappingController::class, 'destroy'])->name('mappings.destroy');
    });

    Route::middleware(['auth', 'role:designer'])->prefix('{locale}/designer')->where(['locale' => '(en|ar|tr)'])->name('designer.')->group(function (): void {
        Route::get('/', [DesignerProfileController::class, 'dashboard'])->name('dashboard.localized');
        Route::get('/edit', [DesignerProfileController::class, 'edit'])->name('edit.localized');
        Route::patch('/', [DesignerProfileController::class, 'update'])->name('update.localized');

        Route::get('/designs/create', [DesignerDesignController::class, 'create'])->name('designs.create.localized');
        Route::post('/designs', [DesignerDesignController::class, 'store'])->name('designs.store.localized');
        Route::get('/designs/{design}', [DesignerDesignController::class, 'show'])->name('designs.show.localized');
        Route::get('/designs/{design}/edit', [DesignerDesignController::class, 'edit'])->name('designs.edit.localized');
        Route::patch('/designs/{design}', [DesignerDesignController::class, 'update'])->name('designs.update.localized');
        Route::delete('/designs/{design}', [DesignerDesignController::class, 'destroy'])->name('designs.destroy.localized');

        Route::get('/orders', [DesignerOrderController::class, 'index'])->name('orders.localized');
        Route::get('/mappings', [DesignerMappingController::class, 'index'])->name('mappings.localized');

        // Designer self-service mapping CRUD (localized). Static
        // /mappings/create must be declared before any /mappings/{mapping}
        // route so the literal path matches first.
        Route::get('/mappings/create', [DesignerMappingController::class, 'create'])->name('mappings.create.localized');
        Route::post('/mappings', [DesignerMappingController::class, 'store'])->name('mappings.store.localized');
        Route::get('/mappings/{mapping}/edit', [DesignerMappingController::class, 'edit'])->name('mappings.edit.localized');
        Route::patch('/mappings/{mapping}', [DesignerMappingController::class, 'update'])->name('mappings.update.localized');
        Route::delete('/mappings/{mapping}', [DesignerMappingController::class, 'destroy'])->name('mappings.destroy.localized');
    });

    // ---------------------------------------------------------------------
    // Printer self-service — auth + role:printer_provider
    // ---------------------------------------------------------------------
    Route::middleware(['auth', 'role:printer_provider'])->prefix('printer')->name('printer.')->group(function (): void {
        Route::get('/', [PrinterProviderController::class, 'dashboard'])->name('dashboard');
        Route::get('/edit', [PrinterProviderController::class, 'edit'])->name('edit');
        Route::patch('/', [PrinterProviderController::class, 'update'])->name('update');

        // Fulfilment queue — printer advances each line item through
        // received → printing → printed → handed_off.
        Route::get('/fulfilment', [PrinterProviderController::class, 'fulfilment'])->name('fulfilment');
        Route::patch('/fulfilment/{item}', [PrinterProviderController::class, 'advanceItem'])->name('fulfilment.advance');
    });

    Route::middleware(['auth', 'role:printer_provider'])->prefix('{locale}/printer')->where(['locale' => '(en|ar|tr)'])->name('printer.')->group(function (): void {
        Route::get('/', [PrinterProviderController::class, 'dashboard'])->name('dashboard.localized');
        Route::get('/edit', [PrinterProviderController::class, 'edit'])->name('edit.localized');
        Route::patch('/', [PrinterProviderController::class, 'update'])->name('update.localized');

        Route::get('/fulfilment', [PrinterProviderController::class, 'fulfilment'])->name('fulfilment.localized');
        Route::patch('/fulfilment/{item}', [PrinterProviderController::class, 'advanceItem'])->where('locale', '(en|ar|tr)')->name('fulfilment.advance.localized');
    });

    // ---------------------------------------------------------------------
    // Legal pages — static Blade templates, no controller required because
    // these pages have no business logic.
    // ---------------------------------------------------------------------
    Route::view('/legal/terms', 'pages.legal.terms')->name('legal.terms');
    Route::view('/legal/privacy', 'pages.legal.privacy')->name('legal.privacy');

    Route::view('/{locale}/legal/terms', 'pages.legal.terms')->where('locale', '(en|ar|tr)')->name('legal.terms.localized');
    Route::view('/{locale}/legal/privacy', 'pages.legal.privacy')->where('locale', '(en|ar|tr)')->name('legal.privacy.localized');

    // ---------------------------------------------------------------------
    // SEO: XML sitemap for search engine crawlers
    // ---------------------------------------------------------------------
    Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
    Route::get('/{locale}/sitemap.xml', SitemapController::class)->where('locale', '(en|ar|tr)')->name('sitemap.localized');
});

// ---------------------------------------------------------------------
// Account-deletion confirmation — signed URL, accessible to anyone
// holding a valid (non-expired) link. NOT auth-gated because the user
// is expected to be logged out by the time they click the email link.
// ---------------------------------------------------------------------
Route::get('/me/delete/confirm/{id}', ConfirmDeleteMeController::class)
    ->name('me.delete.confirm')
    ->middleware('signed');

// ---------------------------------------------------------------------
// Account-deleted landing page — public, shown after a successful
// confirmation.
// ---------------------------------------------------------------------
Route::view('/account-deleted', 'pages.account-deleted')->name('account.deleted');
Route::view('/{locale}/account-deleted', 'pages.account-deleted')
    ->where('locale', '(en|ar|tr)')
    ->name('account.deleted.localized');

// =============================================================================
// L5-Swagger documentation routes. Mounted identically in all 3 environments so
// /api/docs is always reachable during development. In production, gate behind
// admin auth (Task 14) before exposing publicly.
//
// Intentionally kept OUTSIDE the localized group above — admin docs are
// developer-facing and stay English-only for now.
//
// NOTE: In l5-swagger v11.x, JSON docs are served by SwaggerController@docs
// (JsonController was removed). Using SwaggerController for both routes.
//
// The Config middleware reads the 'l5-swagger.documentation' action key that
// the package's own routes.php sets via Route::group(). Since we mount the
// routes manually here (so the URI is /api/docs and not /api/documentation),
// we re-attach the same action key + middleware on each route below.
// =============================================================================
if (class_exists(L5SwaggerFacade::class)) {
    $l5Doc = config('l5-swagger.default', 'default');
    $l5Controller = SwaggerController::class;
    $l5Action = static fn (string $name, string $method): array => [
        'as' => $name,
        'uses' => $l5Controller.'@'.$method,
        'l5-swagger.documentation' => $l5Doc,
        'middleware' => [Config::class],
    ];

    if (app()->environment('local', 'testing', 'development')) {
        // Public access in non-production environments.
        Route::get('/api/docs', $l5Action('l5-swagger.api', 'api'));
        Route::get('/api/docs.json', $l5Action('l5-swagger.docs', 'docs'));
        Route::get('/api/docs/{jsonFile?}', $l5Action('l5-swagger.docs.file', 'docs'));
    } else {
        // Production: gate behind web-session auth + admin role.
        Route::middleware(['auth', 'admin'])->group(function () use ($l5Action): void {
            Route::get('/api/docs', $l5Action('l5-swagger.api', 'api'));
            Route::get('/api/docs.json', $l5Action('l5-swagger.docs', 'docs'));
            Route::get('/api/docs/{jsonFile?}', $l5Action('l5-swagger.docs.file', 'docs'));
        });
    }
}
