<?php

declare(strict_types=1);

namespace App\Domain\Banners\Contracts;

use App\Domain\Banners\Models\Banner;
use Illuminate\Database\Eloquent\Collection;

interface AdminBannerRepositoryInterface
{
    /**
     * All banners for the current tenant, in display order.
     *
     * @return Collection<int, Banner>
     */
    public function all(): Collection;

    public function find(string $id): ?Banner;

    /**
     * @param  array<string, mixed>  $attrs
     */
    public function create(array $attrs): Banner;

    /**
     * @param  array<string, mixed>  $attrs
     */
    public function update(Banner $banner, array $attrs): Banner;

    public function delete(Banner $banner): void;
}
