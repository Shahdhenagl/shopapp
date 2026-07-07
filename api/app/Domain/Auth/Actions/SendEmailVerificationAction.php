<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Contracts\OtpStore;
use App\Domain\Auth\Mail\SendOtpMail;
use App\Domain\Auth\Models\PasswordResetCode;
use App\Domain\Auth\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * (Re)sends the soft email-verification code to the authenticated user. A no-op
 * if the account is already verified. The user is resolved from the token, never
 * the body.
 */
final readonly class SendEmailVerificationAction
{
    public function __construct(
        private OtpStore $otp,
    ) {
    }

    public function execute(User $user): void
    {
        if ($user->email_verified_at !== null) {
            return;
        }

        $code = $this->otp->issue($user->email, PasswordResetCode::PURPOSE_EMAIL_VERIFICATION);

        Mail::to($user->email)->queue(new SendOtpMail(
            code: $code,
            ttlMinutes: (int) config('otp.ttl_minutes', 10),
            purpose: PasswordResetCode::PURPOSE_EMAIL_VERIFICATION,
        ));
    }
}
