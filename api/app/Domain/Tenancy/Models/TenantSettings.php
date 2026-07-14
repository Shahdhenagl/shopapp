<?php

declare(strict_types=1);

namespace App\Domain\Tenancy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSettings extends Model
{
    protected $table = 'tenant_settings';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'app_name',
        'currency',
        'storefront_mode',
        'logo_url',
        'shipping_fee',
        'brand_primary',
        'brand_on_primary',
        'brand_accent',
        'flags',
        'home_rail_categories',
        'max_home_rails',
        'home_rail_item_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'flags' => 'array',
            'shipping_fee' => 'decimal:2',
            'home_rail_categories' => 'array',
            'max_home_rails' => 'integer',
            'home_rail_item_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
