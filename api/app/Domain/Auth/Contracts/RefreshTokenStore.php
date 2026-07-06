<?php

declare(strict_types=1);

namespace App\Domain\Auth\Contracts;

use App\Domain\Auth\Models\User;

/**
 * Narrow contract (ISP) for issuing, rotating and revoking opaque refresh
 * tokens. Implementations hash the token at rest and enforce a TTL.
 */
interface RefreshTokenStore
{
    /**
     * Generate, persist (hashed) and return the plaintext refresh token for
     * the user.
     */
    public function issue(User $user): string;

    /**
     * Validate the presented plaintext token (must exist, be unrevoked and
     * unexpired), revoke it, and issue a rotated replacement for the same user.
     *
     * @return array{user: User, token: string}
     *
     * @throws \App\Domain\Auth\Exceptions\InvalidRefreshTokenException
     */
    public function rotate(string $plaintext): array;

    /**
     * Revoke every active refresh token belonging to the user (e.g. on logout).
     */
    public function revokeAllForUser(User $user): void;
}
