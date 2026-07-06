<?php

declare(strict_types=1);

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
