<?php

use Illuminate\Support\Facades\Route;
use L5Swagger\Http\Controllers\SwaggerController;
use L5Swagger\Http\Middleware\Config;
use L5Swagger\L5SwaggerFacade;

Route::get('/', function () {
    return response()->json(['app' => config('app.name'), 'env' => config('app.env')]);
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
