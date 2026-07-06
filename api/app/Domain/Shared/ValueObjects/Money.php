<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObjects;

use App\Domain\Shared\Exceptions\DomainException;

/**
 * Integer-cents money value object.
 *
 * All arithmetic is performed on integer minor units (piastres for EGP) so the
 * application never suffers binary floating-point drift. Conversions to/from
 * major units (e.g. decimal "820.00") happen only at the boundaries.
 */
final readonly class Money
{
    private function __construct(
        public int $amount,
        public string $currency,
    ) {
    }

    /**
     * Build from an integer number of minor units (cents/piastres).
     */
    public static function fromCents(int $amount, string $currency = 'EGP'): self
    {
        return new self($amount, strtoupper($currency));
    }

    /**
     * Build from a major-unit amount (e.g. "820" or "820.50" or 820.5).
     */
    public static function fromMajor(int|float|string $amount, string $currency = 'EGP'): self
    {
        $normalized = number_format((float) $amount, 2, '.', '');
        [$whole, $fraction] = explode('.', $normalized);

        $cents = ((int) $whole) * 100 + ((int) $fraction) * ((int) $whole < 0 ? -1 : 1);

        return new self($cents, strtoupper($currency));
    }

    public static function zero(string $currency = 'EGP'): self
    {
        return new self(0, strtoupper($currency));
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount - $other->amount, $this->currency);
    }

    public function multiply(int $factor): self
    {
        return new self($this->amount * $factor, $this->currency);
    }

    /**
     * Apply a discount fraction (e.g. 0.10) using banker-free, deterministic
     * rounding (round half up) on integer cents.
     */
    public function percentage(float $fraction): self
    {
        $discount = (int) round($this->amount * $fraction, 0, PHP_ROUND_HALF_UP);

        return new self($discount, $this->currency);
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function isNegative(): bool
    {
        return $this->amount < 0;
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    /**
     * Integer major-unit value when the catalog uses whole-currency prices.
     */
    public function toMajorInt(): int
    {
        return intdiv($this->amount, 100);
    }

    /**
     * Decimal major-unit value as float (use only at serialization boundaries).
     */
    public function toMajorFloat(): float
    {
        return $this->amount / 100;
    }

    public function toCents(): int
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new DomainException(
                sprintf('Currency mismatch: %s vs %s.', $this->currency, $other->currency),
                422
            );
        }
    }
}
