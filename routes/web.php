<?php

use App\Http\Controllers\Web\HomeController;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use L5Swagger\Http\Controllers\SwaggerController;
use L5Swagger\Http\Middleware\Config;
use L5Swagger\L5SwaggerFacade;

Route::middleware(['share.unread'])->group(function (): void {
    Route::get('/', HomeController::class)->name('home');

    // -- Stub named routes (Task 2) ---------------------------------------
    // Layout components (header/footer/home) reference these named routes
    // before the corresponding feature tasks add their real handlers.
    // Each stub returns 404 with a consistent page so the home view renders
    // cleanly. They are owned by the following tasks and will be replaced:
    //
    //   browse.designs / browse.categories / browse.designers -> Task 3
    //   cart.show                                              -> Task 5
    //   login / register / web.logout                          -> Task 7
    //   account.dashboard / account.orders / account.notifications -> Task 8
    //   legal.terms / legal.privacy                            -> Task 10
    //
    // Each stub uses the placeholder URL the real route will eventually own.
    // The closure returns a plain 404 response without invoking a view so the
    // Task 2 home view renders cleanly without any extra templates.
    $placeholder = static fn (): Response => response('Placeholder — route implementation pending.', 404);

    Route::get('/browse/designs', $placeholder)->name('browse.designs');
    Route::get('/browse/categories', $placeholder)->name('browse.categories');
    Route::get('/browse/designers', $placeholder)->name('browse.designers');
    Route::get('/cart', $placeholder)->name('cart.show');
    Route::get('/login', $placeholder)->name('login');
    Route::get('/register', $placeholder)->name('register');
    Route::post('/logout', $placeholder)->name('web.logout');
    Route::get('/account', $placeholder)->name('account.dashboard');
    Route::get('/account/orders', $placeholder)->name('account.orders');
    Route::get('/account/notifications', $placeholder)->name('account.notifications');
    Route::get('/legal/terms', $placeholder)->name('legal.terms');
    Route::get('/legal/privacy', $placeholder)->name('legal.privacy');
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
