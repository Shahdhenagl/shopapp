<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Contracts\ProductRepositoryInterface;
use App\Domain\Catalog\DTOs\ProductFilter;
use App\Domain\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListProductsAction
{
    public function __construct(
        private ProductRepositoryInterface $products,
    ) {
    }

    /**
     * Returns the full active catalog by default (filters optional) so the
     * app's client-side search/filter keeps working.
     *
     * @return Collection<int, Product>
     */
    public function execute(ProductFilter $filter): Collection
    {
        return $this->products->all($filter);
    }
}
