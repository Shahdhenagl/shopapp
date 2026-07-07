<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions\Admin;

use App\Domain\Catalog\Contracts\AdminProductRepositoryInterface;
use App\Domain\Catalog\Exceptions\ProductNotFoundException;
use App\Domain\Catalog\Models\Product;

final readonly class DeleteProductAction
{
    public function __construct(
        private AdminProductRepositoryInterface $products,
    ) {
    }

    public function execute(string $id): Product
    {
        $product = $this->products->find($id)
            ?? throw new ProductNotFoundException;

        $this->products->delete($product);

        return $product;
    }
}
