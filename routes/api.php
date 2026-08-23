<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MeController;
use Illuminate\Support\Facades\Route;

// Public auth endpoints.
Route::post('/auth/register', [AuthController::class, 'register'])->name('auth.register');
Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');

// Authenticated auth endpoints.
Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
});

// Self-service user endpoints.
Route::middleware('auth:sanctum')->prefix('me')->group(function (): void {
    Route::get('/', [MeController::class, 'show'])->name('me.show');
    Route::patch('/', [MeController::class, 'update'])->name('me.update');
    Route::patch('/password', [MeController::class, 'updatePassword'])->name('me.updatePassword');
    Route::delete('/', [MeController::class, 'destroy'])->name('me.destroy');
});
