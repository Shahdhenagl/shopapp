<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Dashboard view of a composed notification. Exposes both locales of the
 * message and whether the row was a broadcast (null user_id) or targeted.
 */
class AdminNotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, string> $message */
        $message = $this->getTranslations('message');

        return [
            'id' => (string) $this->id,
            'type' => $this->type,
            'message' => [
                'en' => $message['en'] ?? null,
                'ar' => $message['ar'] ?? null,
            ],
            'user_id' => $this->user_id !== null ? (string) $this->user_id : null,
            'is_broadcast' => $this->user_id === null,
            'images' => $this->whenLoaded('images', fn () => $this->images->pluck('url')->values()),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
