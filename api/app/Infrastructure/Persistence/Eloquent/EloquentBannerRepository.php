<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Banners\Contracts\BannerRepositoryInterface;
use App\Domain\Banners\Models\Banner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

final class EloquentBannerRepository implements BannerRepositoryInterface
{
    public function active(): Collection
    {
        $now = Carbon::now();

        return Banner::query()
            ->where('is_active', true)
            ->where('image_url', '!=', '')
            ->where(function (Builder $q) use ($now): void {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $q) use ($now): void {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get();
    }
}
