<?php

declare(strict_types=1);

namespace App\Domain\Checkout\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

/**
 * §3.7 — the requested order status is not reachable from the current one.
 */
final class InvalidOrderTransitionException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            __('api.order_status_invalid_transition'),
            422,
            ['status' => [__('api.order_status_invalid_transition')]],
            'order.invalid_transition',
        );
    }
}
