<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

/**
 * Registration attempted with an email that already belongs to a verified
 * account (BACKEND.md §2/§4 — 409 conflict, code auth.email_in_use).
 */
final class EmailInUseException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('api.email_in_use'), 409, [
            'email' => [__('api.email_in_use')],
        ], 'auth.email_in_use');
    }
}
