<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Catalog\Models\Category;
use Illuminate\Database\Seeder;

final class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'slug' => 'tshirt',
                'label_key' => 'category_tshirt',
                'icon_key' => 'tshirt',
                'name' => ['en' => 'T-Shirts', 'ar' => 'تيشيرتات'],
            ],
            [
                'slug' => 'pants',
                'label_key' => 'category_pants',
                'icon_key' => 'pants',
                'name' => ['en' => 'Pants', 'ar' => 'بناطيل'],
            ],
            [
                'slug' => 'jacket',
                'label_key' => 'category_jacket',
                'icon_key' => 'jacket',
                'name' => ['en' => 'Jackets', 'ar' => 'جواكت'],
            ],
            [
                'slug' => 'shorts',
                'label_key' => 'category_shorts',
                'icon_key' => 'shorts',
                'name' => ['en' => 'Shorts', 'ar' => 'شورتات'],
            ],
            [
                'slug' => 'shoes',
                'label_key' => 'category_shoes',
                'icon_key' => 'shoes',
                'name' => ['en' => 'Shoes', 'ar' => 'أحذية'],
            ],
        ];

        foreach ($categories as $sortOrder => $category) {
            // Keyed by (tenant_id, slug); tenant_id is auto-filled from the
            // active tenant context by the BelongsToTenant trait.
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'label_key' => $category['label_key'],
                    'icon_key' => $category['icon_key'],
                    'name' => $category['name'],
                    'sort_order' => $sortOrder,
                ],
            );
        }
    }
}
