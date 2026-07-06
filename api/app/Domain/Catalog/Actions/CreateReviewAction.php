<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Auth\Models\User;
use App\Domain\Catalog\Contracts\ProductRepositoryInterface;
use App\Domain\Catalog\Contracts\ReviewRepositoryInterface;
use App\Domain\Catalog\Exceptions\ProductNotFoundException;
use App\Domain\Catalog\Models\ProductReview;

final readonly class CreateReviewAction
{
    public function __construct(
        private ProductRepositoryInterface $products,
        private ReviewRepositoryInterface $reviews,
    ) {
    }

    public function execute(string $productId, User $author, int $rating, ?string $comment): ProductReview
    {
        $product = $this->products->findById($productId)
            ?? throw new ProductNotFoundException;

        // Server attributes the review to the authenticated user and snapshots
        // the author name (BACKEND.md §6.3b). tenant_id is auto-stamped.
        $review = $this->reviews->create([
            'product_id' => $productId,
            'user_id' => $author->getKey(),
            'author_name' => $author->name,
            'rating' => max(0, min(5, $rating)),
            'comment' => $comment,
        ]);

        // Recompute the catalog-level aggregate from the reviews feed so the
        // product card's rating stays in sync (BACKEND.md §6.3b).
        $product->forceFill([
            'rating' => $this->reviews->averageRatingForProduct($productId),
        ])->save();

        return $review;
    }
}
