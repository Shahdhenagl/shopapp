<?php

declare(strict_types=1);

namespace App\Http\Middleware\Admin;

use App\Domain\Admin\Models\AdminUser;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the admin dashboard surface and resolves the tenant it operates on.
 *
 * The authenticated principal must be an AdminUser (the `abilities:admin` guard
 * already ensures the token carries the admin ability). Tenant admins and staff
 * are pinned to their own tenant; a super-admin (null tenant_id) may target a
 * specific tenant via the X-Tenant slug header, or leave the context unset to
 * work cross-tenant (e.g. the tenants list).
 */
final class EnsureAdminContext
{
    public function __construct(
        private readonly TenantContext $context,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user();

        if (! $admin instanceof AdminUser) {
            abort(403, __('api.forbidden'));
        }

        if ($admin->tenant_id !== null) {
            $this->context->setRequestTenant($admin->tenant);

            return $next($request);
        }

        $slug = trim((string) $request->header('X-Tenant'));

        if ($slug !== '') {
            $tenant = Tenant::query()->where('slug', $slug)->first();

            if ($tenant === null) {
                abort(404, __('api.not_found'));
            }

            $this->context->setRequestTenant($tenant);
        }

        return $next($request);
    }
}
