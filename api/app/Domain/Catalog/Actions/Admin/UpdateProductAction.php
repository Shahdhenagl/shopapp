<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions\Admin;

use App\Domain\Catalog\Contracts\AdminProductRepositoryInterface;
use App\Domain\Catalog\Exceptions\ProductCategoryInvalidException;
use App\Domain\Catalog\Exceptions\ProductCategoryNotLeafException;
use App\Domain\Catalog\Exceptions\ProductNotFoundException;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;

final readonly class UpdateProductAction
{
    public function __construct(
        private AdminProductRepositoryInterface $products,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(string $id, array $data): Product
    {
        $product = $this->products->find($id)
            ?? throw new ProductNotFoundException;

        if (array_key_exists('category_id', $data)) {
            $this->assertLeafCategory((string) $data['category_id']);
        }

        $attrs = [];

        if (array_key_exists('category_id', $data)) {
            $attrs['category_id'] = $data['category_id'];
        }

        if (array_key_exists('price', $data)) {
            $attrs['price'] = $data['price'];
        }

        if (array_key_exists('currency', $data)) {
            $attrs['currency'] = $data['currency'] ?? config('app.currency', 'EGP');
        }

        if (array_key_exists('rating', $data)) {
            $attrs['rating'] = $data['rating'];
        }

        if (array_key_exists('is_newest', $data)) {
            $attrs['is_newest'] = $data['is_newest'];
        }

        if (array_key_exists('name', $data)) {
            $attrs['name'] = $this->normalizeTranslatable($data['name']);
        }

        if (array_key_exists('style', $data)) {
            $attrs['style'] = $data['style'] !== null ? $this->normalizeTranslatable($data['style']) : null;
        }

        if (array_key_exists('description', $data)) {
            $attrs['description'] = $data['description'] !== null ? $this->normalizeTranslatable($data['description']) : null;
        }

        return $this->products->update(
            $product,
            $attrs,
            $data['images'] ?? null,
            $data['sizes'] ?? null,
            $data['colors'] ?? null,
        );
    }

    private function assertLeafCategory(string $slug): void
    {
        $category = Category::query()->where('slug', $slug)->first()
            ?? throw new ProductCategoryInvalidException;

        if (! $category->isLeaf()) {
            throw new ProductCategoryNotLeafException;
        }
    }

    /**
     * Scalars become a { en, ar } map; an already-shaped map passes through.
     *
     * @param  string|array<string, string>  $value
     * @return array<string, string>
     */
    private function normalizeTranslatable(string|array $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        return ['en' => $value, 'ar' => $value];
    }
}
