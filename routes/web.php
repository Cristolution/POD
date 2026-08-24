<?php

use App\Http\Controllers\Web\AccountController;
use App\Http\Controllers\Web\AccountOrderController;
use App\Http\Controllers\Web\AddressController;
use App\Http\Controllers\Web\BrowseController;
use App\Http\Controllers\Web\CartController;
use App\Http\Controllers\Web\CheckoutController;
use App\Http\Controllers\Web\DesignDetailController;
use App\Http\Controllers\Web\DesignerProfileController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\LoginController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\OrderController;
use App\Http\Controllers\Web\PasswordResetController;
use App\Http\Controllers\Web\PrinterProviderController;
use App\Http\Controllers\Web\RegisterController;
use Illuminate\Support\Facades\Route;
use L5Swagger\Http\Controllers\SwaggerController;
use L5Swagger\Http\Middleware\Config;
use L5Swagger\L5SwaggerFacade;

Route::middleware(['share.cart'])->group(function (): void {
    Route::get('/', HomeController::class)->name('home');

    // -- Task 3: browse pages -------------------------------------------
    Route::get('/browse/designs', [BrowseController::class, 'designs'])->name('browse.designs');
    Route::get('/browse/categories', [BrowseController::class, 'categories'])->name('browse.categories');
    Route::get('/browse/designers', [BrowseController::class, 'designers'])->name('browse.designers');

    // -- Task 4: real design detail page ---------------------------------
    Route::get('/designs/{design}', [DesignDetailController::class, 'show'])->name('design.show');

    // -- Task 7: web auth (login / register / password reset) ------------
    // Uses Laravel's session guard ('web'), not Sanctum. The 'guest'
    // middleware bounces already-authenticated visitors back home so they
    // don't see the login form. The 'auth' middleware on /logout ensures
    // anonymous POSTs redirect to login (handled by the
    // Illuminate\Auth\Middleware\Authenticate middleware default).
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [LoginController::class, 'create'])->name('login');
        Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:auth');

        Route::get('/register', [RegisterController::class, 'create'])->name('register');
        Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:auth');

        Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
        Route::post('/forgot-password', [PasswordResetController::class, 'email'])->name('password.email')->middleware('throttle:auth');
        Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
        Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.store')->middleware('throttle:auth');
    });

    // -- Task 5: real cart routes ----------------------------------------
    Route::middleware('auth')->group(function (): void {
        Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
        Route::post('/cart/items', [CartController::class, 'store'])->name('cart.items.store');
        Route::patch('/cart/items/{item}', [CartController::class, 'update'])->name('cart.items.update');
        Route::delete('/cart/items/{item}', [CartController::class, 'destroy'])->name('cart.items.destroy');
        Route::delete('/cart', [CartController::class, 'clearAll'])->name('cart.clear');

        // -- Task 6: checkout + order detail -----------------------------
        Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
        Route::post('/checkout', [CheckoutController::class, 'place'])->name('checkout.place');
        // Note: the API owns the name `orders.show` for the Sanctum-protected
        // REST resource. The web confirmation page shares the URL pattern
        // but uses `orders.confirmation` so the named route resolves to the
        // session-auth web handler, not the API resource.
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.confirmation');

        // -- Task 7: web logout (auth only) ------------------------------
        Route::post('/logout', [LoginController::class, 'destroy'])->name('web.logout');
    });

    // -- Task 8: account dashboard + addresses + orders + notifications --
    // Session-authenticated. Sidebar component renders links to all four
    // account pages. Form handlers live on PATCH/POST/DELETE so they
    // remain CSRF-protected by the default VerifyCsrfToken middleware.
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

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
        Route::patch('/notifications/{notification}', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    });

    // -- Task 9: public designer / printer profiles ---------------------
    // Anonymous, route-model-bound. Sidebar / edit pages for designers
    // and printers live behind auth + role middleware (see below).
    Route::get('/designers/{designer}', [DesignerProfileController::class, 'show'])->name('designer.show');
    Route::get('/printers/{printer}', [PrinterProviderController::class, 'show'])->name('printer.show');

    // -- Task 9: designer self-service ---------------------------------
    // Session-authenticated designers only. Profile-edit form posts to
    // designer.update (PATCH) so the form is CSRF-protected by the
    // default VerifyCsrfToken middleware.
    Route::middleware(['auth', 'role:designer'])->prefix('designer')->name('designer.')->group(function (): void {
        Route::get('/', [DesignerProfileController::class, 'dashboard'])->name('dashboard');
        Route::get('/edit', [DesignerProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [DesignerProfileController::class, 'update'])->name('update');
    });

    // -- Task 9: printer self-service -----------------------------------
    Route::middleware(['auth', 'role:printer_provider'])->prefix('printer')->name('printer.')->group(function (): void {
        Route::get('/', [PrinterProviderController::class, 'dashboard'])->name('dashboard');
        Route::get('/edit', [PrinterProviderController::class, 'edit'])->name('edit');
        Route::patch('/', [PrinterProviderController::class, 'update'])->name('update');
    });

    // -- Task 10: legal pages --------------------------------------------
    // Render the static Blade templates directly. No controller is needed
    // because these pages have no business logic — counsel replaces the
    // text in resources/views/pages/legal/ before public launch.
    Route::view('/legal/terms', 'pages.legal.terms')->name('legal.terms');
    Route::view('/legal/privacy', 'pages.legal.privacy')->name('legal.privacy');
});

// L5-Swagger documentation routes. Mounted identically in all 3 environments so
// /api/docs is always reachable during development. In production, gate behind
// admin auth (Task 14) before exposing publicly.
//
// NOTE: In l5-swagger v11.x, JSON docs are served by SwaggerController@docs
// (JsonController was removed). Using SwaggerController for both routes.
//
// The Config middleware reads the 'l5-swagger.documentation' action key that
// the package's own routes.php sets via Route::group(). Since we mount the
// routes manually here (so the URI is /api/docs and not /api/documentation),
// we re-attach the same action key + middleware on each route below.
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
