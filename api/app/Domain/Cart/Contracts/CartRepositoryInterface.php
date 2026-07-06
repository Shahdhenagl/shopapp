<?php

declare(strict_types=1);

namespace App\Domain\Cart\Contracts;

use App\Domain\Auth\Models\User;
use App\Domain\Cart\Models\Cart;
use App\Domain\Cart\Models\CartItem;

interface CartRepositoryInterface
{
    /**
     * Get (or lazily create) the user's cart with items + products eager-loaded.
     */
    public function forUser(User $user): Cart;

    /**
     * Add a line, merging by (product_id, size, color_value) — summing quantity.
     */
    public function addItem(Cart $cart, string $productId, string $size, int $colorValue, int $quantity): CartItem;

    /**
     * Set the absolute quantity of a line; quantity <= 0 removes it.
     */
    public function setQuantity(Cart $cart, string $productId, string $size, int $colorValue, int $quantity): void;

    public function removeItem(Cart $cart, string $productId, string $size, int $colorValue): void;

    /**
     * Persist (or clear, with null) the promo code applied to the cart.
     */
    public function setPromo(Cart $cart, ?string $promoCode): void;

    public function clear(Cart $cart): void;

    /**
     * Reload the cart fresh with items.product relations.
     */
    public function reload(Cart $cart): Cart;
}
