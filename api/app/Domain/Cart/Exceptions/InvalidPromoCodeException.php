<?php

declare(strict_types=1);

namespace App\Domain\Cart\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidPromoCodeException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('api.promo_invalid'), 422, [
            'code' => [__('api.promo_invalid')],
        ], 'promo.unknown');
    }
}
