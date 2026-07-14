<?php

declare(strict_types=1);

namespace App\Domain\Profile\Actions;

use App\Domain\Auth\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

final readonly class UpdateAvatarAction
{
    /**
     * Store a freshly uploaded avatar on the public disk, point the user at its
     * absolute URL, and clean up the previous avatar if we owned it (an
     * externally-hosted social avatar is left untouched).
     */
    public function execute(User $user, UploadedFile $image): User
    {
        $previous = $user->avatar_url;

        $path = $image->store('avatars', 'public');

        $user->avatar_url = URL::to(Storage::disk('public')->url($path));
        $user->save();

        $this->deleteIfOwned($previous);

        return $user;
    }

    /**
     * Remove a previously stored avatar file. Only deletes files under our own
     * public `avatars/` prefix so external (social) URLs are never touched.
     */
    private function deleteIfOwned(?string $url): void
    {
        if ($url === null || $url === '') {
            return;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path)) {
            return;
        }

        // Map "/storage/avatars/xyz.jpg" back to the disk path "avatars/xyz.jpg".
        if (preg_match('#/storage/(avatars/[^/]+)$#', $path, $m) !== 1) {
            return;
        }

        Storage::disk('public')->delete($m[1]);
    }
}
