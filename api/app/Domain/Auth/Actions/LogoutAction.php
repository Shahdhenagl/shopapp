<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Contracts\RefreshTokenStore;
use App\Domain\Auth\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

final class LogoutAction
{
    public function __construct(
        private readonly RefreshTokenStore $refreshTokens,
    ) {
    }

    /**
     * Revoke the access token used for the current request, plus every active
     * refresh token belonging to the user.
     */
    public function execute(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        $this->refreshTokens->revokeAllForUser($user);
    }
}
