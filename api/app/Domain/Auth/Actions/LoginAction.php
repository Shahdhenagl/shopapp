<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Contracts\RefreshTokenStore;
use App\Domain\Auth\Contracts\UserRepositoryInterface;
use App\Domain\Auth\DTOs\AuthResult;
use App\Domain\Auth\Exceptions\InvalidCredentialsException;
use Illuminate\Support\Facades\Hash;

final readonly class LoginAction
{
    public function __construct(
        private UserRepositoryInterface $users,
        private RefreshTokenStore $refreshTokens,
    ) {
    }

    public function execute(string $email, string $password): AuthResult
    {
        $user = $this->users->findByEmail($email);

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw new InvalidCredentialsException;
        }

        // Email verification is soft: login is NOT gated. The client sees the
        // user's `email_verified` flag and the server enforces at checkout.
        $token = $user->createToken('mobile')->plainTextToken;
        $refreshToken = $this->refreshTokens->issue($user);

        return new AuthResult($token, $refreshToken, $user);
    }
}
