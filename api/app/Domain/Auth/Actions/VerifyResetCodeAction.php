<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Contracts\OtpStore;
use App\Domain\Auth\Exceptions\InvalidResetCodeException;

final readonly class VerifyResetCodeAction
{
    public function __construct(
        private OtpStore $otp,
    ) {
    }

    public function execute(string $email, string $code): void
    {
        if (! $this->otp->verify($email, $code)) {
            throw new InvalidResetCodeException;
        }
    }
}
