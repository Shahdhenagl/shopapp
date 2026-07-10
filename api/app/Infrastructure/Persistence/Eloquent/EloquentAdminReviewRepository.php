<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Catalog\Contracts\AdminReviewRepositoryInterface;
use App\Domain\Catalog\Models\ProductReview;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentAdminReviewRepository implements AdminReviewRepositoryInterface
{
    public function paginate(?string $status, ?string $productId, int $perPage): LengthAwarePaginator
    {
        return ProductReview::query()
            ->with('product')
            ->when($status !== null && $status !== '', fn ($query) => $query->where('status', $status))
            ->when($productId !== null && $productId !== '', fn ($query) => $query->where('product_id', $productId))
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function find(string $id): ?ProductReview
    {
        return ProductReview::query()->with('product')->find($id);
    }

    public function updateStatus(ProductReview $review, string $status): ProductReview
    {
        $review->status = $status;
        $review->save();

        return $review;
    }

    public function delete(ProductReview $review): void
    {
        $review->delete();
    }

    public function approvedAverageForProduct(string $productId): float
    {
        $avg = ProductReview::query()
            ->where('product_id', $productId)
            ->where('status', ProductReview::STATUS_APPROVED)
            ->avg('rating');

        return round((float) $avg, 1);
    }
}
