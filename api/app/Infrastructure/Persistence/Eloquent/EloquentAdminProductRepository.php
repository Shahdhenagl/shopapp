<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Catalog\Contracts\AdminProductRepositoryInterface;
use App\Domain\Catalog\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class EloquentAdminProductRepository implements AdminProductRepositoryInterface
{
    public function paginate(?string $search, ?string $categorySlug, int $perPage): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(['images', 'colors', 'sizes']);

        if ($categorySlug !== null && $categorySlug !== '') {
            $query->where('category_id', $categorySlug);
        }

        if ($search !== null && $search !== '') {
            $term = '%' . $search . '%';
            $query->where(function (Builder $inner) use ($term): void {
                $inner->where('name->en', 'like', $term)
                    ->orWhere('name->ar', 'like', $term);
            });
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function find(string $id): ?Product
    {
        return Product::query()
            ->with(['images', 'colors', 'sizes'])
            ->find($id);
    }

    public function create(array $attrs, array $images, array $sizes, array $colors): Product
    {
        return DB::transaction(function () use ($attrs, $images, $sizes, $colors): Product {
            $product = Product::query()->create($attrs);

            $this->syncImages($product, $images);
            $this->syncSizes($product, $sizes);
            $this->syncColors($product, $colors);

            return $product->load(['images', 'colors', 'sizes']);
        });
    }

    public function update(Product $product, array $attrs, ?array $images, ?array $sizes, ?array $colors): Product
    {
        return DB::transaction(function () use ($product, $attrs, $images, $sizes, $colors): Product {
            $product->update($attrs);

            if ($images !== null) {
                $product->images()->delete();
                $this->syncImages($product, $images);
            }

            if ($sizes !== null) {
                $product->sizes()->delete();
                $this->syncSizes($product, $sizes);
            }

            if ($colors !== null) {
                $product->colors()->delete();
                $this->syncColors($product, $colors);
            }

            return $product->load(['images', 'colors', 'sizes']);
        });
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    /**
     * @param  array<int, string>  $images
     */
    private function syncImages(Product $product, array $images): void
    {
        foreach (array_values($images) as $position => $url) {
            $product->images()->create([
                'url' => $url,
                'position' => $position,
            ]);
        }
    }

    /**
     * @param  array<int, string>  $sizes
     */
    private function syncSizes(Product $product, array $sizes): void
    {
        foreach (array_values($sizes) as $position => $size) {
            $product->sizes()->create([
                'size' => $size,
                'position' => $position,
            ]);
        }
    }

    /**
     * Incoming colors may be an int or a hex string (#AARRGGBB / #RRGGBB);
     * normalize to an unsigned int stored as color_value.
     *
     * @param  array<int, int|string>  $colors
     */
    private function syncColors(Product $product, array $colors): void
    {
        foreach (array_values($colors) as $position => $color) {
            $product->colors()->create([
                'color_value' => $this->normalizeColor($color),
                'position' => $position,
            ]);
        }
    }

    private function normalizeColor(int|string $color): int
    {
        if (is_int($color)) {
            return $color;
        }

        return (int) hexdec(ltrim($color, '#'));
    }
}
