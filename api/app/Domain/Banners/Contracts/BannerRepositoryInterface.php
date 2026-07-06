<?php

declare(strict_types=1);

namespace App\Domain\Banners\Contracts;

use App\Domain\Banners\Models\Banner;
use Illuminate\Database\Eloquent\Collection;

interface BannerRepositoryInterface
{
    /**
     * Active, in-window banners for the current tenant, ordered by sort_order
     * (BACKEND.md §6.9). Rows without an image_url are excluded — the client
     * drops them anyway.
     *
     * @return Collection<int, Banner>
     */
    public function active(): Collection;
}
