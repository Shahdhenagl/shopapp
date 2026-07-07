<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

/**
 * Checkout attempted by a user whose email is not yet verified. Soft email
 * verification is enforced server-side here — the one place it gates the flow
 * (BACKEND.md §1.4 — 403 forbidden, code auth.email_not_verified).
 */
final class EmailNotVerifiedException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('api.email_not_verified'), 403, [], 'auth.email_not_verified');
    }
}
