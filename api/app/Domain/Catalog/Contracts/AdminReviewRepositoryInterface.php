<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Contracts;

use App\Domain\Catalog\Models\ProductReview;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AdminReviewRepositoryInterface
{
    /**
     * Reviews across the tenant, newest first, optionally filtered by moderation
     * status and/or product, with the product eager-loaded.
     */
    public function paginate(?string $status, ?string $productId, int $perPage): LengthAwarePaginator;

    public function find(string $id): ?ProductReview;

    public function updateStatus(ProductReview $review, string $status): ProductReview;

    public function delete(ProductReview $review): void;

    /**
     * Average rating (0–5, one decimal) across a product's APPROVED reviews;
     * 0 when none remain approved.
     */
    public function approvedAverageForProduct(string $productId): float;
}
