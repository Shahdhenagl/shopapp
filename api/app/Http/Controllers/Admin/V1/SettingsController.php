<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1;

use App\Domain\Admin\Models\AdminUser;
use App\Domain\Tenancy\Actions\Admin\UpdateSettingsAction;
use App\Domain\Tenancy\Models\TenantSettings;
use App\Domain\Tenancy\Support\TenantContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\Settings\UpdateSettingsRequest;
use App\Http\Resources\Admin\AdminSettingsResource;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(
        private readonly UpdateSettingsAction $updateSettingsAction,
        private readonly TenantContext $tenantContext,
    ) {
    }

    public function show(Request $request): AdminSettingsResource
    {
        return AdminSettingsResource::make($this->currentSettings());
    }

    public function update(UpdateSettingsRequest $request): AdminSettingsResource
    {
        /** @var AdminUser $actor */
        $actor = $request->user();

        $settings = $this->updateSettingsAction->execute(
            $actor,
            $request->validated(),
            $request->boolean('force'),
        );

        return AdminSettingsResource::make($settings);
    }

    /**
     * The current tenant's settings row, created with sensible defaults on first
     * access so the dashboard always has something to render.
     */
    private function currentSettings(): TenantSettings
    {
        $tenant = $this->tenantContext->tenant();

        return TenantSettings::query()->firstOrCreate(
            ['tenant_id' => $tenant?->id],
            [
                'app_name' => $tenant?->name,
                'currency' => (string) config('app.currency', 'EGP'),
                'storefront_mode' => 'single',
                'shipping_fee' => 0,
            ],
        );
    }
}
