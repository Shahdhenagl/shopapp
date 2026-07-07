<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Catalog\Contracts\CategoryRepositoryInterface;
use App\Domain\Catalog\Models\Category;
use Illuminate\Database\Eloquent\Collection;

final class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    public function allOrdered(): Collection
    {
        return Category::query()
            ->with('parent')
            ->orderBy('sort_order', 'asc')
            ->get();
    }
}
