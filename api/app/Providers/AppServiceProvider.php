<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Tenancy\Support\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One tenant context per request, shared by the global scope,
        // middleware and repositories.
        $this->app->singleton(TenantContext::class);
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        // Auth endpoints: 5 attempts / minute keyed by IP + email.
        RateLimiter::for('auth', function (Request $request): Limit {
            $email = (string) $request->input('email', '');

            return Limit::perMinute(5)->by(
                Str::lower($email).'|'.$request->ip()
            );
        });

        // Promo validation: 10 / minute / user.
        RateLimiter::for('promo', function (Request $request): Limit {
            return Limit::perMinute(10)->by(
                (string) ($request->user()?->getAuthIdentifier() ?? $request->ip())
            );
        });

        // Global API default.
        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute(60)->by(
            (string) ($request->user()?->getAuthIdentifier() ?? $request->ip())
        ));
    }
}
