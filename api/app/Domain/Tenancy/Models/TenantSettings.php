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
        'brand_primary',
        'brand_on_primary',
        'brand_accent',
        'flags',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'flags' => 'array',
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
