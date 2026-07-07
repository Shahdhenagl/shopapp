<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\V1\Auth\AdminAuthController;
use App\Http\Controllers\Admin\V1\CategoryController;
use App\Http\Controllers\Admin\V1\MediaController;
use App\Http\Controllers\Admin\V1\ProductController;
use App\Http\Controllers\Admin\V1\SettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin API (/api/admin/v1)
|--------------------------------------------------------------------------
| Dashboard control plane. Admin tokens carry the `admin` ability; the
| `admin` middleware sets the tenant context from the admin user (or the
| X-Tenant header for super-admins) so every query is tenant-scoped.
*/

Route::prefix('admin/v1')->group(function (): void {
    // Public
    Route::post('auth/login', [AdminAuthController::class, 'login'])
        ->middleware('throttle:auth');

    // Authenticated admin surface
    Route::middleware(['auth:admin', 'abilities:admin', 'admin'])->group(function (): void {
        Route::post('auth/logout', [AdminAuthController::class, 'logout']);
        Route::get('me', [AdminAuthController::class, 'me']);

        // Media library
        Route::post('media', [MediaController::class, 'store']);

        // Store settings (drives GET /settings/app)
        Route::get('settings', [SettingsController::class, 'show']);
        Route::patch('settings', [SettingsController::class, 'update']);

        // Categories tree
        Route::get('categories', [CategoryController::class, 'index']);
        Route::post('categories', [CategoryController::class, 'store']);
        Route::patch('categories/{id}', [CategoryController::class, 'update']);
        Route::delete('categories/{id}', [CategoryController::class, 'destroy']);

        // Products
        Route::get('products', [ProductController::class, 'index']);
        Route::post('products', [ProductController::class, 'store']);
        Route::get('products/{id}', [ProductController::class, 'show']);
        Route::patch('products/{id}', [ProductController::class, 'update']);
        Route::delete('products/{id}', [ProductController::class, 'destroy']);
    });
});
