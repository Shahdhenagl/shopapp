<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Banners\Models\Banner;
use Illuminate\Database\Seeder;

final class BannerSeeder extends Seeder
{
    public function run(): void
    {
        $banners = [
            [
                'image_url' => 'https://picsum.photos/seed/modist-hero1/1000/625',
                'title' => 'New Collection',
                'subtitle' => 'Discount 50% for the new summer collection.',
                'cta_text' => 'Shop now',
                'link_type' => Banner::LINK_CATEGORY,
                'link_value' => 'tshirt',
                'sort_order' => 0,
                'is_active' => true,
            ],
            [
                'image_url' => 'https://picsum.photos/seed/modist-hero2/1000/625',
                'title' => 'Fresh Kicks',
                'subtitle' => 'Step into the season with new sneakers.',
                'cta_text' => 'Explore',
                'link_type' => Banner::LINK_CATEGORY,
                'link_value' => 'shoes',
                'sort_order' => 1,
                'is_active' => true,
            ],
        ];

        foreach ($banners as $banner) {
            Banner::create($banner);
        }
    }
}
