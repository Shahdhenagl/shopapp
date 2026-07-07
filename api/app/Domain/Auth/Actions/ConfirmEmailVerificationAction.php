<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Contracts\OtpStore;
use App\Domain\Auth\Contracts\UserRepositoryInterface;
use App\Domain\Auth\Exceptions\InvalidOtpException;
use App\Domain\Auth\Models\PasswordResetCode;
use App\Domain\Auth\Models\User;

/**
 * Confirms the soft email-verification code for the authenticated user, stamping
 * email_verified_at. Wrong/expired code → InvalidOtpException (422 otp.invalid).
 */
final readonly class ConfirmEmailVerificationAction
{
    public function __construct(
        private UserRepositoryInterface $users,
        private OtpStore $otp,
    ) {
    }

    public function execute(User $user, string $code): User
    {
        if ($user->email_verified_at !== null) {
            return $user;
        }

        if (! $this->otp->verify($user->email, $code, PasswordResetCode::PURPOSE_EMAIL_VERIFICATION)) {
            throw new InvalidOtpException;
        }

        $verified = $this->users->markEmailVerified($user);
        $this->otp->consume($user->email, PasswordResetCode::PURPOSE_EMAIL_VERIFICATION);

        return $verified;
    }
}
