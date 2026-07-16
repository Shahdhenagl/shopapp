<?php

declare(strict_types=1);

namespace App\Domain\Checkout\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class PaymentTotalMismatchException extends DomainException
{
    public function __construct(float $collected, float $expected)
    {
        parent::__construct(
            __('api.payment_total_mismatch', [
                'collected' => number_format($collected, 2),
                'expected' => number_format($expected, 2),
            ]),
            422,
            [],
            'checkout.payment_total_mismatch',
        );
    }
}
