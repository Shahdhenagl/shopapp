<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions\Admin;

use App\Domain\Admin\Models\AdminUser;
use App\Domain\Admin\Support\AuditLogger;
use App\Domain\Catalog\Contracts\AdminReviewRepositoryInterface;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductReview;

/**
 * §3.9 — approve/hide a review and keep the product's aggregate rating in sync.
 * Only approved reviews count toward the public rating, so any status change (or
 * a delete) recomputes it.
 */
final readonly class ModerateReviewAction
{
    public function __construct(
        private AdminReviewRepositoryInterface $reviews,
        private AuditLogger $audit,
    ) {
    }

    public function setStatus(AdminUser $actor, ProductReview $review, string $status): ProductReview
    {
        $before = ['status' => $review->status];
        $productId = (string) $review->product_id;

        $review = $this->reviews->updateStatus($review, $status);
        $this->recomputeRating($productId);

        $this->audit->log($actor, 'review.moderated', $review, $before, ['status' => $review->status]);

        return $review;
    }

    public function delete(AdminUser $actor, ProductReview $review): void
    {
        $before = $review->toArray();
        $productId = (string) $review->product_id;

        $this->reviews->delete($review);
        $this->recomputeRating($productId);

        $this->audit->log($actor, 'review.deleted', $review, $before, null);
    }

    /**
     * Rewrite the product's cached rating from its currently-approved reviews.
     */
    private function recomputeRating(string $productId): void
    {
        $average = $this->reviews->approvedAverageForProduct($productId);

        Product::query()->whereKey($productId)->update(['rating' => $average]);
    }
}
