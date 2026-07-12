<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTOs;

/**
 * A verified identity returned by a social provider (Facebook / Google) after
 * the provider confirmed the access/id token. The email is provider-verified,
 * so an account created/linked from it is treated as email-verified.
 */
final readonly class SocialAccount
{
    public function __construct(
        public string $provider,
        public string $providerId,
        public string $email,
        public ?string $name = null,
        public ?string $avatarUrl = null,
    ) {
    }
}
