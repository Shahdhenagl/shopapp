<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Contracts\ProductRepositoryInterface;
use App\Domain\Catalog\Contracts\ReviewRepositoryInterface;
use App\Domain\Catalog\Exceptions\ProductNotFoundException;
use App\Domain\Catalog\Models\ProductReview;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListReviewsAction
{
    public function __construct(
        private ProductRepositoryInterface $products,
        private ReviewRepositoryInterface $reviews,
    ) {
    }

    /**
     * @return Collection<int, ProductReview>
     */
    public function execute(string $productId): Collection
    {
        // 404 only for a genuinely missing product — an empty feed is a 200
        // with [] (BACKEND.md §2.1).
        if ($this->products->findById($productId) === null) {
            throw new ProductNotFoundException;
        }

        return $this->reviews->forProduct($productId);
    }
}
