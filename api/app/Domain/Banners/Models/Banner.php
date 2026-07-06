<?php

declare(strict_types=1);

namespace App\Domain\Banners\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use BelongsToTenant;
    use HasUuids;

    /** Deep-link target types (BACKEND.md §6.9). */
    public const LINK_NONE = 'none';

    public const LINK_CATEGORY = 'category';

    public const LINK_PRODUCT = 'product';

    public const LINK_URL = 'url';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'tenant_id',
        'image_url',
        'title',
        'subtitle',
        'cta_text',
        'link_type',
        'link_value',
        'sort_order',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}
