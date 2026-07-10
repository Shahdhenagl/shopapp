<?php

declare(strict_types=1);

namespace App\Domain\Notifications\Actions\Admin;

use App\Domain\Admin\Models\AdminUser;
use App\Domain\Admin\Support\AuditLogger;
use App\Domain\Notifications\Contracts\AdminNotificationRepositoryInterface;
use App\Domain\Notifications\Exceptions\NotificationTargetInvalidException;
use App\Domain\Notifications\Models\Notification;

/**
 * §3.8 — compose one notification and fan it out to the chosen audience. A
 * broadcast writes a single tenant-wide row (null user_id); a targeted send
 * writes a row for one user. Push delivery is a separate transport concern —
 * the recipient set is resolved here so a push driver can consume it later.
 */
final readonly class BroadcastNotificationAction
{
    public function __construct(
        private AdminNotificationRepositoryInterface $notifications,
        private AuditLogger $audit,
    ) {
    }

    /**
     * @param  array<string, string>  $message  bilingual { en, ar } map
     * @param  array<int, string>  $images
     */
    public function execute(
        AdminUser $actor,
        string $type,
        array $message,
        ?int $userId,
        array $images = [],
    ): Notification {
        $recipients = $this->notifications->recipientIds($userId);

        if ($recipients === []) {
            throw new NotificationTargetInvalidException;
        }

        $notification = $this->notifications->create($type, $message, $userId, $images);

        $this->audit->log($actor, 'notification.broadcast', $notification, null, [
            'type' => $type,
            'user_id' => $userId,
            'recipients' => count($recipients),
        ]);

        return $notification;
    }
}
