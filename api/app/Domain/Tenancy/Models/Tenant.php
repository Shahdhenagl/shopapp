<?php

declare(strict_types=1);

namespace App\Domain\Tenancy\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    use HasUuids;

    public const string STATUS_ACTIVE = 'active';
    public const string STATUS_SUSPENDED = 'suspended';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'status',
    ];

    /**
     * @return HasOne<TenantSettings, $this>
     */
    public function settings(): HasOne
    {
        return $this->hasOne(TenantSettings::class);
    }
}
