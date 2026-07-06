<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Contracts\RefreshTokenStore;
use App\Domain\Auth\DTOs\AuthResult;
use App\Domain\Auth\Models\User;

final readonly class RefreshAccessTokenAction
{
    public function __construct(
        private RefreshTokenStore $refreshTokens,
    ) {
    }

    public function execute(string $refreshToken): AuthResult
    {
        $rotated = $this->refreshTokens->rotate($refreshToken);

        /** @var User $user */
        $user = $rotated['user'];

        $accessToken = $user->createToken('mobile')->plainTextToken;

        return new AuthResult($accessToken, $rotated['token'], $user);
    }
}
