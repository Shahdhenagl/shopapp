<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Contracts\RefreshTokenStore;
use App\Domain\Auth\Contracts\SocialTokenVerifier;
use App\Domain\Auth\Contracts\UserRepositoryInterface;
use App\Domain\Auth\DTOs\AuthResult;
use App\Domain\Auth\DTOs\SocialAccount;
use App\Domain\Auth\Exceptions\AccountSuspendedException;
use App\Domain\Auth\Models\User;

/**
 * Signs a user in with a Facebook / Google token. Resolves the account in three
 * steps: (1) an existing linked social identity, (2) an existing email account
 * the provider identity links onto, or (3) a brand-new account. Provider emails
 * are verified, so linked/created accounts are marked email-verified.
 */
final readonly class SocialLoginAction
{
    public function __construct(
        private SocialTokenVerifier $verifier,
        private UserRepositoryInterface $users,
        private RefreshTokenStore $refreshTokens,
    ) {
    }

    public function execute(string $provider, string $token): AuthResult
    {
        $account = $this->verifier->verify($provider, $token);

        $user = $this->users->findByProvider($account->provider, $account->providerId)
            ?? $this->linkOrCreate($account);

        if ($user->status === User::STATUS_SUSPENDED) {
            throw new AccountSuspendedException;
        }

        $accessToken = $user->createToken('mobile')->plainTextToken;
        $refreshToken = $this->refreshTokens->issue($user);

        return new AuthResult($accessToken, $refreshToken, $user);
    }

    /**
     * Attach the social identity to an existing email account, or create one.
     */
    private function linkOrCreate(SocialAccount $account): User
    {
        $existing = $this->users->findByEmail($account->email);

        if ($existing !== null) {
            return $this->users->update($existing, [
                'provider' => $existing->provider ?? $account->provider,
                'provider_id' => $existing->provider_id ?? $account->providerId,
                // A provider-verified email confirms the account.
                'email_verified_at' => $existing->email_verified_at ?? now(),
                'avatar_url' => $existing->avatar_url ?? $account->avatarUrl,
            ]);
        }

        // Omit `password` entirely — a social account has none, and the column
        // is nullable. (Passing null through the `hashed` cast is avoided.)
        return $this->users->create([
            'name' => $account->name ?: 'User',
            'email' => $account->email,
            'provider' => $account->provider,
            'provider_id' => $account->providerId,
            'avatar_url' => $account->avatarUrl,
            'email_verified_at' => now(),
        ]);
    }
}
