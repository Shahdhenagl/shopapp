<?php

declare(strict_types=1);

namespace App\Domain\Cart\DTOs;

use App\Domain\Shared\ValueObjects\Money;

/**
 * Computed cart totals, returned alongside the cart lines.
 */
final readonly class CartSummary
{
    public function __construct(
        public Money $subtotal,
        public Money $discount,
        public Money $total,
        public ?string $promoCode = null,
        public ?float $promoFraction = null,
    ) {
    }

    public function hasPromo(): bool
    {
        return $this->promoCode !== null && $this->promoFraction !== null;
    }
}
