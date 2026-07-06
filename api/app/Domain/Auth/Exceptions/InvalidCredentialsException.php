<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidCredentialsException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('api.invalid_credentials'), 401, [], 'auth.invalid_credentials');
    }
}
