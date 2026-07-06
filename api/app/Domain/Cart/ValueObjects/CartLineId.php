<?php

declare(strict_types=1);

namespace App\Domain\Cart\ValueObjects;

use App\Domain\Cart\Exceptions\CartLineNotFoundException;

/**
 * Parses the composite cart line id `product_id|size|color_value` used by the
 * Flutter client for PATCH/DELETE /cart/{lineId}.
 */
final readonly class CartLineId
{
    public function __construct(
        public string $productId,
        public string $size,
        public int $colorValue,
    ) {
    }

    public static function parse(string $raw): self
    {
        $parts = explode('|', $raw);

        if (count($parts) !== 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
            throw new CartLineNotFoundException;
        }

        if (! ctype_digit($parts[2])) {
            throw new CartLineNotFoundException;
        }

        return new self(
            productId: $parts[0],
            size: $parts[1],
            colorValue: (int) $parts[2],
        );
    }

    public function toString(): string
    {
        return sprintf('%s|%s|%d', $this->productId, $this->size, $this->colorValue);
    }
}
