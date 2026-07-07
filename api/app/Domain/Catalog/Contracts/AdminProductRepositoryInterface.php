<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Contracts;

use App\Domain\Catalog\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AdminProductRepositoryInterface
{
    public function paginate(?string $search, ?string $categorySlug, int $perPage): LengthAwarePaginator;

    public function find(string $id): ?Product;

    /**
     * @param  array<string, mixed>  $attrs
     * @param  array<int, string>  $images
     * @param  array<int, string>  $sizes
     * @param  array<int, int|string>  $colors
     */
    public function create(array $attrs, array $images, array $sizes, array $colors): Product;

    /**
     * When a child array is provided it REPLACES the existing set; when null the
     * existing rows are left untouched.
     *
     * @param  array<string, mixed>  $attrs
     * @param  array<int, string>|null  $images
     * @param  array<int, string>|null  $sizes
     * @param  array<int, int|string>|null  $colors
     */
    public function update(Product $product, array $attrs, ?array $images, ?array $sizes, ?array $colors): Product;

    public function delete(Product $product): void;
}
