<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'author_name' => $this->author_name,
            'rating' => (int) $this->rating,
            'comment' => $this->comment,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
