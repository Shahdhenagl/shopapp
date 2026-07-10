<?php

declare(strict_types=1);

namespace App\Domain\Notifications\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class NotificationTargetInvalidException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            __('api.notification_target_invalid'),
            422,
            ['user_id' => [__('api.notification_target_invalid')]],
            'notification.target_invalid',
        );
    }
}
