<?php

declare(strict_types=1);

namespace App\Domain\Tenancy\Actions;

use App\Domain\Tenancy\Models\TenantSettings;
use App\Domain\Tenancy\Support\TenantContext;

/**
 * Resolves the current tenant's settings row (or null when none exists yet),
 * keeping the controller thin and consistent with the other modules' Actions.
 */
final readonly class ShowAppSettingsAction
{
    public function __construct(
        private TenantContext $tenants,
    ) {
    }

    public function execute(): ?TenantSettings
    {
        return $this->tenants->tenant()?->settings;
    }
}
