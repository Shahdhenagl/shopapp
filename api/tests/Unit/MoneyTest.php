<?php

declare(strict_types=1);

use App\Domain\Shared\Exceptions\DomainException;
use App\Domain\Shared\ValueObjects\Money;

it('converts major units to cents', function (): void {
    expect(Money::fromMajor('820')->toCents())->toBe(82000);
});

it('adds two money values', function (): void {
    $sum = Money::fromCents(82000)->add(Money::fromCents(1000));

    expect($sum->toCents())->toBe(83000);
});

it('subtracts two money values', function (): void {
    $diff = Money::fromCents(82000)->subtract(Money::fromCents(2000));

    expect($diff->toCents())->toBe(80000);
});

it('computes a percentage', function (): void {
    expect(Money::fromCents(82000)->percentage(0.10)->toCents())->toBe(8200);
});

it('multiplies by a factor', function (): void {
    expect(Money::fromCents(82000)->multiply(3)->toCents())->toBe(246000);
});

it('builds a zero value', function (): void {
    $zero = Money::zero();

    expect($zero->toCents())->toBe(0);
    expect($zero->isZero())->toBeTrue();
});

it('throws on currency mismatch', function (): void {
    Money::fromCents(82000, 'EGP')->add(Money::fromCents(1000, 'USD'));
})->throws(DomainException::class);
