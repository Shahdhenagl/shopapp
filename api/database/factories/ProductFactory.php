<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
final class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Ensure the referenced category exists (matched by per-tenant slug) so
        // the product's category relation resolves even when products are
        // factory-built in isolation (tests). tenant_id is auto-filled from the
        // active tenant context by the BelongsToTenant trait.
        Category::query()->firstOrCreate(
            ['slug' => 'tshirt'],
            [
                'label_key' => 'category_tshirt',
                'icon_key' => 'tshirt',
                'sort_order' => 0,
                'name' => ['en' => 'T-Shirts', 'ar' => 'تيشيرتات'],
            ],
        );

        return [
            'category_id' => 'tshirt',
            'price' => 820,
            'currency' => 'EGP',
            'rating' => 4.6,
            'is_newest' => true,
            'name' => ['en' => fake()->words(2, true), 'ar' => 'منتج'],
            'style' => ['en' => 'Men Style', 'ar' => 'ستايل رجالي'],
            'description' => ['en' => fake()->sentence(), 'ar' => 'وصف'],
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Product $product): void {
            $product->images()->create([
                'url' => 'https://picsum.photos/seed/modist1/600/800',
                'position' => 0,
            ]);

            $palette = [
                hexdec('FF1B2A4A'),
                hexdec('FF7B1E1E'),
                hexdec('FF111111'),
                hexdec('FF6B4A2B'),
            ];

            foreach ($palette as $position => $colorValue) {
                $product->colors()->create([
                    'color_value' => $colorValue,
                    'position' => $position,
                ]);
            }

            $sizes = ['S', 'M', 'L', 'XL', 'XXL', 'XXXL'];

            foreach ($sizes as $position => $size) {
                $product->sizes()->create([
                    'size' => $size,
                    'position' => $position,
                ]);
            }
        });
    }
}
