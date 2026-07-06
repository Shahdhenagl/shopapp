<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Contracts\CategoryRepositoryInterface;
use App\Domain\Catalog\Models\Category;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListCategoriesAction
{
    public function __construct(
        private CategoryRepositoryInterface $categories,
    ) {
    }

    /**
     * @return Collection<int, Category>
     */
    public function execute(): Collection
    {
        return $this->categories->allOrdered();
    }
}
