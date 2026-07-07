<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Contracts\RefreshTokenStore;
use App\Domain\Auth\Contracts\UserRepositoryInterface;
use App\Domain\Auth\DTOs\AuthResult;
use App\Domain\Auth\Exceptions\EmailInUseException;
use Illuminate\Support\Facades\Hash;

/**
 * Sign-up is instant: create the account and sign the user in with a token pair.
 * The account starts UNVERIFIED (email_verified_at = null); email verification is
 * soft (a nudge in-app) and enforced only at checkout — so we never gate sign-up
 * behind an OTP.
 */
final readonly class RegisterAction
{
    public function __construct(
        private UserRepositoryInterface $users,
        private RefreshTokenStore $refreshTokens,
    ) {
    }

    public function execute(string $name, string $email, ?string $phone, string $password): AuthResult
    {
        if ($this->users->findByEmail($email) !== null) {
            throw new EmailInUseException;
        }

        $user = $this->users->create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make($password),
        ]);

        $token = $user->createToken('mobile')->plainTextToken;
        $refreshToken = $this->refreshTokens->issue($user);

        return new AuthResult($token, $refreshToken, $user);
    }
}
