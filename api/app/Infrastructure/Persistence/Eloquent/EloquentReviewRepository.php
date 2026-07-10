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
        // The public feed shows approved reviews only; pending/hidden ones are
        // visible to moderators through the admin API.
        return ProductReview::query()
            ->where('product_id', $productId)
            ->where('status', ProductReview::STATUS_APPROVED)
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
            ->where('status', ProductReview::STATUS_APPROVED)
            ->avg('rating');

        return round((float) $avg, 1);
    }
}
