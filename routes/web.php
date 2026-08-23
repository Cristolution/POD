<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['app' => config('app.name'), 'env' => config('app.env')]);
});

// L5-Swagger documentation routes. Mounted identically in all 3 environments so
// /api/docs is always reachable during development. In production, gate behind
// admin auth (Task 14) before exposing publicly.
//
// NOTE: In l5-swagger v11.x, JSON docs are served by SwaggerController@docs
// (JsonController was removed). Using SwaggerController for both routes.
if (class_exists(\L5Swagger\L5SwaggerFacade::class)) {
    Route::get('/api/docs', [\L5Swagger\Http\Controllers\SwaggerController::class, 'api'])
        ->name('l5-swagger.api');
    Route::get('/api/docs.json', [\L5Swagger\Http\Controllers\SwaggerController::class, 'docs'])
        ->name('l5-swagger.docs');
    Route::get('/api/docs/{jsonFile?}', [\L5Swagger\Http\Controllers\SwaggerController::class, 'docs'])
        ->name('l5-swagger.docs.file');
}