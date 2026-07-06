<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Contracts\OtpStore;
use App\Domain\Auth\Contracts\UserRepositoryInterface;
use App\Domain\Auth\Exceptions\InvalidResetCodeException;
use App\Domain\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\Hash;

/**
 * The app's reset call sends only {email, password}; we therefore bind the
 * reset to a recently-verified, unexpired, unconsumed code for that email.
 */
final readonly class ResetPasswordAction
{
    public function __construct(
        private UserRepositoryInterface $users,
        private OtpStore $otp,
    ) {
    }

    public function execute(string $email, string $password): void
    {
        if (! $this->otp->hasVerified($email)) {
            throw new InvalidResetCodeException;
        }

        $user = $this->users->findByEmail($email);

        if ($user === null) {
            // Should not happen given a verified code, but stay safe.
            throw new DomainException(__('api.no_verified_code'), 422);
        }

        $this->users->updatePassword($user, Hash::make($password));

        // Invalidate the code and revoke existing tokens for safety.
        $this->otp->consume($email);
        $user->tokens()->delete();
    }
}
