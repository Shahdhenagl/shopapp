<?php

declare(strict_types=1);

namespace App\Domain\Auth\Contracts;

use App\Domain\Auth\DTOs\SocialAccount;

interface SocialTokenVerifier
{
    /**
     * Verify a provider token and return the confirmed account.
     *
     * @param  string  $provider  facebook | google
     * @param  string  $token  the provider access token (Facebook) or id token (Google)
     *
     * @throws \App\Domain\Auth\Exceptions\SocialProviderUnsupportedException
     * @throws \App\Domain\Auth\Exceptions\SocialAuthFailedException
     */
    public function verify(string $provider, string $token): SocialAccount;
}
