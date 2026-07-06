<?php

declare(strict_types=1);

namespace App\Domain\Checkout\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class UnsupportedPaymentMethodException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('api.unsupported_payment_method'), 422);
    }
}
