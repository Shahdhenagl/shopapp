<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Banners;

use App\Domain\Banners\Actions\ListBannersAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BannerController extends Controller
{
    public function __construct(
        private readonly ListBannersAction $listBannersAction,
    ) {
    }

    public function index(): AnonymousResourceCollection
    {
        return BannerResource::collection($this->listBannersAction->execute());
    }
}
