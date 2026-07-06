<?php

declare(strict_types=1);

namespace App\Domain\Cart\Actions;

use App\Domain\Auth\Models\User;
use App\Domain\Cart\Contracts\CartRepositoryInterface;
use App\Domain\Cart\Models\Cart;
use App\Domain\Cart\ValueObjects\CartLineId;

final readonly class RemoveCartItemAction
{
    public function __construct(
        private CartRepositoryInterface $carts,
    ) {
    }

    public function execute(User $user, string $rawLineId): Cart
    {
        $line = CartLineId::parse($rawLineId);
        $cart = $this->carts->forUser($user);

        $this->carts->removeItem($cart, $line->productId, $line->size, $line->colorValue);

        return $this->carts->reload($cart);
    }
}
