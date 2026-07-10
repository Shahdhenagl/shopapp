<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Catalog\Contracts\ProductRepositoryInterface;
use App\Domain\Catalog\DTOs\ProductFilter;
use App\Domain\Catalog\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class EloquentProductRepository implements ProductRepositoryInterface
{
    public function all(ProductFilter $filter): Collection
    {
        return $this->query($filter)->get();
    }

    public function paginate(ProductFilter $filter, int $perPage): LengthAwarePaginator
    {
        return $this->query($filter)->paginate($perPage);
    }

    public function findById(string $id): ?Product
    {
        return Product::query()
            ->with(['images', 'colors', 'sizes', 'category'])
            ->where('status', Product::STATUS_ACTIVE)
            ->find($id);
    }

    private function query(ProductFilter $filter): Builder
    {
        // The storefront only ever sees published products; hidden ones are
        // dashboard-only until the operator flips them active.
        $query = Product::query()
            ->with(['images', 'colors', 'sizes', 'category'])
            ->where('status', Product::STATUS_ACTIVE);

        if ($filter->hasCategory()) {
            $query->where('category_id', $filter->categoryId);
        }

        if ($filter->newestOnly === true) {
            $query->where('is_newest', true);
        }

        if ($filter->hasSearch()) {
            $term = '%' . $filter->search . '%';
            $query->where(function (Builder $inner) use ($term): void {
                $inner->where('name->en', 'like', $term)
                    ->orWhere('name->ar', 'like', $term);
            });
        }

        return $query
            ->orderBy('is_newest', 'desc')
            ->orderBy('created_at', 'desc');
    }
}
