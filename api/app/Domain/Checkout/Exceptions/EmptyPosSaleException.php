<?php

declare(strict_types=1);

namespace App\Domain\Checkout\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class EmptyPosSaleException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('api.pos_empty_sale'), 422, [], 'checkout.pos_empty_sale');
    }
}
