<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Tenancy\Models\TenantSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * White-label theming payload for the current tenant. The underlying resource
 * may be null (tenant has no settings row yet), so every field reads through
 * `?->` and falls back to config defaults.
 *
 * @property-read TenantSettings|null $resource
 */
class AppSettingsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var TenantSettings|null $settings */
        $settings = $this->resource;

        return [
            'app_name' => $settings?->app_name ?? config('app.name'),
            'currency' => $settings?->currency ?? config('app.currency', 'EGP'),
            // Reshapes the app navigation: 'single' (flat fashion catalog) or
            // 'multi_department' (department rail + Category browse).
            'storefront_mode' => $settings?->storefront_mode ?? 'single',
            'logo_url' => $settings?->logo_url,
            'shipping_fee' => (int) round((float) ($settings?->shipping_fee ?? 0)),
            'brand' => $this->brand($settings),
            'flags' => $this->flags($settings),
            // Dashboard-curated Home rails: ordered category ids the client
            // resolves against its already-loaded catalog. Absent/empty → no
            // rails (safe default). Clamps mirror the client contract (§6).
            'home_rail_categories' => $this->railCategories($settings),
            'max_home_rails' => max(0, min(20, (int) ($settings?->max_home_rails ?? 8))),
            'home_rail_item_count' => max(1, min(20, (int) ($settings?->home_rail_item_count ?? 5))),
        ];
    }

    /**
     * Ordered, de-duplicated list of promoted category ids (slugs). Empties and
     * non-strings are dropped; order is preserved (jsonb array, not a set).
     *
     * @return list<string>
     */
    private function railCategories(?TenantSettings $settings): array
    {
        /** @var array<int, mixed> $ids */
        $ids = (array) ($settings?->home_rail_categories ?? []);

        $seen = [];
        $out = [];

        foreach ($ids as $id) {
            if (! is_string($id) || $id === '' || isset($seen[$id])) {
                continue;
            }

            $seen[$id] = true;
            $out[] = $id;
        }

        return $out;
    }

    /**
     * Only include the colours that are set (non-null). Omitted colours fall
     * back to the client's built-in neutrals.
     *
     * @return array<string, string>
     */
    private function brand(?TenantSettings $settings): array
    {
        $brand = [];

        if ($settings?->brand_primary !== null) {
            $brand['primary'] = (string) $settings->brand_primary;
        }

        if ($settings?->brand_on_primary !== null) {
            $brand['on_primary'] = (string) $settings->brand_on_primary;
        }

        if ($settings?->brand_accent !== null) {
            $brand['accent'] = (string) $settings->brand_accent;
        }

        return $brand;
    }

    /**
     * Merge stored flags over the defaults (any omitted flag defaults true).
     *
     * @return array<string, bool>
     */
    private function flags(?TenantSettings $settings): array
    {
        $defaults = [
            'card_payment' => true,
            'cash_payment' => true,
            'promo_codes' => true,
            'favorites' => true,
        ];

        /** @var array<string, mixed> $stored */
        $stored = $settings?->flags ?? [];

        $flags = $defaults;

        foreach ($stored as $key => $value) {
            $flags[$key] = (bool) $value;
        }

        return $flags;
    }
}
