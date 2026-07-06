<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Cart\Models\PromoCode;
use Illuminate\Database\Seeder;

final class PromoSeeder extends Seeder
{
    public function run(): void
    {
        $promos = [
            ['code' => 'MODIST10', 'fraction' => 0.10],
            ['code' => 'WELCOME15', 'fraction' => 0.15],
            ['code' => 'XX032910', 'fraction' => 0.20],
        ];

        foreach ($promos as $promo) {
            PromoCode::updateOrCreate(
                ['code' => $promo['code']],
                [
                    'type' => 'percent',
                    'fraction' => $promo['fraction'],
                    'active' => true,
                    'used_count' => 0,
                ],
            );
        }
    }
}
