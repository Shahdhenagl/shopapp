<?php

declare(strict_types=1);

use App\Domain\Cart\Models\PromoCode;
use Illuminate\Support\Carbon;

it('is usable when active with no window', function (): void {
    $promo = new PromoCode([
        'code' => 'MODIST10',
        'type' => 'percent',
        'fraction' => 0.10,
        'active' => true,
        'used_count' => 0,
    ]);

    expect($promo->isUsable())->toBeTrue();
});

it('is not usable when inactive', function (): void {
    $promo = new PromoCode([
        'code' => 'OFF',
        'type' => 'percent',
        'fraction' => 0.10,
        'active' => false,
    ]);

    expect($promo->isUsable())->toBeFalse();
});

it('is not usable when the window has ended', function (): void {
    $promo = new PromoCode([
        'code' => 'EXPIRED',
        'type' => 'percent',
        'fraction' => 0.10,
        'active' => true,
        'starts_at' => Carbon::now()->subDays(10),
        'ends_at' => Carbon::now()->subDay(),
    ]);

    expect($promo->isUsable())->toBeFalse();
});

it('is not usable when the usage limit is reached', function (): void {
    $promo = new PromoCode([
        'code' => 'CAPPED',
        'type' => 'percent',
        'fraction' => 0.10,
        'active' => true,
        'usage_limit' => 5,
        'used_count' => 5,
    ]);

    expect($promo->isUsable())->toBeFalse();
});
