<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Contracts\OtpStore;
use App\Domain\Auth\Contracts\UserRepositoryInterface;
use App\Domain\Auth\Exceptions\EmailInUseException;
use App\Domain\Auth\Mail\SendOtpMail;
use App\Domain\Auth\Models\PasswordResetCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Sign-up is OTP-gated (BACKEND.md §4): this creates the account in an
 * *unverified* state and emails a 4-digit code. It returns NO token — the
 * client posts the code to /auth/register/verify to receive the token pair.
 */
final readonly class RegisterAction
{
    public function __construct(
        private UserRepositoryInterface $users,
        private OtpStore $otp,
    ) {
    }

    public function execute(string $name, string $email, ?string $phone, string $password): void
    {
        $existing = $this->users->findByEmail($email);

        if ($existing !== null && $existing->email_verified_at !== null) {
            throw new EmailInUseException;
        }

        if ($existing !== null) {
            // Re-registering an unverified account: refresh its details so a
            // user who mistyped isn't locked out, then re-issue the OTP.
            $this->users->update($existing, [
                'name' => $name,
                'phone' => $phone,
                'password' => Hash::make($password),
            ]);
        } else {
            $this->users->create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => Hash::make($password),
            ]);
        }

        $code = $this->otp->issue($email, PasswordResetCode::PURPOSE_EMAIL_VERIFICATION);

        Mail::to($email)->queue(new SendOtpMail(
            code: $code,
            ttlMinutes: (int) config('otp.ttl_minutes', 10),
            purpose: PasswordResetCode::PURPOSE_EMAIL_VERIFICATION,
        ));
    }
}
