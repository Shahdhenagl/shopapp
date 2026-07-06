<?php

declare(strict_types=1);

namespace App\Domain\Tenancy\Scopes;

use App\Domain\Tenancy\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Constrains every query on a tenant-owned model to the current tenant. When no
 * tenant is resolved (e.g. console commands, seeding) the scope is a no-op so
 * cross-tenant maintenance work still functions.
 */
final class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = app(TenantContext::class)->id();

        if ($tenantId === null) {
            return;
        }

        $builder->where($model->getTable().'.'.$model->getTenantColumn(), $tenantId);
    }
}
