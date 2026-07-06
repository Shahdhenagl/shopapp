<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Auth\Models\User;
use App\Domain\Cart\Contracts\CartRepositoryInterface;
use App\Domain\Cart\Models\Cart;
use App\Domain\Cart\Models\CartItem;

final class EloquentCartRepository implements CartRepositoryInterface
{
    private const ITEM_RELATIONS = ['items.product.images', 'items.product.colors', 'items.product.sizes'];

    public function forUser(User $user): Cart
    {
        $cart = Cart::query()->firstOrCreate(['user_id' => $user->id]);
        $cart->load(self::ITEM_RELATIONS);

        return $cart;
    }

    public function addItem(Cart $cart, string $productId, string $size, int $colorValue, int $quantity): CartItem
    {
        $item = $cart->items()->firstOrNew([
            'product_id' => $productId,
            'size' => $size,
            'color_value' => $colorValue,
        ]);

        $item->quantity = ($item->exists ? (int) $item->quantity : 0) + $quantity;
        $item->save();

        return $item;
    }

    public function setQuantity(Cart $cart, string $productId, string $size, int $colorValue, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeItem($cart, $productId, $size, $colorValue);

            return;
        }

        $cart->items()->updateOrCreate(
            [
                'product_id' => $productId,
                'size' => $size,
                'color_value' => $colorValue,
            ],
            ['quantity' => $quantity],
        );
    }

    public function removeItem(Cart $cart, string $productId, string $size, int $colorValue): void
    {
        $cart->items()
            ->where('product_id', $productId)
            ->where('size', $size)
            ->where('color_value', $colorValue)
            ->delete();
    }

    public function setPromo(Cart $cart, ?string $promoCode): void
    {
        $cart->promo_code = $promoCode;
        $cart->save();
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
        $cart->promo_code = null;
        $cart->save();
    }

    public function reload(Cart $cart): Cart
    {
        return $cart->fresh(self::ITEM_RELATIONS);
    }
}
