<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Addresses\AddressController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Banners\BannerController;
use App\Http\Controllers\Api\V1\Cart\CartController;
use App\Http\Controllers\Api\V1\Catalog\CategoryController;
use App\Http\Controllers\Api\V1\Catalog\ProductController;
use App\Http\Controllers\Api\V1\Catalog\ReviewController;
use App\Http\Controllers\Api\V1\Checkout\CheckoutController;
use App\Http\Controllers\Api\V1\Favorites\FavoriteController;
use App\Http\Controllers\Api\V1\Notifications\DeviceController;
use App\Http\Controllers\Api\V1\Notifications\NotificationController;
use App\Http\Controllers\Api\V1\Profile\ProfileController;
use App\Http\Controllers\Api\V1\Settings\AppSettingsController;
use App\Http\Controllers\Api\V1\Catalog\SubCategoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    /*
    |--------------------------------------------------------------------------
    | Auth (public)
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function (): void {
        // Instant sign-up (returns a token pair; account starts unverified).
        Route::post('register', [AuthController::class, 'register'])
            ->middleware('throttle:auth');

        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:auth');

        // Social sign-in (Facebook / Google) — verifies the provider token.
        Route::post('social', [AuthController::class, 'social'])
            ->middleware('throttle:auth');

        Route::post('refresh', [AuthController::class, 'refresh'])
            ->middleware('throttle:auth');

        Route::prefix('password')->middleware('throttle:auth')->group(function (): void {
            Route::post('forgot', [PasswordResetController::class, 'forgot']);
            Route::post('verify', [PasswordResetController::class, 'verify']);
            Route::post('resend', [PasswordResetController::class, 'resend']);
            Route::post('reset', [PasswordResetController::class, 'reset']);
        });

        // Soft email verification (Bearer — user resolved from the token).
        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('email/verify/send', [AuthController::class, 'sendEmailVerification'])
                ->middleware('throttle:auth');
            Route::post('email/verify', [AuthController::class, 'verifyEmail']);

            Route::post('logout', [AuthController::class, 'logout']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Storefront config & catalog (public)
    |--------------------------------------------------------------------------
    */
    Route::get('settings/app', [AppSettingsController::class, 'show']);
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('sub-categories', [SubCategoryController::class, 'index']);
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{id}', [ProductController::class, 'show']);
    Route::get('products/{id}/reviews', [ReviewController::class, 'index']);
    Route::get('home/banners', [BannerController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | Authenticated (bearer)
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function (): void {
        // Cart
        Route::get('cart', [CartController::class, 'show']);
        Route::post('cart', [CartController::class, 'store']);
        Route::patch('cart/{lineId}', [CartController::class, 'update'])
            ->where('lineId', '.*');
        Route::delete('cart/{lineId}', [CartController::class, 'destroy'])
            ->where('lineId', '.*');
        Route::delete('cart', [CartController::class, 'clear']);
        Route::post('cart/promo', [CartController::class, 'promo'])
            ->middleware('throttle:promo');

        // Favorites
        Route::get('favorites', [FavoriteController::class, 'index']);
        Route::post('favorites', [FavoriteController::class, 'toggle']);
        Route::delete('favorites', [FavoriteController::class, 'clear']);

        // Addresses (delivery address book)
        Route::get('addresses', [AddressController::class, 'index']);
        Route::post('addresses', [AddressController::class, 'store']);
        Route::patch('addresses/{id}', [AddressController::class, 'update']);
        Route::delete('addresses/{id}', [AddressController::class, 'destroy']);
        Route::post('addresses/{id}/default', [AddressController::class, 'default']);

        // Product reviews (write is auth-only; read is public above)
        Route::post('products/{id}/reviews', [ReviewController::class, 'store']);

        // Checkout
        Route::post('checkout', [CheckoutController::class, 'store']);

        // Notifications
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::post('notifications/read', [NotificationController::class, 'read']);
        Route::get('notifications/count', [NotificationController::class, 'count']);
        Route::post('notifications/devices', [DeviceController::class, 'store']);
        Route::delete('notifications/devices', [DeviceController::class, 'destroy']);

        // Profile
        Route::get('me', [ProfileController::class, 'show']);
        Route::patch('me', [ProfileController::class, 'update']);
        Route::post('me/avatar', [ProfileController::class, 'updateAvatar']);
        Route::get('me/orders', [ProfileController::class, 'orders']);
    });
});
