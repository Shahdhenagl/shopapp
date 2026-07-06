<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'type' => (string) $this->type,
            'message' => (string) $this->message,
            'created_at' => $this->created_at?->toIso8601String(),
            'images' => $this->images->pluck('url')->values(),
            'is_read' => (bool) $this->getAttribute('is_read_computed'),
        ];
    }
}
