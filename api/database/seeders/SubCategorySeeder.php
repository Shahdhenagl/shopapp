<?php

namespace Database\Seeders;

use App\Domain\Catalog\Models\SubCategory;
use App\Domain\Catalog\Models\Category;
use Illuminate\Database\Seeder;

class SubCategorySeeder extends Seeder
{
    public function run(): void
    {
        $subCategories = [
            'men-fashion' => [
                ['slug' => 't-shirts', 'en' => 'T-Shirts', 'ar' => 'تيشيرتات'],
                ['slug' => 'shirts', 'en' => 'Shirts', 'ar' => 'قمصان'],
                ['slug' => 'jeans', 'en' => 'Jeans', 'ar' => 'جينز'],
                ['slug' => 'pants', 'en' => 'Pants', 'ar' => 'بناطيل'],
                ['slug' => 'shorts', 'en' => 'Shorts', 'ar' => 'شورتات'],
                ['slug' => 'jackets', 'en' => 'Jackets', 'ar' => 'جاكيتات'],
                ['slug' => 'hoodies', 'en' => 'Hoodies', 'ar' => 'هوديز'],
                ['slug' => 'suits', 'en' => 'Suits', 'ar' => 'بدل'],
            ],

            'women-fashion' => [
                ['slug' => 'dresses', 'en' => 'Dresses', 'ar' => 'فساتين'],
                ['slug' => 'blouses', 'en' => 'Blouses', 'ar' => 'بلوزات'],
                ['slug' => 'skirts', 'en' => 'Skirts', 'ar' => 'تنانير'],
                // ['slug' => 'pants', 'en' => 'Pants', 'ar' => 'بناطيل'],
                ['slug' => 'abayas', 'en' => 'Abayas', 'ar' => 'عبايات'],
                ['slug' => 'jackets', 'en' => 'Jackets', 'ar' => 'جاكيتات'],
                ['slug' => 'lingerie', 'en' => 'Lingerie', 'ar' => 'لانجري'],
            ],

            'kids-fashion' => [
                ['slug' => 'boys-clothing', 'en' => 'Boys Clothing', 'ar' => 'ملابس أولاد'],
                ['slug' => 'girls-clothing', 'en' => 'Girls Clothing', 'ar' => 'ملابس بنات'],
                ['slug' => 'baby-clothing', 'en' => 'Baby Clothing', 'ar' => 'ملابس بيبي'],
            ],

            'shoes' => [
                ['slug' => 'sneakers', 'en' => 'Sneakers', 'ar' => 'سنيكرز'],
                ['slug' => 'boots', 'en' => 'Boots', 'ar' => 'بوت'],
                ['slug' => 'sandals', 'en' => 'Sandals', 'ar' => 'صنادل'],
                ['slug' => 'formal-shoes', 'en' => 'Formal Shoes', 'ar' => 'أحذية رسمية'],
            ],

            'bags' => [
                ['slug' => 'handbags', 'en' => 'Handbags', 'ar' => 'حقائب يد'],
                ['slug' => 'backpacks', 'en' => 'Backpacks', 'ar' => 'حقائب ظهر'],
                ['slug' => 'travel-bags', 'en' => 'Travel Bags', 'ar' => 'حقائب سفر'],
                ['slug' => 'wallets', 'en' => 'Wallets', 'ar' => 'محافظ'],
            ],

            'accessories' => [
                ['slug' => 'belts', 'en' => 'Belts', 'ar' => 'أحزمة'],
                ['slug' => 'caps', 'en' => 'Caps', 'ar' => 'كابات'],
                ['slug' => 'scarves', 'en' => 'Scarves', 'ar' => 'أوشحة'],
                ['slug' => 'sunglasses', 'en' => 'Sunglasses', 'ar' => 'نظارات شمسية'],
            ],

            'watches' => [
                ['slug' => 'men-watches', 'en' => "Men's Watches", 'ar' => 'ساعات رجالي'],
                ['slug' => 'women-watches', 'en' => "Women's Watches", 'ar' => 'ساعات حريمي'],
                ['slug' => 'smart-watches', 'en' => 'Smart Watches', 'ar' => 'ساعات ذكية'],
            ],

            'sportswear' => [
                ['slug' => 'gym-wear', 'en' => 'Gym Wear', 'ar' => 'ملابس جيم'],
                ['slug' => 'running', 'en' => 'Running', 'ar' => 'ملابس جري'],
                ['slug' => 'football', 'en' => 'Football', 'ar' => 'ملابس كرة قدم'],
            ],
        ];

        foreach ($subCategories as $categorySlug => $items) {
            $category = Category::where('slug', $categorySlug)->first();

            if (! $category) {
                continue;
            }

            foreach ($items as $item) {
                SubCategory::updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'slug' => $item['slug'],
                    ],
                    [
                        'name' => [
                            'en' => $item['en'],
                            'ar' => $item['ar'],
                        ],
                    ]
                );
            }
        }
    }
}
