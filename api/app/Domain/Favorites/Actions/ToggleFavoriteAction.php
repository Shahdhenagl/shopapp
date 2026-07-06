<?php

declare(strict_types=1);

namespace App\Domain\Favorites\Actions;

use App\Domain\Auth\Models\User;
use App\Domain\Catalog\Contracts\ProductRepositoryInterface;
use App\Domain\Catalog\Exceptions\ProductNotFoundException;
use App\Domain\Favorites\Contracts\FavoriteRepositoryInterface;

final readonly class ToggleFavoriteAction
{
    public function __construct(
        private FavoriteRepositoryInterface $favorites,
        private ProductRepositoryInterface $products,
    ) {
    }

    /**
     * @return array<int, string> The updated favorite product-id list.
     */
    public function execute(User $user, string $productId): array
    {
        if ($this->products->findById($productId) === null) {
            throw new ProductNotFoundException;
        }

        return $this->favorites->toggle($user, $productId);
    }
}
