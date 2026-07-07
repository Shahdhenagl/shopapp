<?php

declare(strict_types=1);

namespace App\Domain\Addresses\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class AddressNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('api.address_not_found'), 404, [], 'address.not_found');
    }
}
