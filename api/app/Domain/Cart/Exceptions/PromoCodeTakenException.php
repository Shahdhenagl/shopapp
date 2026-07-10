<?php

declare(strict_types=1);

namespace App\Domain\Cart\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class PromoCodeTakenException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            __('api.promo_code_taken'),
            422,
            ['code' => [__('api.promo_code_taken')]],
            'promo.code_taken',
        );
    }
}
