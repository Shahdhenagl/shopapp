<?php

declare(strict_types=1);

namespace App\Domain\Admin\Models;

use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * A dashboard operator. Authenticated with Sanctum tokens carrying the `admin`
 * ability (kept distinct from app users, whose tokens never have it). A null
 * tenant_id = super-admin (acts across all tenants).
 */
class AdminUser extends Authenticatable
{
    use HasApiTokens;

    public const string ROLE_SUPER_ADMIN = 'super-admin';
    public const string ROLE_TENANT_ADMIN = 'tenant-admin';
    public const string ROLE_STAFF = 'staff';

    protected $table = 'admin_users';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'two_factor_confirmed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
