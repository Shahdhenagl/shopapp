<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Contracts\OtpStore;
use App\Domain\Auth\Contracts\RefreshTokenStore;
use App\Domain\Auth\Contracts\UserRepositoryInterface;
use App\Domain\Auth\DTOs\AuthResult;
use App\Domain\Auth\Exceptions\InvalidOtpException;
use App\Domain\Auth\Models\PasswordResetCode;

/**
 * Confirms the sign-up OTP (BACKEND.md §4): on a valid code it marks the
 * account verified, consumes the code and returns the token pair + user — the
 * same shape as login, signing the user in.
 */
final readonly class VerifyEmailAction
{
    public function __construct(
        private UserRepositoryInterface $users,
        private OtpStore $otp,
        private RefreshTokenStore $refreshTokens,
    ) {
    }

    public function execute(string $email, string $code): AuthResult
    {
        $user = $this->users->findByEmail($email);

        if ($user === null || ! $this->otp->verify($email, $code, PasswordResetCode::PURPOSE_EMAIL_VERIFICATION)) {
            throw new InvalidOtpException;
        }

        $this->users->markEmailVerified($user);
        $this->otp->consume($email, PasswordResetCode::PURPOSE_EMAIL_VERIFICATION);

        $token = $user->createToken('mobile')->plainTextToken;
        $refreshToken = $this->refreshTokens->issue($user);

        return new AuthResult($token, $refreshToken, $user);
    }
}
