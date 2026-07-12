<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\V1\Auth\AdminAuthController;
use App\Http\Controllers\Admin\V1\BannerController;
use App\Http\Controllers\Admin\V1\CategoryController;
use App\Http\Controllers\Admin\V1\CustomerController;
use App\Http\Controllers\Admin\V1\InventoryController;
use App\Http\Controllers\Admin\V1\MediaController;
use App\Http\Controllers\Admin\V1\MetricsController;
use App\Http\Controllers\Admin\V1\NotificationController;
use App\Http\Controllers\Admin\V1\OrderController;
use App\Http\Controllers\Admin\V1\ProductController;
use App\Http\Controllers\Admin\V1\PromoController;
use App\Http\Controllers\Admin\V1\ReviewController;
use App\Http\Controllers\Admin\V1\SettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin API (/api/admin/v1)
|--------------------------------------------------------------------------
| Dashboard control plane. Admin tokens carry the `admin` ability; the
| `admin` middleware sets the tenant context from the admin user (or the
| X-Tenant header for super-admins) so every query is tenant-scoped.
|
| RBAC: a super-admin bypasses every role gate. `admin.role:tenant-admin`
| restricts merchandising/config writes to store owners; the Orders module is
| left open to Staff (fulfilment) as well. Reads stay ungated for any admin.
*/

Route::prefix('admin/v1')->group(function (): void {
    // Public
    Route::post('auth/login', [AdminAuthController::class, 'login'])
        ->middleware('throttle:auth');

    // Authenticated admin surface
    Route::middleware(['auth:admin', 'abilities:admin', 'admin'])->group(function (): void {
        Route::post('auth/logout', [AdminAuthController::class, 'logout']);
        Route::get('me', [AdminAuthController::class, 'me']);

        // Dashboard KPIs
        Route::get('metrics', [MetricsController::class, 'index']);

        // Media library
        Route::post('media', [MediaController::class, 'store']);

        // Orders — fulfilment surface, open to Staff as well as admins.
        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/{id}', [OrderController::class, 'show']);
        Route::patch('orders/{id}', [OrderController::class, 'update']);

        // Inventory (low-stock visibility)
        Route::get('inventory/low-stock', [InventoryController::class, 'lowStock']);

        // Reads available to any admin.
        Route::get('settings', [SettingsController::class, 'show']);
        Route::get('categories', [CategoryController::class, 'index']);
        Route::get('products', [ProductController::class, 'index']);
        Route::get('products/{id}', [ProductController::class, 'show']);
        Route::get('promos', [PromoController::class, 'index']);
        Route::get('banners', [BannerController::class, 'index']);
        Route::get('reviews', [ReviewController::class, 'index']);
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::get('customers', [CustomerController::class, 'index']);
        Route::get('customers/{id}', [CustomerController::class, 'show']);

        // Merchandising & configuration writes — store owners (tenant-admin) only.
        Route::middleware('admin.role:tenant-admin')->group(function (): void {
            // Store settings (drives GET /settings/app)
            Route::patch('settings', [SettingsController::class, 'update']);

            // Categories tree
            Route::post('categories', [CategoryController::class, 'store']);
            Route::patch('categories/{id}', [CategoryController::class, 'update']);
            Route::delete('categories/{id}', [CategoryController::class, 'destroy']);

            // Products
            Route::post('products', [ProductController::class, 'store']);
            Route::patch('products/{id}', [ProductController::class, 'update']);
            Route::delete('products/{id}', [ProductController::class, 'destroy']);

            // Promotions
            Route::post('promos', [PromoController::class, 'store']);
            Route::patch('promos/{id}', [PromoController::class, 'update']);
            Route::delete('promos/{id}', [PromoController::class, 'destroy']);

            // Banners (deep-link targets validated server-side)
            Route::post('banners', [BannerController::class, 'store']);
            Route::patch('banners/{id}', [BannerController::class, 'update']);
            Route::delete('banners/{id}', [BannerController::class, 'destroy']);

            // Reviews moderation
            Route::patch('reviews/{id}', [ReviewController::class, 'update']);
            Route::delete('reviews/{id}', [ReviewController::class, 'destroy']);

            // Notifications broadcast
            Route::post('notifications', [NotificationController::class, 'store']);

            // Customers (create / suspend / reactivate)
            Route::post('customers', [CustomerController::class, 'store']);
            Route::patch('customers/{id}', [CustomerController::class, 'update']);
        });
    });
});
