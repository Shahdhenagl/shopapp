<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

/**
 * Login attempted before the sign-up email OTP was confirmed (BACKEND.md §4 —
 * 403 forbidden, code auth.email_not_verified).
 */
final class EmailNotVerifiedException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('api.email_not_verified'), 403, [], 'auth.email_not_verified');
    }
}
