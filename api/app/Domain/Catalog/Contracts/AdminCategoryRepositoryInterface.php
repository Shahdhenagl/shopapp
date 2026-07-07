<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Contracts;

use App\Domain\Catalog\Models\Category;
use Illuminate\Support\Collection;

interface AdminCategoryRepositoryInterface
{
    /**
     * The full tenant category tree: top-level categories (parent_id null) each
     * with a nested `children` relation loaded recursively, ordered by sort_order.
     *
     * @return Collection<int, Category>
     */
    public function tree(): Collection;

    public function find(string $id): ?Category;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Category;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Category $category, array $attributes): Category;

    public function delete(Category $category): void;
}
