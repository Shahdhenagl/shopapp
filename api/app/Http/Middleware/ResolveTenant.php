<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the tenant for the request and seeds the TenantContext, so public
 * routes (catalog, settings) are tenant-scoped before auth runs. Precedence:
 *
 *   1. X-Tenant header (slug) — explicit override for first-party clients.
 *   2. Subdomain (acme.api.example.com -> "acme").
 *   3. Configured default tenant slug (single-tenant / current Flutter app,
 *      which sends no tenant today).
 *
 * Authenticated requests still prefer the token user's tenant (TenantContext),
 * so this only governs the unauthenticated surface.
 */
final class ResolveTenant
{
    public function __construct(
        private readonly TenantContext $context,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->fromHeader($request)
            ?? $this->fromSubdomain($request)
            ?? $this->default();

        $this->context->setRequestTenant($tenant);

        return $next($request);
    }

    private function fromHeader(Request $request): ?Tenant
    {
        $slug = trim((string) $request->header('X-Tenant'));

        return $slug === '' ? null : $this->bySlug($slug);
    }

    private function fromSubdomain(Request $request): ?Tenant
    {
        $host = $request->getHost();
        $baseHost = (string) config('tenant.base_host', '');

        if ($baseHost === '' || ! str_ends_with($host, '.'.$baseHost)) {
            return null;
        }

        $sub = substr($host, 0, -1 * (strlen($baseHost) + 1));

        if ($sub === '' || in_array($sub, ['www', 'api'], true)) {
            return null;
        }

        return $this->bySlug($sub);
    }

    private function default(): ?Tenant
    {
        $slug = (string) config('tenant.default_slug', '');

        return $slug === '' ? null : $this->bySlug($slug);
    }

    private function bySlug(string $slug): ?Tenant
    {
        return Tenant::query()->where('slug', $slug)->first();
    }
}
