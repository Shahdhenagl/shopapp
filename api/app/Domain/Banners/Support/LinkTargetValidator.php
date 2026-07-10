<?php

declare(strict_types=1);

namespace App\Domain\Banners\Support;

use App\Domain\Banners\Exceptions\BannerLinkInvalidException;
use App\Domain\Banners\Models\Banner;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;

/**
 * §7.3 — resolves and validates a deep-link target so a banner (or any linked
 * content) can never point at a category/product that doesn't exist in the
 * tenant. Queries run through the tenant-scoped models, so cross-tenant targets
 * are rejected automatically.
 */
final class LinkTargetValidator
{
    /**
     * @throws BannerLinkInvalidException when the target is missing or dangling
     */
    public function assertValid(string $linkType, ?string $linkValue): void
    {
        if ($linkType === Banner::LINK_NONE) {
            return;
        }

        $value = $linkValue !== null ? trim($linkValue) : '';

        if ($value === '') {
            throw new BannerLinkInvalidException('api.banner_link_missing');
        }

        match ($linkType) {
            // Categories are addressed by their per-tenant slug (the app's wire id).
            Banner::LINK_CATEGORY => $this->assertCategoryExists($value),
            Banner::LINK_PRODUCT => $this->assertProductExists($value),
            // URLs are format-checked in the FormRequest; nothing to resolve.
            Banner::LINK_URL => null,
            default => throw new BannerLinkInvalidException,
        };
    }

    private function assertCategoryExists(string $slug): void
    {
        $exists = Category::query()
            ->where(fn ($q) => $q->where('slug', $slug)->orWhere('id', $slug))
            ->exists();

        if (! $exists) {
            throw new BannerLinkInvalidException;
        }
    }

    private function assertProductExists(string $id): void
    {
        if (! Product::query()->whereKey($id)->exists()) {
            throw new BannerLinkInvalidException;
        }
    }
}
