<?php

declare(strict_types=1);

namespace App\Domain\Auth\Contracts;

use App\Domain\Auth\Models\PasswordResetCode;

/**
 * Narrow contract (ISP) for issuing/validating one-time codes.
 *
 * Implementations hash the code at rest, enforce a TTL, and cap attempts. The
 * $purpose scopes a code to a single flow (password reset vs. sign-up email
 * verification) so the two never collide for the same email (BACKEND.md §4).
 */
interface OtpStore
{
    /**
     * Generate, persist (hashed) and return the plaintext code for the email.
     */
    public function issue(string $email, string $purpose = PasswordResetCode::PURPOSE_PASSWORD_RESET): string;

    /**
     * Verify a plaintext code against the latest unexpired record for the
     * email, marking it verified on success. Returns true on success.
     */
    public function verify(string $email, string $code, string $purpose = PasswordResetCode::PURPOSE_PASSWORD_RESET): bool;

    /**
     * Whether a verified, unexpired, unconsumed code exists for the email
     * (required by the password reset step which only sends {email,password}).
     */
    public function hasVerified(string $email, string $purpose = PasswordResetCode::PURPOSE_PASSWORD_RESET): bool;

    /**
     * Consume (invalidate) the active verified code for the email.
     */
    public function consume(string $email, string $purpose = PasswordResetCode::PURPOSE_PASSWORD_RESET): void;
}
