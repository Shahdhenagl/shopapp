<?php

declare(strict_types=1);

namespace App\Domain\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PasswordResetCode extends Model
{
    /** One-time-code purposes (scopes a code to a single flow). */
    public const PURPOSE_PASSWORD_RESET = 'password_reset';

    public const PURPOSE_EMAIL_VERIFICATION = 'email_verification';

    protected $table = 'password_reset_codes';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'purpose',
        'code_hash',
        'expires_at',
        'consumed_at',
        'verified_at',
        'attempts',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'verified_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at instanceof Carbon
            && $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }
}
