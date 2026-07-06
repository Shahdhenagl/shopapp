<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Domain\Auth\Contracts\RefreshTokenStore;
use App\Domain\Auth\Exceptions\InvalidRefreshTokenException;
use App\Domain\Auth\Models\RefreshToken;
use App\Domain\Auth\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

final class DatabaseRefreshTokenStore implements RefreshTokenStore
{
    public function issue(User $user): string
    {
        return $this->issueFor($user, null);
    }

    public function rotate(string $plaintext): array
    {
        /** @var RefreshToken|null $record */
        $record = RefreshToken::query()
            ->where('token_hash', $this->hash($plaintext))
            ->first();

        if ($record === null
            || $record->revoked_at !== null
            || ($record->expires_at instanceof Carbon && $record->expires_at->isPast())
        ) {
            throw new InvalidRefreshTokenException;
        }

        $record->revoked_at = Carbon::now();
        $record->save();

        /** @var User $user */
        $user = $record->user;

        $token = $this->issueFor($user, (string) $record->id);

        return ['user' => $user, 'token' => $token];
    }

    public function revokeAllForUser(User $user): void
    {
        RefreshToken::query()
            ->where('user_id', $user->getKey())
            ->whereNull('revoked_at')
            ->update(['revoked_at' => Carbon::now()]);
    }

    private function issueFor(User $user, ?string $rotatedFrom): string
    {
        $ttlDays = (int) config('auth.refresh_ttl_days', 30);

        $token = Str::random(64);

        RefreshToken::query()->create([
            'user_id' => $user->getKey(),
            'token_hash' => $this->hash($token),
            'expires_at' => Carbon::now()->addDays($ttlDays),
            'rotated_from' => $rotatedFrom,
        ]);

        return $token;
    }

    private function hash(string $plaintext): string
    {
        return hash('sha256', $plaintext);
    }
}
