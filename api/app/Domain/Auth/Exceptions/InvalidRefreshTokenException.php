<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidRefreshTokenException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('api.invalid_refresh_token'), 401);
    }
}
