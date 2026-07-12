<?php

declare(strict_types=1);

namespace App\Domain\Notifications\Models;

use App\Domain\Auth\Models\User;
use App\Domain\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Notification extends Model
{
    use BelongsToTenant;
    use HasTranslations;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'tenant_id',
        'user_id',
        'type',
        'message',
        'read_at',
    ];

    /**
     * @var list<string>
     */
    public array $translatable = ['message'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        // NOTE: the translatable `message` field is intentionally NOT cast to
        // 'array' — spatie/laravel-translatable manages its JSON storage and
        // returns the active-locale string on access, so (string) $this->message
        // resolves correctly inside the Resource.
        return [
            'read_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<NotificationImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(NotificationImage::class)->orderBy('position');
    }

    /**
     * @return HasMany<NotificationRead, $this>
     */
    public function reads(): HasMany
    {
        return $this->hasMany(NotificationRead::class);
    }
}
