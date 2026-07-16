<?php

declare(strict_types=1);

namespace App\Domain\Checkout\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InsufficientStockException extends DomainException
{
    public function __construct(string $productName, int $available)
    {
        parent::__construct(
            __('api.insufficient_stock', ['product' => $productName, 'available' => $available]),
            422,
            [],
            'checkout.insufficient_stock',
        );
    }
}
