<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Contracts\OtpStore;
use App\Domain\Auth\Contracts\UserRepositoryInterface;
use App\Domain\Auth\Mail\SendOtpMail;
use App\Domain\Auth\Models\PasswordResetCode;
use Illuminate\Support\Facades\Mail;

/**
 * Re-sends the sign-up OTP (BACKEND.md §4). Always returns void/200 and only
 * mails a code for an existing, still-unverified account — so it never reveals
 * whether an email is registered or already verified.
 */
final readonly class ResendVerificationAction
{
    public function __construct(
        private UserRepositoryInterface $users,
        private OtpStore $otp,
    ) {
    }

    public function execute(string $email): void
    {
        $user = $this->users->findByEmail($email);

        if ($user === null || $user->email_verified_at !== null) {
            return;
        }

        $code = $this->otp->issue($email, PasswordResetCode::PURPOSE_EMAIL_VERIFICATION);

        Mail::to($email)->queue(new SendOtpMail(
            code: $code,
            ttlMinutes: (int) config('otp.ttl_minutes', 10),
            purpose: PasswordResetCode::PURPOSE_EMAIL_VERIFICATION,
        ));
    }
}
