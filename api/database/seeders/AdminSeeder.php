<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Admin\Models\AdminUser;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds a super-admin (SaaS owner) and a tenant-admin for the default store, so
 * the dashboard can be logged into immediately. Change these passwords in prod.
 */
final class AdminSeeder extends Seeder
{
    public function run(): void
    {
        AdminUser::query()->updateOrCreate(
            ['email' => 'superadmin@modist.test'],
            [
                'tenant_id' => null,
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => AdminUser::ROLE_SUPER_ADMIN,
            ],
        );

        $tenant = Tenant::query()
            ->where('slug', (string) config('tenant.default_slug', 'modist'))
            ->first();

        if ($tenant !== null) {
            AdminUser::query()->updateOrCreate(
                ['email' => 'admin@modist.test'],
                [
                    'tenant_id' => $tenant->id,
                    'name' => 'MODIST Admin',
                    'password' => Hash::make('password'),
                    'role' => AdminUser::ROLE_TENANT_ADMIN,
                ],
            );
        }
    }
}
