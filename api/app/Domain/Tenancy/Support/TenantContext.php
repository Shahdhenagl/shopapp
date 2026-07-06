<?php

declare(strict_types=1);

namespace App\Domain\Tenancy\Support;

use App\Domain\Tenancy\Models\Tenant;

/**
 * Holds the tenant resolved for the current request. Registered as a singleton
 * so the BelongsToTenant global scope and every repository read the same value.
 *
 * The tenant is set once per request by the ResolveTenant middleware (from the
 * X-Tenant header, the subdomain, or the configured default, §3). Outside an
 * HTTP request (console, seeders, factories) it lazily falls back to the
 * configured default tenant so maintenance work and tests still resolve a
 * tenant without extra wiring.
 *
 * It deliberately does NOT consult the authenticated user: the User model is
 * itself tenant-scoped, so reading Auth inside the scope would recurse while
 * Sanctum resolves the token. The request tenant is the single source of truth,
 * and an authenticated token only matches users within that same tenant.
 */
final class TenantContext
{
    private ?Tenant $requestTenant = null;

    private bool $defaultResolved = false;

    private ?Tenant $defaultTenant = null;

    public function setRequestTenant(?Tenant $tenant): void
    {
        $this->requestTenant = $tenant;
    }

    public function tenant(): ?Tenant
    {
        return $this->requestTenant ?? $this->defaultTenant();
    }

    public function id(): ?string
    {
        return $this->tenant()?->id;
    }

    public function hasTenant(): bool
    {
        return $this->tenant() !== null;
    }

    private function defaultTenant(): ?Tenant
    {
        if ($this->defaultResolved) {
            return $this->defaultTenant;
        }

        $slug = (string) config('tenant.default_slug', '');

        if ($slug === '') {
            return null;
        }

        $tenant = Tenant::query()->where('slug', $slug)->first();

        // Only cache a hit — caching a miss would poison later lookups once the
        // tenant is created within the same process (e.g. mid-seed).
        if ($tenant !== null) {
            $this->defaultResolved = true;
            $this->defaultTenant = $tenant;
        }

        return $tenant;
    }

    /**
     * Clear the cached default tenant (e.g. after seeding creates it within the
     * same process, so a later lookup sees the new row).
     */
    public function flush(): void
    {
        $this->defaultResolved = false;
        $this->defaultTenant = null;
    }
}
