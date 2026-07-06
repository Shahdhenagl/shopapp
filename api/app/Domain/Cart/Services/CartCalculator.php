<?php

declare(strict_types=1);

namespace App\Domain\Cart\Services;

use App\Domain\Cart\DTOs\CartSummary;
use App\Domain\Cart\Models\Cart;
use App\Domain\Shared\ValueObjects\Money;

/**
 * Computes cart totals server-side using integer-cents Money (no float drift).
 */
final class CartCalculator
{
    public function summarize(Cart $cart, ?string $promoCode = null, ?float $promoFraction = null): CartSummary
    {
        $currency = (string) config('app.currency', 'EGP');
        $subtotal = Money::zero($currency);

        foreach ($cart->items as $item) {
            $product = $item->product;

            if ($product === null) {
                continue;
            }

            $unit = Money::fromMajor((string) $product->price, $currency);
            $subtotal = $subtotal->add($unit->multiply((int) $item->quantity));
        }

        $discount = ($promoFraction !== null && $promoFraction > 0.0)
            ? $subtotal->percentage($promoFraction)
            : Money::zero($currency);

        $total = $subtotal->subtract($discount);

        return new CartSummary(
            subtotal: $subtotal,
            discount: $discount,
            total: $total->isNegative() ? Money::zero($currency) : $total,
            promoCode: $promoFraction !== null ? $promoCode : null,
            promoFraction: $promoFraction,
        );
    }

    public function lineTotal(Money $unitPrice, int $quantity): Money
    {
        return $unitPrice->multiply($quantity);
    }
}
