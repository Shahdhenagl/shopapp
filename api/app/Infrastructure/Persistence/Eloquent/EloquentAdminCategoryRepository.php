<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Catalog\Contracts\AdminCategoryRepositoryInterface;
use App\Domain\Catalog\Models\Category;
use Illuminate\Support\Collection;

final class EloquentAdminCategoryRepository implements AdminCategoryRepositoryInterface
{
    /**
     * @return Collection<int, Category>
     */
    public function tree(): Collection
    {
        // Fetch the whole (tenant-scoped) set once with product counts, then wire
        // the parent/child links in memory so the tree supports arbitrary depth
        // without an N+1 or a fixed eager-load depth.
        $all = Category::query()
            ->withCount('products')
            ->orderBy('sort_order')
            ->get();

        /** @var Collection<string|null, Collection<int, Category>> $byParent */
        $byParent = $all->groupBy('parent_id');

        $attach = function (Category $node) use (&$attach, $byParent): Category {
            $children = ($byParent[$node->getKey()] ?? new Collection())
                ->map(static fn (Category $child): Category => $attach($child))
                ->values();

            $node->setRelation('children', $children);

            return $node;
        };

        return $all
            ->filter(static fn (Category $category): bool => $category->parent_id === null)
            ->map(static fn (Category $category): Category => $attach($category))
            ->values();
    }

    public function find(int|string $id): ?Category
    {
        return Category::query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Category
    {
        return Category::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Category $category, array $attributes): Category
    {
        $category->update($attributes);

        return $category;
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }
}
