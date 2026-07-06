<?php

declare(strict_types=1);

namespace App\Infrastructure\Otp;

use App\Domain\Auth\Contracts\OtpStore;
use App\Domain\Auth\Models\PasswordResetCode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

final class DatabaseOtpStore implements OtpStore
{
    public function issue(string $email, string $purpose = PasswordResetCode::PURPOSE_PASSWORD_RESET): string
    {
        $this->consume($email, $purpose);

        $length = (int) config('otp.length', 4);
        $ttlMinutes = (int) config('otp.ttl_minutes', 10);

        $max = (10 ** $length) - 1;
        $code = str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);

        PasswordResetCode::query()->create([
            'email' => $email,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => Carbon::now()->addMinutes($ttlMinutes),
            'attempts' => 0,
        ]);

        return $code;
    }

    public function verify(string $email, string $code, string $purpose = PasswordResetCode::PURPOSE_PASSWORD_RESET): bool
    {
        /** @var PasswordResetCode|null $record */
        $record = PasswordResetCode::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->orderByDesc('id')
            ->first();

        $maxAttempts = (int) config('otp.max_attempts', 5);

        if ($record === null || $record->isExpired() || (int) $record->attempts >= $maxAttempts) {
            return false;
        }

        $record->attempts = (int) $record->attempts + 1;

        if (Hash::check($code, $record->code_hash)) {
            $record->verified_at = Carbon::now();
            $record->save();

            return true;
        }

        $record->save();

        return false;
    }

    public function hasVerified(string $email, string $purpose = PasswordResetCode::PURPOSE_PASSWORD_RESET): bool
    {
        return PasswordResetCode::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNotNull('verified_at')
            ->whereNull('consumed_at')
            ->where('expires_at', '>', Carbon::now())
            ->exists();
    }

    public function consume(string $email, string $purpose = PasswordResetCode::PURPOSE_PASSWORD_RESET): void
    {
        PasswordResetCode::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => Carbon::now()]);
    }
}
