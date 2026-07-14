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
                'slug' => 'men-fashion',
                'label_key' => 'category_men_fashion',
                'icon_key' => 'men',
                'name' => ['en' => "Men's Fashion", 'ar' => 'ملابس رجالي'],
            ],
            [
                'slug' => 'women-fashion',
                'label_key' => 'category_women_fashion',
                'icon_key' => 'women',
                'name' => ['en' => "Women's Fashion", 'ar' => 'ملابس حريمي'],
            ],
            [
                'slug' => 'kids-fashion',
                'label_key' => 'category_kids_fashion',
                'icon_key' => 'kids',
                'name' => ['en' => "Kids' Fashion", 'ar' => 'ملابس أطفال'],
            ],
            [
                'slug' => 'shoes',
                'label_key' => 'category_shoes',
                'icon_key' => 'shoes',
                'name' => ['en' => 'Shoes', 'ar' => 'الأحذية'],
            ],
            [
                'slug' => 'bags',
                'label_key' => 'category_bags',
                'icon_key' => 'bags',
                'name' => ['en' => 'Bags', 'ar' => 'الشنط'],
            ],
            [
                'slug' => 'accessories',
                'label_key' => 'category_accessories',
                'icon_key' => 'accessories',
                'name' => ['en' => 'Accessories', 'ar' => 'الإكسسوارات'],
            ],
            [
                'slug' => 'watches',
                'label_key' => 'category_watches',
                'icon_key' => 'watch',
                'name' => ['en' => 'Watches', 'ar' => 'الساعات'],
            ],
            [
                'slug' => 'sportswear',
                'label_key' => 'category_sportswear',
                'icon_key' => 'sportswear',
                'name' => ['en' => 'Sportswear', 'ar' => 'ملابس رياضية'],
            ],
            [
                'slug' => 'underwear',
                'label_key' => 'category_underwear',
                'icon_key' => 'underwear',
                'name' => ['en' => 'Underwear', 'ar' => 'ملابس داخلية'],
            ],
            [
                'slug' => 'sleepwear',
                'label_key' => 'category_sleepwear',
                'icon_key' => 'sleepwear',
                'name' => ['en' => 'Sleepwear', 'ar' => 'ملابس نوم'],
            ],
            [
                'slug' => 'traditional-wear',
                'label_key' => 'category_traditional_wear',
                'icon_key' => 'abaya',
                'name' => ['en' => 'Traditional Wear', 'ar' => 'العبايات والملابس التقليدية'],
            ],
            [
                'slug' => 'jewelry',
                'label_key' => 'category_jewelry',
                'icon_key' => 'jewelry',
                'name' => ['en' => 'Jewelry', 'ar' => 'المجوهرات'],
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
