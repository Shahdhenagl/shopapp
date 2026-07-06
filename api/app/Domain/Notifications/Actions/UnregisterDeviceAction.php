<?php

declare(strict_types=1);

namespace App\Domain\Notifications\Actions;

use App\Domain\Auth\Models\User;
use App\Domain\Notifications\Contracts\NotificationRepositoryInterface;

final readonly class UnregisterDeviceAction
{
    public function __construct(
        private NotificationRepositoryInterface $notifications,
    ) {
    }

    public function execute(User $user, string $token): void
    {
        $this->notifications->unregisterDevice($user, $token);
    }
}
