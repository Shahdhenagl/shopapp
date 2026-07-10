<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Banners\Contracts\AdminBannerRepositoryInterface;
use App\Domain\Banners\Models\Banner;
use Illuminate\Database\Eloquent\Collection;

final class EloquentAdminBannerRepository implements AdminBannerRepositoryInterface
{
    public function all(): Collection
    {
        return Banner::query()
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get();
    }

    public function find(string $id): ?Banner
    {
        return Banner::query()->find($id);
    }

    public function create(array $attrs): Banner
    {
        return Banner::query()->create($attrs);
    }

    public function update(Banner $banner, array $attrs): Banner
    {
        $banner->update($attrs);

        return $banner->refresh();
    }

    public function delete(Banner $banner): void
    {
        $banner->delete();
    }
}
