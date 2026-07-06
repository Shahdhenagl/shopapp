<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class ProductNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('api.product_not_found'), 404);
    }
}
