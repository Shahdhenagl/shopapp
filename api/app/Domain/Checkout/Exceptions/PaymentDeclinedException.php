<?php

declare(strict_types=1);

namespace App\Domain\Checkout\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class PaymentDeclinedException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('api.payment_declined'), 402, [], 'checkout.payment_declined');
    }
}
