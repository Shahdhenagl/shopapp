<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers;

use App\Domain\Addresses\Contracts\AddressRepositoryInterface;
use App\Domain\Auth\Contracts\OtpStore;
use App\Domain\Auth\Contracts\RefreshTokenStore;
use App\Domain\Auth\Contracts\UserRepositoryInterface;
use App\Domain\Banners\Contracts\BannerRepositoryInterface;
use App\Domain\Cart\Contracts\CartRepositoryInterface;
use App\Domain\Cart\Contracts\PromoRepositoryInterface;
use App\Domain\Catalog\Contracts\AdminCategoryRepositoryInterface;
use App\Domain\Catalog\Contracts\AdminProductRepositoryInterface;
use App\Domain\Catalog\Contracts\CategoryRepositoryInterface;
use App\Domain\Catalog\Contracts\ProductRepositoryInterface;
use App\Domain\Catalog\Contracts\ReviewRepositoryInterface;
use App\Domain\Checkout\Contracts\OrderRepositoryInterface;
use App\Domain\Checkout\Support\PaymentProcessorFactory;
use App\Domain\Favorites\Contracts\FavoriteRepositoryInterface;
use App\Domain\Notifications\Contracts\NotificationRepositoryInterface;
use App\Infrastructure\Auth\DatabaseRefreshTokenStore;
use App\Infrastructure\Otp\DatabaseOtpStore;
use App\Infrastructure\Persistence\Eloquent\EloquentAddressRepository;
use App\Infrastructure\Payment\CardPaymentProcessor;
use App\Infrastructure\Payment\CashPaymentProcessor;
use App\Infrastructure\Persistence\Eloquent\EloquentAdminCategoryRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentAdminProductRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentBannerRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentCartRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentCategoryRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentFavoriteRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentNotificationRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentOrderRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentProductRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentPromoRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentReviewRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentUserRepository;
use Illuminate\Support\ServiceProvider;

final class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(OtpStore::class, DatabaseOtpStore::class);
        $this->app->bind(RefreshTokenStore::class, DatabaseRefreshTokenStore::class);
        $this->app->bind(ProductRepositoryInterface::class, EloquentProductRepository::class);
        $this->app->bind(ReviewRepositoryInterface::class, EloquentReviewRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, EloquentCategoryRepository::class);
        $this->app->bind(BannerRepositoryInterface::class, EloquentBannerRepository::class);
        $this->app->bind(CartRepositoryInterface::class, EloquentCartRepository::class);
        $this->app->bind(PromoRepositoryInterface::class, EloquentPromoRepository::class);
        $this->app->bind(FavoriteRepositoryInterface::class, EloquentFavoriteRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, EloquentOrderRepository::class);
        $this->app->bind(NotificationRepositoryInterface::class, EloquentNotificationRepository::class);
        $this->app->bind(AddressRepositoryInterface::class, EloquentAddressRepository::class);

        // Admin (dashboard) repositories.
        $this->app->bind(AdminCategoryRepositoryInterface::class, EloquentAdminCategoryRepository::class);
        $this->app->bind(AdminProductRepositoryInterface::class, EloquentAdminProductRepository::class);

        // Payment processors — adding a provider is a new class + a tag entry,
        // with no change to the checkout action (OCP).
        $this->app->tag([CashPaymentProcessor::class, CardPaymentProcessor::class], 'payment.processors');
        $this->app->bind(
            PaymentProcessorFactory::class,
            fn ($app): PaymentProcessorFactory => new PaymentProcessorFactory($app->tagged('payment.processors')),
        );
    }
}
