<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DesignController;
use App\Http\Controllers\Api\DesignerController;
use App\Http\Controllers\Api\DesignProductMappingController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\PrinterController;
use App\Http\Controllers\Api\ProductTemplateController;
use App\Http\Controllers\Api\ProductVariantController;
use App\Http\Controllers\Api\TagController;
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

// Design product mappings (catalog) — public reads.
Route::get('/mappings', [DesignProductMappingController::class, 'index'])->name('mappings.index');
Route::get('/mappings/{mapping}', [DesignProductMappingController::class, 'show'])->name('mappings.show');

// Catalog — Categories (public reads, admin writes).
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('categories.show');

// Catalog — Tags (public reads).
Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
Route::get('/tags/{tag}', [TagController::class, 'show'])->name('tags.show');

// Catalog — Designs (public reads).
Route::get('/designs', [DesignController::class, 'index'])->name('designs.index');
Route::get('/designs/{design}', [DesignController::class, 'show'])->name('designs.show');

// Catalog — Templates (public reads).
Route::get('/templates', [ProductTemplateController::class, 'index'])->name('templates.index');
Route::get('/templates/{template}', [ProductTemplateController::class, 'show'])->name('templates.show');
Route::get('/templates/{template}/variants', [ProductVariantController::class, 'index'])->name('templates.variants.index');

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

    Route::post('/me/mappings', [DesignProductMappingController::class, 'store'])->name('me.mappings.store');
    Route::patch('/me/mappings/{mapping}', [DesignProductMappingController::class, 'update'])->name('me.mappings.update');
    Route::delete('/me/mappings/{mapping}', [DesignProductMappingController::class, 'destroy'])->name('me.mappings.destroy');

    Route::post('/me/designs', [DesignController::class, 'store'])->name('me.designs.store');
    Route::patch('/me/designs/{design}', [DesignController::class, 'updateMe'])->name('me.designs.update');
    Route::delete('/me/designs/{design}', [DesignController::class, 'destroyMe'])->name('me.designs.destroy');

    Route::post('/me/templates', [ProductTemplateController::class, 'store'])->name('me.templates.store');
    Route::patch('/me/templates/{template}', [ProductTemplateController::class, 'updateMe'])->name('me.templates.update');
    Route::delete('/me/templates/{template}', [ProductTemplateController::class, 'destroyMe'])->name('me.templates.destroy');

    Route::post('/me/templates/{template}/variants', [ProductVariantController::class, 'store'])->name('me.variants.store');
    Route::patch('/me/variants/{variant}', [ProductVariantController::class, 'update'])->name('me.variants.update');
    Route::delete('/me/variants/{variant}', [ProductVariantController::class, 'destroy'])->name('me.variants.destroy');

    Route::get('/me/cart', [CartController::class, 'index'])->name('me.cart.index');
    Route::post('/me/cart/items', [CartController::class, 'store'])->name('me.cart.items.store');
    Route::patch('/me/cart/items/{item}', [CartController::class, 'update'])->name('me.cart.items.update');
    Route::delete('/me/cart/items/{item}', [CartController::class, 'destroy'])->name('me.cart.items.destroy');
    Route::delete('/me/cart', [CartController::class, 'clear'])->name('me.cart.clear');
});

// Admin — Categories and Tags.
Route::middleware(['auth:sanctum', 'role:admin'])->group(function (): void {
    Route::post('/admin/categories', [CategoryController::class, 'store'])->name('admin.categories.store');
    Route::patch('/admin/categories/{category}', [CategoryController::class, 'update'])->name('admin.categories.update');
    Route::delete('/admin/categories/{category}', [CategoryController::class, 'destroy'])->name('admin.categories.destroy');

    Route::post('/admin/tags', [TagController::class, 'store'])->name('admin.tags.store');
    Route::patch('/admin/tags/{tag}', [TagController::class, 'update'])->name('admin.tags.update');
    Route::delete('/admin/tags/{tag}', [TagController::class, 'destroy'])->name('admin.tags.destroy');

    Route::post('/admin/designs/{design}/transfer', [DesignController::class, 'transfer'])->name('admin.designs.transfer');
});
