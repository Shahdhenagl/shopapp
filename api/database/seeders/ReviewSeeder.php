<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductReview;
use Illuminate\Database\Seeder;

final class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $samples = [
            ['author_name' => 'Sara', 'rating' => 5, 'comment' => 'Lovely fit and fabric.'],
            ['author_name' => 'Omar', 'rating' => 4, 'comment' => 'Great quality for the price.'],
            ['author_name' => 'Mona', 'rating' => 5, 'comment' => 'Exactly as pictured, fast delivery.'],
        ];

        // Attach a few reviews to the two newest products so the detail screen's
        // reviews section has content, and recompute each product's rating.
        Product::query()->take(2)->get()->each(function (Product $product) use ($samples): void {
            foreach ($samples as $sample) {
                ProductReview::create([
                    'product_id' => $product->id,
                    'user_id' => null,
                    'author_name' => $sample['author_name'],
                    'rating' => $sample['rating'],
                    'comment' => $sample['comment'],
                ]);
            }

            $product->forceFill([
                'rating' => round((float) $product->reviews()->avg('rating'), 1),
            ])->save();
        });
    }
}
