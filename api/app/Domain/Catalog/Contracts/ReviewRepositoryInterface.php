<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Contracts;

use App\Domain\Catalog\Models\ProductReview;
use Illuminate\Database\Eloquent\Collection;

interface ReviewRepositoryInterface
{
    /**
     * Newest-first reviews for a product within the current tenant.
     *
     * @return Collection<int, ProductReview>
     */
    public function forProduct(string $productId): Collection;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): ProductReview;

    /**
     * Average rating (0–5, one decimal) across a product's reviews; 0 when none.
     */
    public function averageRatingForProduct(string $productId): float;
}
