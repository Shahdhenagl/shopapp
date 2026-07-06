<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Cart\Contracts\PromoRepositoryInterface;
use App\Domain\Cart\Models\PromoCode;

final class EloquentPromoRepository implements PromoRepositoryInterface
{
    public function findUsableByCode(string $code): ?PromoCode
    {
        $promo = PromoCode::query()
            ->where('code', strtoupper($code))
            ->first();

        if ($promo === null || ! $promo->isUsable()) {
            return null;
        }

        return $promo;
    }
}
