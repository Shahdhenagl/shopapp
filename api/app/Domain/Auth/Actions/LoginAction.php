<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Contracts\RefreshTokenStore;
use App\Domain\Auth\Contracts\UserRepositoryInterface;
use App\Domain\Auth\DTOs\AuthResult;
use App\Domain\Auth\Exceptions\EmailNotVerifiedException;
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

        // Sign-up is OTP-gated: reject a login until the email is verified.
        if ($user->email_verified_at === null) {
            throw new EmailNotVerifiedException;
        }

        $token = $user->createToken('mobile')->plainTextToken;
        $refreshToken = $this->refreshTokens->issue($user);

        return new AuthResult($token, $refreshToken, $user);
    }
}
