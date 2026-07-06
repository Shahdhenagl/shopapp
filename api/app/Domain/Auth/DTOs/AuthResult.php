<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTOs;

use App\Domain\Auth\Models\User;

/**
 * Outcome of a successful authentication: the plaintext Sanctum access token,
 * the plaintext rotating refresh token, plus the authenticated user. Serialized
 * flat (NOT data-wrapped) per the contract.
 */
final readonly class AuthResult
{
    public function __construct(
        public string $token,
        public string $refreshToken,
        public User $user,
    ) {
    }
}
