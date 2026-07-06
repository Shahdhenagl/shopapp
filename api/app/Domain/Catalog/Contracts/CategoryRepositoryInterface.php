<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Contracts;

use App\Domain\Catalog\Models\Category;
use Illuminate\Database\Eloquent\Collection;

interface CategoryRepositoryInterface
{
    /**
     * @return Collection<int, Category>
     */
    public function allOrdered(): Collection;
}
