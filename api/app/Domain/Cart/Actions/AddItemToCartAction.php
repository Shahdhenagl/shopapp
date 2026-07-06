<?php

declare(strict_types=1);

namespace App\Domain\Cart\Actions;

use App\Domain\Auth\Models\User;
use App\Domain\Cart\Contracts\CartRepositoryInterface;
use App\Domain\Catalog\Contracts\ProductRepositoryInterface;
use App\Domain\Catalog\Exceptions\ProductNotFoundException;
use App\Domain\Cart\Models\Cart;

final readonly class AddItemToCartAction
{
    public function __construct(
        private CartRepositoryInterface $carts,
        private ProductRepositoryInterface $products,
    ) {
    }

    public function execute(User $user, string $productId, string $size, int $colorValue, int $quantity): Cart
    {
        if ($this->products->findById($productId) === null) {
            throw new ProductNotFoundException;
        }

        $cart = $this->carts->forUser($user);

        // Merges by (product_id, size, color_value) — DB unique constraint.
        $this->carts->addItem($cart, $productId, $size, $colorValue, max(1, $quantity));

        return $this->carts->reload($cart);
    }
}
