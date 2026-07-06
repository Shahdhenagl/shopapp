<?php

declare(strict_types=1);

namespace App\Domain\Cart\Contracts;

use App\Domain\Cart\Models\PromoCode;

interface PromoRepositoryInterface
{
    /**
     * Find an active, in-window promo by code (case-insensitive). Null when
     * the code is unknown or not currently usable.
     */
    public function findUsableByCode(string $code): ?PromoCode;
}
