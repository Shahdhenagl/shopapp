<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Contracts\OtpStore;
use App\Domain\Auth\Contracts\UserRepositoryInterface;
use App\Domain\Auth\Mail\SendOtpMail;
use Illuminate\Support\Facades\Mail;

/**
 * Regenerates and re-mails a reset code. Like SendResetCode, it stays generic
 * (always 200) and only does work when the account exists.
 */
final readonly class ResendResetCodeAction
{
    public function __construct(
        private UserRepositoryInterface $users,
        private OtpStore $otp,
    ) {
    }

    public function execute(string $email): void
    {
        if ($this->users->findByEmail($email) === null) {
            return;
        }

        $code = $this->otp->issue($email);

        Mail::to($email)->queue(new SendOtpMail(
            code: $code,
            ttlMinutes: (int) config('otp.ttl_minutes', 10),
        ));
    }
}
