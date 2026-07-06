<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Catalog\Contracts\ReviewRepositoryInterface;
use App\Domain\Catalog\Models\ProductReview;
use Illuminate\Database\Eloquent\Collection;

final class EloquentReviewRepository implements ReviewRepositoryInterface
{
    public function forProduct(string $productId): Collection
    {
        return ProductReview::query()
            ->where('product_id', $productId)
            ->latest()
            ->get();
    }

    public function create(array $attributes): ProductReview
    {
        return ProductReview::query()->create($attributes);
    }

    public function averageRatingForProduct(string $productId): float
    {
        $avg = ProductReview::query()
            ->where('product_id', $productId)
            ->avg('rating');

        return round((float) $avg, 1);
    }
}
