<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

/**
 * A registration email-verification code is wrong or expired (BACKEND.md §2 —
 * 422, code otp.invalid).
 */
final class InvalidOtpException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('api.otp_invalid'), 422, [
            'code' => [__('api.otp_invalid')],
        ], 'otp.invalid');
    }
}
