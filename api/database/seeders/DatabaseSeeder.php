<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Support\TenantContext;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Provision the default tenant first, then bind it as the active tenant
        // so every subsequent seeder's records are stamped with its tenant_id
        // (via the BelongsToTenant auto-fill) and tenant-scoped lookups resolve.
        $this->call(TenantSeeder::class);

        $slug = (string) config('tenant.default_slug', 'modist');
        $tenant = Tenant::query()->where('slug', $slug)->first();

        app(TenantContext::class)->setRequestTenant($tenant);

        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            ReviewSeeder::class,
            PromoSeeder::class,
            BannerSeeder::class,
        ]);
    }
}
