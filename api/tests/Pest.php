<?php

declare(strict_types=1);

use App\Domain\Admin\Models\AdminUser;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\TenantSettings;
use App\Domain\Tenancy\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)
    ->beforeEach(function (): void {
        // Every feature test runs against the default tenant: create it (so the
        // ResolveTenant middleware finds it on public routes) and bind it as the
        // active tenant (so factory-built records get the right tenant_id).
        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => (string) config('tenant.default_slug', 'modist')],
            ['name' => 'MODIST', 'status' => Tenant::STATUS_ACTIVE],
        );

        TenantSettings::query()->firstOrCreate(
            ['tenant_id' => $tenant->id],
            ['app_name' => 'MODIST', 'currency' => 'EGP'],
        );

        app(TenantContext::class)->setRequestTenant($tenant);
    })
    ->in('Feature');

uses(TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Admin (dashboard) test helpers
|--------------------------------------------------------------------------
| The admin API sits under /api/admin/v1, is guarded by the `admin` Sanctum
| guard + the `admin` token ability, and pins the tenant from the AdminUser
| (or X-Tenant for super-admins). These helpers mint operators and the Bearer
| headers their requests need.
*/

/**
 * Create a dashboard operator. Tenant-admin/staff are pinned to the currently
 * bound tenant; a super-admin has a null tenant_id (acts across tenants).
 */
function makeAdmin(string $role = AdminUser::ROLE_TENANT_ADMIN, ?Tenant $tenant = null): AdminUser
{
    $tenant ??= app(TenantContext::class)->tenant();

    return AdminUser::query()->create([
        'tenant_id' => $role === AdminUser::ROLE_SUPER_ADMIN ? null : $tenant?->id,
        'name' => 'Admin '.$role,
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password', // hashed by the model cast
        'role' => $role,
    ]);
}

/**
 * Bearer headers for an admin token carrying the `admin` ability. Pass abilities
 * = [] to simulate a token missing the ability (should be rejected 403).
 *
 * @param  list<string>  $abilities
 * @param  array<string, string>  $extra
 * @return array<string, string>
 */
function adminHeaders(?AdminUser $admin = null, array $abilities = ['admin'], array $extra = []): array
{
    $admin ??= makeAdmin();
    $token = $admin->createToken('test', $abilities)->plainTextToken;

    return array_merge([
        'Accept' => 'application/json',
        'Authorization' => 'Bearer '.$token,
    ], $extra);
}

/**
 * A second, isolated tenant (with its own settings row) for cross-tenant
 * isolation assertions.
 */
function makeOtherTenant(string $slug = 'other-store'): Tenant
{
    $tenant = Tenant::query()->firstOrCreate(
        ['slug' => $slug],
        ['name' => 'Other Store', 'status' => Tenant::STATUS_ACTIVE],
    );

    TenantSettings::query()->firstOrCreate(
        ['tenant_id' => $tenant->id],
        ['app_name' => 'Other', 'currency' => 'EGP'],
    );

    return $tenant;
}

/**
 * Run a callback with a different tenant bound as active (e.g. to factory-build
 * rows owned by another tenant), then restore the original binding.
 */
function withTenant(Tenant $tenant, Closure $callback): mixed
{
    $context = app(TenantContext::class);
    $previous = $context->tenant();
    $context->setRequestTenant($tenant);

    try {
        return $callback();
    } finally {
        $context->setRequestTenant($previous);
    }
}
