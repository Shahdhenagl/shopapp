<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The dashboard-facing shape of a tenant's settings: raw columns plus the
 * grouped `brand` and `flags` objects the admin UI edits. Distinct from the
 * app-facing storefront config resource.
 */
class AdminSettingsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, bool> $flags */
        $flags = $this->flags ?? [];

        return [
            'app_name' => $this->app_name,
            'currency' => $this->currency,
            'storefront_mode' => $this->storefront_mode,
            'logo_url' => $this->logo_url,
            'shipping_fee' => (float) $this->shipping_fee,
            'brand' => [
                'primary' => $this->brand_primary,
                'on_primary' => $this->brand_on_primary,
                'accent' => $this->brand_accent,
            ],
            'flags' => [
                'card_payment' => (bool) ($flags['card_payment'] ?? true),
                'cash_payment' => (bool) ($flags['cash_payment'] ?? true),
                'promo_codes' => (bool) ($flags['promo_codes'] ?? true),
                'favorites' => (bool) ($flags['favorites'] ?? true),
            ],
            // Dashboard-curated Home rails (§5): ordered category ids + caps.
            'home_rail_categories' => array_values(array_filter(
                (array) ($this->home_rail_categories ?? []),
                static fn ($id): bool => is_string($id) && $id !== '',
            )),
            'max_home_rails' => (int) ($this->max_home_rails ?? 8),
            'home_rail_item_count' => (int) ($this->home_rail_item_count ?? 5),
        ];
    }
}
