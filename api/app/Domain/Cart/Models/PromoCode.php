<?php

declare(strict_types=1);

namespace App\Domain\Cart\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PromoCode extends Model
{
    use BelongsToTenant;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'code',
        'type',
        'fraction',
        'active',
        'starts_at',
        'ends_at',
        'usage_limit',
        'used_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fraction' => 'float',
            'active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
        ];
    }

    public function isUsable(): bool
    {
        if (! $this->active) {
            return false;
        }

        $now = Carbon::now();

        if ($this->starts_at instanceof Carbon && $this->starts_at->gt($now)) {
            return false;
        }

        if ($this->ends_at instanceof Carbon && $this->ends_at->lt($now)) {
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }
}
