<?php

declare(strict_types=1);

namespace App\Domain\Notifications\Contracts;

use App\Domain\Notifications\Models\Notification;
use Illuminate\Database\Eloquent\Collection;

interface AdminNotificationRepositoryInterface
{
    /**
     * All notifications composed in the tenant, newest first (broadcasts and
     * user-targeted alike), with images eager-loaded.
     *
     * @return Collection<int, Notification>
     */
    public function all(): Collection;

    /**
     * Persist a single notification row.
     *
     * A null $userId marks a broadcast (visible to every user in the tenant);
     * a concrete id targets one user. Images are attached in order.
     *
     * @param  array<string, string>  $message  bilingual { en, ar } map
     * @param  array<int, string>  $images
     */
    public function create(string $type, array $message, ?int $userId, array $images): Notification;

    /**
     * Ids of the tenant's app users (used to size a broadcast's push fan-out).
     *
     * @return array<int, int>
     */
    public function recipientIds(?int $userId): array;
}
