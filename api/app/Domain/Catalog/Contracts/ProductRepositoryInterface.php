<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Contracts;

use App\Domain\Catalog\DTOs\ProductFilter;
use App\Domain\Catalog\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    /**
     * @return Collection<int, Product>
     */
    public function all(ProductFilter $filter): Collection;

    public function paginate(ProductFilter $filter, int $perPage): LengthAwarePaginator;

    public function findById(string $id): ?Product;
}
