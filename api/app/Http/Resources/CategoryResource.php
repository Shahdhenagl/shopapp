<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // The client identifies categories by the per-tenant slug, not the
            // surrogate UUID primary key.
            'id' => $this->slug,
            'label_key' => $this->label_key,
            'icon_key' => $this->icon_key,
        ];
    }
}
