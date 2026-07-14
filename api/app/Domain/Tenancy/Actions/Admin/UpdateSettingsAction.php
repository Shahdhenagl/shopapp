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

        if (array_key_exists('home_rail_categories', $data)) {
            $data['home_rail_categories'] = $this->sanitizeRailCategories($data['home_rail_categories']);
        }

        if (array_key_exists('max_home_rails', $data)) {
            $data['max_home_rails'] = max(0, min(20, (int) $data['max_home_rails']));
        }

        if (array_key_exists('home_rail_item_count', $data)) {
            $data['home_rail_item_count'] = max(1, min(20, (int) $data['home_rail_item_count']));
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

    /**
     * Normalize the promoted-category list: keep only ids (slugs) that still
     * exist in the tenant's catalog (GET /categories), drop empties and
     * duplicates, and preserve the submitted order (display order on Home).
     *
     * @param  mixed  $value
     * @return list<string>
     */
    private function sanitizeRailCategories(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        /** @var array<int, string> $known */
        $known = Category::query()->pluck('slug')->all();
        $known = array_flip($known);

        $seen = [];
        $out = [];

        foreach ($value as $id) {
            if (! is_string($id) || $id === '' || isset($seen[$id]) || ! isset($known[$id])) {
                continue;
            }

            $seen[$id] = true;
            $out[] = $id;
        }

        return $out;
    }
}
