<?php

declare(strict_types=1);

namespace App\Domain\Tenancy\Actions\Admin;

use App\Domain\Admin\Models\AdminUser;
use App\Domain\Admin\Support\AuditLogger;
use App\Domain\Catalog\Exceptions\MultiDepartmentRequiresTreeException;
use App\Domain\Catalog\Models\Category;
use App\Domain\Tenancy\Models\TenantSettings;
use App\Domain\Tenancy\Support\TenantContext;

final readonly class UpdateSettingsAction
{
    public function __construct(
        private TenantContext $tenantContext,
        private AuditLogger $audit,
    ) {
    }

    /**
     * Persists the current tenant's settings. Enforces §7.2: switching to
     * `multi_department` requires an existing category tree (at least one
     * category with a parent) unless $force is set. Records before/after in the
     * audit trail.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(AdminUser $actor, array $data, bool $force = false): TenantSettings
    {
        if (($data['storefront_mode'] ?? null) === 'multi_department' && ! $force) {
            $hasTree = Category::query()->whereNotNull('parent_id')->exists();

            if (! $hasTree) {
                throw new MultiDepartmentRequiresTreeException;
            }
        }

        $tenantId = $this->tenantContext->id();

        $existing = TenantSettings::query()->where('tenant_id', $tenantId)->first();
        $before = $existing?->toArray();

        $settings = TenantSettings::query()->updateOrCreate(
            ['tenant_id' => $tenantId],
            $data,
        );

        $this->audit->log($actor, 'settings.updated', $settings, $before, $settings->toArray());

        return $settings;
    }
}
