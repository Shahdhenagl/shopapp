<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'image_url' => $this->image_url,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'cta_text' => $this->cta_text,
            'link_type' => $this->link_type,
            'link_value' => $this->link_value,
        ];
    }
}
