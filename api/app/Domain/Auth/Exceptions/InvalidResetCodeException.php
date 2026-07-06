<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidResetCodeException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('api.reset_code_invalid'), 422, [
            'code' => [__('api.reset_code_invalid')],
        ], 'otp.invalid');
    }
}
