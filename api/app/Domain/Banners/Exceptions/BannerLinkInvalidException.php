<?php

declare(strict_types=1);

namespace App\Domain\Banners\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

/**
 * §7.3 — a banner's deep-link target must reference a live category or product.
 */
final class BannerLinkInvalidException extends DomainException
{
    public function __construct(string $messageKey = 'api.banner_link_invalid')
    {
        parent::__construct(
            __($messageKey),
            422,
            ['link_value' => [__($messageKey)]],
            'banner.link_invalid',
        );
    }
}
