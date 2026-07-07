<?php

declare(strict_types=1);

namespace App\Domain\Admin\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidAdminCredentialsException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('api.invalid_credentials'), 401, [], 'auth.invalid_credentials');
    }
}
