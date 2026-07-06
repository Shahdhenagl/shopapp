<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Contracts\OtpStore;
use App\Domain\Auth\Contracts\UserRepositoryInterface;
use App\Domain\Auth\Mail\SendOtpMail;
use Illuminate\Support\Facades\Mail;

/**
 * Always succeeds (returns void / 200) to avoid leaking account existence.
 */
final readonly class SendResetCodeAction
{
    public function __construct(
        private UserRepositoryInterface $users,
        private OtpStore $otp,
    ) {
    }

    public function execute(string $email): void
    {
        // Only issue + mail a code when the account actually exists, but never
        // reveal that fact to the caller.
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
