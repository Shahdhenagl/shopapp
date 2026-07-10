<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

/**
 * A user suspended from the dashboard (§3.10) is refused at login.
 */
final class AccountSuspendedException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('api.account_suspended'), 403, [], 'auth.account_suspended');
    }
}
