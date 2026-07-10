<?php

declare(strict_types=1);

namespace App\Domain\Admin\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    // Audit rows are immutable: only created_at is tracked. NB the constant is
    // intentionally untyped — a typed `const string` cannot hold null (PHP 8.3
    // fatals), which is exactly how Eloquent disables the updated_at column.
    public const UPDATED_AT = null;

    protected $table = 'audit_logs';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'admin_user_id',
        'action',
        'entity_type',
        'entity_id',
        'before',
        'after',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
        ];
    }

    /**
     * @return BelongsTo<AdminUser, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id');
    }
}
