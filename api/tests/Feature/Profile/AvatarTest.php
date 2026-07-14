<?php

declare(strict_types=1);

use App\Domain\Auth\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Register a user and return a Bearer token that authenticates them, so the
 * avatar upload runs as a real authenticated app user.
 */
function avatarUserToken(): string
{
    return test()->postJson('/api/v1/auth/register', [
        'name' => 'Avatar User',
        'email' => 'avatar@example.com',
        'phone' => '+201234567890',
        'password' => 'password123',
    ], ['Accept' => 'application/json'])->json('token');
}

it('uploads an avatar image and points the user at its URL', function (): void {
    Storage::fake('public');
    $token = avatarUserToken();

    $response = $this->postJson('/api/v1/me/avatar', [
        'image' => UploadedFile::fake()->image('me.jpg', 200, 200),
    ], ['Accept' => 'application/json', 'Authorization' => 'Bearer ' . $token]);

    $response->assertStatus(200);
    expect($response->json('data.avatar_url'))->toContain('/storage/avatars/');

    // Exactly one file landed under avatars/.
    expect(Storage::disk('public')->files('avatars'))->toHaveCount(1);
});

it('replaces the previous stored avatar and deletes the old file', function (): void {
    Storage::fake('public');
    $token = avatarUserToken();
    $headers = ['Accept' => 'application/json', 'Authorization' => 'Bearer ' . $token];

    $this->postJson('/api/v1/me/avatar', ['image' => UploadedFile::fake()->image('a.jpg')], $headers)
        ->assertStatus(200);
    $this->postJson('/api/v1/me/avatar', ['image' => UploadedFile::fake()->image('b.jpg')], $headers)
        ->assertStatus(200);

    // The old file was cleaned up — only the latest avatar remains.
    expect(Storage::disk('public')->files('avatars'))->toHaveCount(1);
});

it('rejects a non-image upload', function (): void {
    Storage::fake('public');
    $token = avatarUserToken();

    $this->postJson('/api/v1/me/avatar', [
        'image' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
    ], ['Accept' => 'application/json', 'Authorization' => 'Bearer ' . $token])
        ->assertStatus(422);
});

it('requires authentication to change the avatar', function (): void {
    Storage::fake('public');

    $this->postJson('/api/v1/me/avatar', [
        'image' => UploadedFile::fake()->image('me.jpg'),
    ], ['Accept' => 'application/json'])->assertStatus(401);
});

it('leaves an external (social) avatar file untouched on replace', function (): void {
    Storage::fake('public');
    $token = avatarUserToken();

    // Simulate a social-login avatar hosted elsewhere.
    User::query()->where('email', 'avatar@example.com')
        ->update(['avatar_url' => 'https://cdn.example.com/social/pic.jpg']);

    $this->postJson('/api/v1/me/avatar', [
        'image' => UploadedFile::fake()->image('me.jpg'),
    ], ['Accept' => 'application/json', 'Authorization' => 'Bearer ' . $token])
        ->assertStatus(200);

    // Nothing to delete off our disk; the new upload is the only stored file.
    expect(Storage::disk('public')->files('avatars'))->toHaveCount(1);
});
