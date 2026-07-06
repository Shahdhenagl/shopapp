<?php

declare(strict_types=1);

namespace App\Domain\Cart\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class CartLineNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('api.cart_line_not_found'), 404);
    }
}
