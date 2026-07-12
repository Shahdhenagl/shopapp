<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

/**
 * The social provider rejected the token, or it carried no usable email.
 */
final class SocialAuthFailedException extends DomainException
{
    public function __construct(string $messageKey = 'api.social_auth_failed')
    {
        parent::__construct(__($messageKey), 401, [], 'auth.social_failed');
    }
}
