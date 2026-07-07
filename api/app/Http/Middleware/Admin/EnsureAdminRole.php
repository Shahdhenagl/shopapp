<?php

declare(strict_types=1);

namespace App\Http\Middleware\Admin;

use App\Domain\Admin\Models\AdminUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role gate for admin routes. A super-admin bypasses every gate; any other
 * admin must hold one of the listed roles or the request is forbidden.
 */
final class EnsureAdminRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        /** @var AdminUser $admin */
        $admin = $request->user();

        if ($admin->isSuperAdmin()) {
            return $next($request);
        }

        if (! $admin->hasRole(...$roles)) {
            abort(403, __('api.forbidden'));
        }

        return $next($request);
    }
}
