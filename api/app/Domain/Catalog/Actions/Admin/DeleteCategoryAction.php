<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions\Admin;

use App\Domain\Admin\Models\AdminUser;
use App\Domain\Admin\Support\AuditLogger;
use App\Domain\Catalog\Contracts\AdminCategoryRepositoryInterface;
use App\Domain\Catalog\Exceptions\CategoryNotEmptyException;
use App\Domain\Catalog\Models\Category;

final readonly class DeleteCategoryAction
{
    public function __construct(
        private AdminCategoryRepositoryInterface $categories,
        private AuditLogger $audit,
    ) {
    }

    /**
     * Soft-deletes a category. Blocks deletion when it still has children or
     * assigned products (§7.5) unless $cascade is set, in which case the whole
     * sub-tree is soft-deleted too.
     */
    public function execute(AdminUser $actor, Category $category, bool $cascade = false): void
    {
        if (! $cascade && ($category->children()->exists() || $category->products()->exists())) {
            throw new CategoryNotEmptyException;
        }

        $before = $category->toArray();

        if ($cascade) {
            $this->deleteSubtree($category);
        } else {
            $this->categories->delete($category);
        }

        $this->audit->log($actor, 'category.deleted', $category, $before, null);
    }

    /**
     * Depth-first soft-delete: children before their parent.
     */
    private function deleteSubtree(Category $category): void
    {
        foreach ($category->children()->get() as $child) {
            $this->deleteSubtree($child);
        }

        $this->categories->delete($category);
    }
}
