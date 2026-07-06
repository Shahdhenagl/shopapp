<?php

declare(strict_types=1);

namespace App\Domain\Notifications\Actions;

use App\Domain\Auth\Models\User;
use App\Domain\Notifications\Contracts\NotificationRepositoryInterface;
use App\Domain\Notifications\Models\Notification;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListNotificationsAction
{
    public function __construct(
        private NotificationRepositoryInterface $notifications,
    ) {
    }

    /**
     * @return Collection<int, Notification>
     */
    public function execute(User $user): Collection
    {
        return $this->notifications->feedForUser($user);
    }
}
