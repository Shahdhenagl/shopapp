<?php

declare(strict_types=1);

namespace App\Domain\Cart\Actions;

use App\Domain\Auth\Models\User;
use App\Domain\Cart\Contracts\CartRepositoryInterface;
use App\Domain\Cart\Contracts\PromoRepositoryInterface;
use App\Domain\Cart\Exceptions\InvalidPromoCodeException;
use App\Domain\Cart\Models\PromoCode;

final readonly class ApplyPromoAction
{
    public function __construct(
        private PromoRepositoryInterface $promos,
        private CartRepositoryInterface $carts,
    ) {
    }

    /**
     * Validates a promo code (case-insensitive, active, in-window) and persists
     * it on the user's cart so checkout recomputes the discount server-side.
     * Returns the usable PromoCode or throws a 422 with the `promoInvalid` key.
     */
    public function execute(User $user, string $code): PromoCode
    {
        $promo = $this->promos->findUsableByCode($code)
            ?? throw new InvalidPromoCodeException;

        $cart = $this->carts->forUser($user);
        $this->carts->setPromo($cart, $promo->code);

        return $promo;
    }
}
