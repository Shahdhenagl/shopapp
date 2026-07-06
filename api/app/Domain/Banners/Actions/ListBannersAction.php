<?php

declare(strict_types=1);

namespace App\Domain\Banners\Actions;

use App\Domain\Banners\Contracts\BannerRepositoryInterface;
use App\Domain\Banners\Models\Banner;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListBannersAction
{
    public function __construct(
        private BannerRepositoryInterface $banners,
    ) {
    }

    /**
     * @return Collection<int, Banner>
     */
    public function execute(): Collection
    {
        return $this->banners->active();
    }
}
