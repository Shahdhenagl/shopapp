<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Domain\Auth\Contracts\SocialTokenVerifier;
use App\Domain\Auth\DTOs\SocialAccount;
use App\Domain\Auth\Exceptions\SocialAuthFailedException;
use App\Domain\Auth\Exceptions\SocialProviderUnsupportedException;
use Illuminate\Support\Facades\Http;

/**
 * Verifies a social token straight against the provider's own API — no third
 * party SDK. Facebook: exchange the access token for the profile via the Graph
 * API. Google: validate the id token via tokeninfo and check the audience
 * matches one of our configured client ids.
 */
final class HttpSocialTokenVerifier implements SocialTokenVerifier
{
    public const PROVIDER_FACEBOOK = 'facebook';

    public const PROVIDER_GOOGLE = 'google';

    public function verify(string $provider, string $token): SocialAccount
    {
        return match ($provider) {
            self::PROVIDER_FACEBOOK => $this->verifyFacebook($token),
            self::PROVIDER_GOOGLE => $this->verifyGoogle($token),
            default => throw new SocialProviderUnsupportedException,
        };
    }

    private function verifyFacebook(string $token): SocialAccount
    {
        $response = Http::get('https://graph.facebook.com/v19.0/me', [
            'fields' => 'id,name,email,picture.type(large)',
            'access_token' => $token,
        ]);

        if (! $response->successful() || $response->json('id') === null) {
            throw new SocialAuthFailedException;
        }

        $email = $response->json('email');

        // Facebook can withhold email if the user didn't grant the permission.
        if (! is_string($email) || $email === '') {
            throw new SocialAuthFailedException('api.social_email_missing');
        }

        return new SocialAccount(
            provider: self::PROVIDER_FACEBOOK,
            providerId: (string) $response->json('id'),
            email: $email,
            name: $response->json('name'),
            avatarUrl: $response->json('picture.data.url'),
        );
    }

    private function verifyGoogle(string $token): SocialAccount
    {
        $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $token,
        ]);

        if (! $response->successful() || $response->json('sub') === null) {
            throw new SocialAuthFailedException;
        }

        // The token must have been issued for one of our own client ids.
        $allowed = array_filter((array) config('services.google.client_ids', []));
        $aud = (string) $response->json('aud');

        if ($allowed !== [] && ! in_array($aud, $allowed, true)) {
            throw new SocialAuthFailedException;
        }

        $email = $response->json('email');

        if (! is_string($email) || $email === '') {
            throw new SocialAuthFailedException('api.social_email_missing');
        }

        return new SocialAccount(
            provider: self::PROVIDER_GOOGLE,
            providerId: (string) $response->json('sub'),
            email: $email,
            name: $response->json('name'),
            avatarUrl: $response->json('picture'),
        );
    }
}
