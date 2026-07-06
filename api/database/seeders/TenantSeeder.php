<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\TenantSettings;
use Illuminate\Database\Seeder;

/**
 * Creates the default tenant + its white-label settings (the single store for a
 * standalone deployment, and the tenant every other seeder writes into).
 */
final class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $slug = (string) config('tenant.default_slug', 'modist');

        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'name' => 'MODIST',
                'status' => Tenant::STATUS_ACTIVE,
            ],
        );

        TenantSettings::query()->updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'app_name' => 'MODIST',
                'currency' => 'EGP',
                'brand_primary' => '#0E0E0E',
                'brand_on_primary' => '#FFFFFF',
                'brand_accent' => '#1F8A5B',
                'flags' => [
                    'card_payment' => true,
                    'cash_payment' => true,
                    'promo_codes' => true,
                    'favorites' => true,
                ],
            ],
        );
    }
}
