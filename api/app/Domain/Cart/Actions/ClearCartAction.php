<?php

declare(strict_types=1);

namespace App\Domain\Cart\Actions;

use App\Domain\Auth\Models\User;
use App\Domain\Cart\Contracts\CartRepositoryInterface;
use App\Domain\Cart\Models\Cart;

final readonly class ClearCartAction
{
    public function __construct(
        private CartRepositoryInterface $carts,
    ) {
    }

    public function execute(User $user): Cart
    {
        $cart = $this->carts->forUser($user);
        $this->carts->clear($cart);

        return $this->carts->reload($cart);
    }
}
