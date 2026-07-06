<?php

declare(strict_types=1);

namespace App\Domain\Cart\Actions;

use App\Domain\Auth\Models\User;
use App\Domain\Cart\Contracts\CartRepositoryInterface;
use App\Domain\Cart\Models\Cart;
use App\Domain\Cart\ValueObjects\CartLineId;

final readonly class UpdateCartItemAction
{
    public function __construct(
        private CartRepositoryInterface $carts,
    ) {
    }

    /**
     * Sets the absolute quantity of a line; quantity <= 0 removes it.
     */
    public function execute(User $user, string $rawLineId, int $quantity): Cart
    {
        $line = CartLineId::parse($rawLineId);
        $cart = $this->carts->forUser($user);

        $this->carts->setQuantity($cart, $line->productId, $line->size, $line->colorValue, $quantity);

        return $this->carts->reload($cart);
    }
}
