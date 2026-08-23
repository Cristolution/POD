<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DesignerController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\PrinterController;
use App\Http\Controllers\Api\UserController;
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

// Admin user management.
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin/users')->group(function (): void {
    Route::get('/', [UserController::class, 'index'])->name('admin.users.index');
    Route::patch('/{user}', [UserController::class, 'update'])->name('admin.users.update');
    Route::delete('/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
});

// Restore + show soft-deleted users — bind with trashed.
Route::middleware(['auth:sanctum', 'role:admin'])
    ->prefix('admin/users')
    ->group(function (): void {
        Route::get('/{user}', [UserController::class, 'show'])->name('admin.users.show')->withTrashed();
        Route::post('/{user}/restore', [UserController::class, 'restore'])->name('admin.users.restore')->withTrashed();
    });

// Designer + printer profile catalogs (public reads).
Route::get('/designers', [DesignerController::class, 'index'])->name('designers.index');
Route::get('/designers/{designer}', [DesignerController::class, 'show'])->name('designers.show');
Route::get('/printers', [PrinterController::class, 'index'])->name('printers.index');
Route::get('/printers/{printer}', [PrinterController::class, 'show'])->name('printers.show');

// Designer + printer own profile management (auth required).
Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/me/designer-profile', [DesignerController::class, 'store'])->name('me.designer-profile.store');
    Route::patch('/me/designer-profile', [DesignerController::class, 'updateMe'])->name('me.designer-profile.update');
    Route::delete('/me/designer-profile', [DesignerController::class, 'destroyMe'])->name('me.designer-profile.destroy');

    Route::post('/me/printer-profile', [PrinterController::class, 'store'])->name('me.printer-profile.store');
    Route::patch('/me/printer-profile', [PrinterController::class, 'updateMe'])->name('me.printer-profile.update');
    Route::delete('/me/printer-profile', [PrinterController::class, 'destroyMe'])->name('me.printer-profile.destroy');

    Route::get('/me/addresses', [AddressController::class, 'index'])->name('me.addresses.index');
    Route::post('/me/addresses', [AddressController::class, 'store'])->name('me.addresses.store');
    Route::get('/me/addresses/{address}', [AddressController::class, 'show'])->name('me.addresses.show');
    Route::patch('/me/addresses/{address}', [AddressController::class, 'update'])->name('me.addresses.update');
    Route::delete('/me/addresses/{address}', [AddressController::class, 'destroy'])->name('me.addresses.destroy');
});
