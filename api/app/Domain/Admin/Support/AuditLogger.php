<?php

declare(strict_types=1);

namespace App\Domain\Admin\Support;

use App\Domain\Admin\Models\AdminUser;
use App\Domain\Admin\Models\AuditLog;
use App\Domain\Tenancy\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;

/**
 * Records an immutable audit trail for admin actions. Injected into admin
 * modules that mutate state; call log() after the change succeeds.
 */
final class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function log(
        AdminUser $actor,
        string $action,
        ?Model $entity = null,
        ?array $before = null,
        ?array $after = null,
    ): void {
        AuditLog::query()->create([
            'tenant_id' => $actor->tenant_id ?? app(TenantContext::class)->id(),
            'admin_user_id' => $actor->getKey(),
            'action' => $action,
            'entity_type' => $entity !== null ? class_basename($entity) : null,
            'entity_id' => $entity !== null ? (string) $entity->getKey() : null,
            'before' => $before,
            'after' => $after,
            'created_at' => now(),
        ]);
    }
}
