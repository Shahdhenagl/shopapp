<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions\Admin;

use App\Domain\Catalog\Contracts\AdminProductRepositoryInterface;
use App\Domain\Catalog\Exceptions\ProductCategoryInvalidException;
use App\Domain\Catalog\Exceptions\ProductCategoryNotLeafException;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;

final readonly class CreateProductAction
{
    public function __construct(
        private AdminProductRepositoryInterface $products,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Product
    {
        $this->assertLeafCategory((string) $data['category_id']);

        $attrs = [
            'category_id' => $data['category_id'],
            'price' => $data['price'],
            'currency' => $data['currency'] ?? config('app.currency', 'EGP'),
            'rating' => $data['rating'] ?? 0,
            'is_newest' => $data['is_newest'] ?? false,
            'status' => $data['status'] ?? Product::STATUS_ACTIVE,
            'stock' => $data['stock'] ?? 0,
            'name' => $this->normalizeTranslatable($data['name']),
            'style' => isset($data['style']) ? $this->normalizeTranslatable($data['style']) : null,
            'description' => isset($data['description']) ? $this->normalizeTranslatable($data['description']) : null,
        ];

        return $this->products->create(
            $attrs,
            $data['images'] ?? [],
            $data['sizes'] ?? [],
            $data['colors'] ?? [],
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
