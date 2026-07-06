<?php

declare(strict_types=1);

namespace App\Domain\Tenancy\Concerns;

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Scopes\TenantScope;
use App\Domain\Tenancy\Support\TenantContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marks an Eloquent model as tenant-owned: a global TenantScope filters every
 * read to the current tenant, and new records are stamped with the current
 * tenant id automatically (so callers never have to remember to set it).
 *
 * @phpstan-require-extends \Illuminate\Database\Eloquent\Model
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model): void {
            $column = $model->getTenantColumn();

            if (empty($model->{$column})) {
                $model->{$column} = app(TenantContext::class)->id();
            }
        });
    }

    public function getTenantColumn(): string
    {
        return 'tenant_id';
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
