<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers;

use App\Domain\Addresses\Contracts\AddressRepositoryInterface;
use App\Domain\Auth\Contracts\AdminCustomerRepositoryInterface;
use App\Domain\Auth\Contracts\OtpStore;
use App\Domain\Auth\Contracts\RefreshTokenStore;
use App\Domain\Auth\Contracts\SocialTokenVerifier;
use App\Domain\Auth\Contracts\UserRepositoryInterface;
use App\Domain\Banners\Contracts\AdminBannerRepositoryInterface;
use App\Domain\Banners\Contracts\BannerRepositoryInterface;
use App\Domain\Cart\Contracts\AdminPromoRepositoryInterface;
use App\Domain\Cart\Contracts\CartRepositoryInterface;
use App\Domain\Cart\Contracts\PromoRepositoryInterface;
use App\Domain\Catalog\Contracts\AdminCategoryRepositoryInterface;
use App\Domain\Catalog\Contracts\AdminProductRepositoryInterface;
use App\Domain\Catalog\Contracts\AdminReviewRepositoryInterface;
use App\Domain\Catalog\Contracts\CategoryRepositoryInterface;
use App\Domain\Catalog\Contracts\ProductRepositoryInterface;
use App\Domain\Catalog\Contracts\ReviewRepositoryInterface;
use App\Domain\Checkout\Contracts\AdminOrderRepositoryInterface;
use App\Domain\Checkout\Contracts\OrderRepositoryInterface;
use App\Domain\Checkout\Support\PaymentProcessorFactory;
use App\Domain\Favorites\Contracts\FavoriteRepositoryInterface;
use App\Domain\Notifications\Contracts\AdminNotificationRepositoryInterface;
use App\Domain\Notifications\Contracts\NotificationRepositoryInterface;
use App\Infrastructure\Auth\DatabaseRefreshTokenStore;
use App\Infrastructure\Auth\HttpSocialTokenVerifier;
use App\Infrastructure\Otp\DatabaseOtpStore;
use App\Infrastructure\Persistence\Eloquent\EloquentAddressRepository;
use App\Infrastructure\Payment\CardPaymentProcessor;
use App\Infrastructure\Payment\CashPaymentProcessor;
use App\Infrastructure\Persistence\Eloquent\EloquentAdminBannerRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentAdminCategoryRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentAdminCustomerRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentAdminNotificationRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentAdminOrderRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentAdminProductRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentAdminPromoRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentAdminReviewRepository;
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
        $this->app->bind(SocialTokenVerifier::class, HttpSocialTokenVerifier::class);
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
        $this->app->bind(AdminPromoRepositoryInterface::class, EloquentAdminPromoRepository::class);
        $this->app->bind(AdminBannerRepositoryInterface::class, EloquentAdminBannerRepository::class);
        $this->app->bind(AdminOrderRepositoryInterface::class, EloquentAdminOrderRepository::class);
        $this->app->bind(AdminReviewRepositoryInterface::class, EloquentAdminReviewRepository::class);
        $this->app->bind(AdminNotificationRepositoryInterface::class, EloquentAdminNotificationRepository::class);
        $this->app->bind(AdminCustomerRepositoryInterface::class, EloquentAdminCustomerRepository::class);

        // Payment processors — adding a provider is a new class + a tag entry,
        // with no change to the checkout action (OCP).
        $this->app->tag([CashPaymentProcessor::class, CardPaymentProcessor::class], 'payment.processors');
        $this->app->bind(
            PaymentProcessorFactory::class,
            fn ($app): PaymentProcessorFactory => new PaymentProcessorFactory($app->tagged('payment.processors')),
        );
    }
}
