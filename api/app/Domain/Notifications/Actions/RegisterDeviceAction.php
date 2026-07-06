<?php

declare(strict_types=1);

namespace App\Domain\Notifications\Actions;

use App\Domain\Auth\Models\User;
use App\Domain\Notifications\Contracts\NotificationRepositoryInterface;

final readonly class RegisterDeviceAction
{
    public function __construct(
        private NotificationRepositoryInterface $notifications,
    ) {
    }

    public function execute(User $user, string $token, string $platform): void
    {
        $this->notifications->registerDevice($user, $token, $platform);
    }
}
