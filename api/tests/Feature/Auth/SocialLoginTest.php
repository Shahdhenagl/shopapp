<?php

declare(strict_types=1);

use App\Domain\Auth\Contracts\SocialTokenVerifier;
use App\Domain\Auth\DTOs\SocialAccount;
use App\Domain\Auth\Exceptions\SocialAuthFailedException;
use App\Domain\Auth\Models\User;

/**
 * Bind a fake verifier so tests never hit Facebook/Google. Pass an account to
 * return, or null to simulate a rejected token.
 */
function fakeVerifier(?SocialAccount $account): void
{
    app()->instance(SocialTokenVerifier::class, new class($account) implements SocialTokenVerifier
    {
        public function __construct(private ?SocialAccount $account)
        {
        }

        public function verify(string $provider, string $token): SocialAccount
        {
            return $this->account ?? throw new SocialAuthFailedException;
        }
    });
}

it('creates a verified account from a facebook token', function (): void {
    fakeVerifier(new SocialAccount('facebook', 'fb-123', 'sara@fb.com', 'Sara'));

    $response = $this->postJson('/api/v1/auth/social', [
        'provider' => 'facebook',
        'token' => 'valid-token',
    ], ['Accept' => 'application/json']);

    $response->assertStatus(200);
    $response->assertJsonStructure(['token', 'refresh_token', 'user' => ['id', 'email']]);
    $response->assertJsonPath('user.email', 'sara@fb.com');
    $response->assertJsonPath('user.email_verified', true);

    $this->assertDatabaseHas('users', [
        'email' => 'sara@fb.com',
        'provider' => 'facebook',
        'provider_id' => 'fb-123',
    ]);
});

it('links a social identity onto an existing email account', function (): void {
    $existing = User::factory()->create(['email' => 'omar@x.com', 'provider' => null]);

    fakeVerifier(new SocialAccount('google', 'g-999', 'omar@x.com', 'Omar'));

    $response = $this->postJson('/api/v1/auth/social', [
        'provider' => 'google',
        'token' => 'valid-token',
    ], ['Accept' => 'application/json']);

    $response->assertStatus(200);
    $response->assertJsonPath('user.id', (string) $existing->id);

    $this->assertDatabaseHas('users', [
        'id' => $existing->id,
        'provider' => 'google',
        'provider_id' => 'g-999',
    ]);
    // No duplicate account was created.
    expect(User::query()->where('email', 'omar@x.com')->count())->toBe(1);
});

it('is idempotent — a second social login returns the same user', function (): void {
    $account = new SocialAccount('facebook', 'fb-777', 'layla@fb.com', 'Layla');
    fakeVerifier($account);

    $first = $this->postJson('/api/v1/auth/social', ['provider' => 'facebook', 'token' => 't'], ['Accept' => 'application/json']);
    $second = $this->postJson('/api/v1/auth/social', ['provider' => 'facebook', 'token' => 't'], ['Accept' => 'application/json']);

    expect($second->json('user.id'))->toBe($first->json('user.id'));
    expect(User::query()->where('email', 'layla@fb.com')->count())->toBe(1);
});

it('refuses a suspended social user', function (): void {
    User::factory()->create([
        'email' => 'banned@fb.com',
        'provider' => 'facebook',
        'provider_id' => 'fb-ban',
        'status' => 'suspended',
    ]);

    fakeVerifier(new SocialAccount('facebook', 'fb-ban', 'banned@fb.com', 'Banned'));

    $this->postJson('/api/v1/auth/social', ['provider' => 'facebook', 'token' => 't'], ['Accept' => 'application/json'])
        ->assertStatus(403);
});

it('rejects an unsupported provider', function (): void {
    $this->postJson('/api/v1/auth/social', ['provider' => 'twitter', 'token' => 't'], ['Accept' => 'application/json'])
        ->assertStatus(422);
});

it('surfaces a provider verification failure as 401', function (): void {
    fakeVerifier(null); // the verifier throws

    $this->postJson('/api/v1/auth/social', ['provider' => 'google', 'token' => 'bad'], ['Accept' => 'application/json'])
        ->assertStatus(401)
        ->assertJsonPath('code', 'auth.social_failed');
});
