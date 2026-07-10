<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1;

use App\Domain\Catalog\Contracts\AdminProductRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminProductResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * §3.5 — inventory visibility. Stock is edited through the product editor
 * (status/stock fields); this endpoint surfaces the low-stock list the
 * dashboard home and inventory screen show.
 */
class InventoryController extends Controller
{
    public function __construct(
        private readonly AdminProductRepositoryInterface $products,
    ) {
    }

    public function lowStock(Request $request): AnonymousResourceCollection
    {
        $threshold = max(0, (int) $request->query('threshold', 5));

        return AdminProductResource::collection($this->products->lowStock($threshold));
    }
}
