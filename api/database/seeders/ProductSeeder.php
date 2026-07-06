<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Catalog\Models\Product;
use Illuminate\Database\Seeder;

final class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $palette = [
            hexdec('FF1B2A4A'),
            hexdec('FF7B1E1E'),
            hexdec('FF111111'),
            hexdec('FF6B4A2B'),
        ];

        $sizes = ['S', 'M', 'L', 'XL', 'XXL', 'XXXL'];

        $products = [
            [
                'category_id' => 'tshirt',
                'name' => ['en' => "Men's Casual Navy Shirt", 'ar' => 'قميص كاجوال كحلي'],
                'style' => ['en' => 'Casual', 'ar' => 'كاجوال'],
                'description' => ['en' => 'A comfortable navy shirt for everyday wear.', 'ar' => 'قميص كحلي مريح للارتداء اليومي.'],
            ],
            [
                'category_id' => 'pants',
                'name' => ['en' => 'Slim Fit Chino Pants', 'ar' => 'بنطلون شينو ضيق'],
                'style' => ['en' => 'Smart Casual', 'ar' => 'سمارت كاجوال'],
                'description' => ['en' => 'Slim fit chinos with a modern cut.', 'ar' => 'بنطلون شينو ضيق بقصة عصرية.'],
            ],
            [
                'category_id' => 'jacket',
                'name' => ['en' => 'Classic Bomber Jacket', 'ar' => 'جاكيت بومبر كلاسيكي'],
                'style' => ['en' => 'Streetwear', 'ar' => 'ستريت وير'],
                'description' => ['en' => 'A timeless bomber jacket for cool evenings.', 'ar' => 'جاكيت بومبر خالد للأمسيات الباردة.'],
            ],
            [
                'category_id' => 'shorts',
                'name' => ['en' => 'Summer Cargo Shorts', 'ar' => 'شورت كارجو صيفي'],
                'style' => ['en' => 'Casual', 'ar' => 'كاجوال'],
                'description' => ['en' => 'Breathable cargo shorts for warm days.', 'ar' => 'شورت كارجو مريح للأيام الحارة.'],
            ],
            [
                'category_id' => 'shoes',
                'name' => ['en' => 'Urban Running Sneakers', 'ar' => 'حذاء رياضي حضري'],
                'style' => ['en' => 'Sporty', 'ar' => 'رياضي'],
                'description' => ['en' => 'Lightweight sneakers built for the city.', 'ar' => 'حذاء رياضي خفيف مصمم للمدينة.'],
            ],
            [
                'category_id' => 'tshirt',
                'name' => ['en' => 'Graphic Print Tee', 'ar' => 'تيشيرت مطبوع'],
                'style' => ['en' => 'Streetwear', 'ar' => 'ستريت وير'],
                'description' => ['en' => 'Bold graphic tee made from soft cotton.', 'ar' => 'تيشيرت مطبوع جريء من القطن الناعم.'],
            ],
            [
                'category_id' => 'pants',
                'name' => ['en' => 'Relaxed Denim Jeans', 'ar' => 'جينز دينيم واسع'],
                'style' => ['en' => 'Casual', 'ar' => 'كاجوال'],
                'description' => ['en' => 'Relaxed fit denim jeans for all-day comfort.', 'ar' => 'جينز دينيم واسع لراحة طوال اليوم.'],
            ],
            [
                'category_id' => 'jacket',
                'name' => ['en' => 'Hooded Windbreaker', 'ar' => 'جاكيت واقي من الرياح بقبعة'],
                'style' => ['en' => 'Sporty', 'ar' => 'رياضي'],
                'description' => ['en' => 'Lightweight hooded windbreaker for outdoor adventures.', 'ar' => 'جاكيت خفيف واقي من الرياح بقبعة للمغامرات الخارجية.'],
            ],
        ];

        foreach ($products as $index => $data) {
            $i = $index + 1;

            $product = Product::create([
                'category_id' => $data['category_id'],
                'price' => 820,
                'currency' => 'EGP',
                'rating' => 4.6,
                'is_newest' => $i <= 4,
                'name' => $data['name'],
                'style' => $data['style'],
                'description' => $data['description'],
            ]);

            $product->images()->create([
                'url' => "https://picsum.photos/seed/modist{$i}/600/800",
                'position' => 0,
            ]);
            $product->images()->create([
                'url' => "https://picsum.photos/seed/modist{$i}a/600/800",
                'position' => 1,
            ]);

            foreach ($palette as $position => $colorValue) {
                $product->colors()->create([
                    'color_value' => $colorValue,
                    'position' => $position,
                ]);
            }

            foreach ($sizes as $position => $size) {
                $product->sizes()->create([
                    'size' => $size,
                    'position' => $position,
                ]);
            }
        }
    }
}
