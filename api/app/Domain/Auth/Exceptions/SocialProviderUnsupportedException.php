<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class SocialProviderUnsupportedException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            __('api.social_provider_unsupported'),
            422,
            ['provider' => [__('api.social_provider_unsupported')]],
            'auth.social_unsupported',
        );
    }
}
