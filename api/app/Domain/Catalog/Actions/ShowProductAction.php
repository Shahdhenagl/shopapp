<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Contracts\ProductRepositoryInterface;
use App\Domain\Catalog\Exceptions\ProductNotFoundException;
use App\Domain\Catalog\Models\Product;

final readonly class ShowProductAction
{
    public function __construct(
        private ProductRepositoryInterface $products,
    ) {
    }

    public function execute(string $id): Product
    {
        return $this->products->findById($id)
            ?? throw new ProductNotFoundException;
    }
}
