<?php

declare(strict_types=1);

use App\Domain\Auth\Models\User;
use Illuminate\Support\Facades\Hash;

// Sign-up is instant (§4 / FLUTTER_INTEGRATION.md): register returns a flat
// token pair immediately and the account starts UNVERIFIED — email verification
// is a soft in-app nudge, never an OTP gate on sign-up or login.

it('registers a user and returns a flat token pair, unverified', function (): void {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '+201234567890',
        'password' => 'password123',
    ], ['Accept' => 'application/json']);

    $response->assertStatus(201);
    $response->assertJsonStructure([
        'token',
        'refresh_token',
        'user' => ['id', 'name', 'email', 'phone', 'avatar_url'],
    ]);
    expect($response->json('token'))->not->toBeNull();
    expect($response->json())->not->toHaveKey('data');

    // Instant sign-up: the account exists but is not yet verified.
    $this->assertDatabaseHas('users', ['email' => 'john@example.com', 'email_verified_at' => null]);
});

it('signs the new user in immediately — the register token authenticates', function (): void {
    $token = $this->postJson('/api/v1/auth/register', [
        'name' => 'Jane',
        'email' => 'jane@example.com',
        'phone' => '+201234567890',
        'password' => 'password123',
    ], ['Accept' => 'application/json'])->assertStatus(201)->json('token');

    $this->getJson('/api/v1/me', [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer ' . $token,
    ])->assertStatus(200)->assertJsonPath('data.email', 'jane@example.com');
});

it('rejects registration with a missing email', function (): void {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'No Email',
        'password' => 'password123',
    ], ['Accept' => 'application/json'])->assertStatus(422);
});

it('conflicts (409) when registering a verified email', function (): void {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Dup',
        'email' => 'taken@example.com',
        'password' => 'password123',
    ], ['Accept' => 'application/json']);

    $response->assertStatus(409);
    $response->assertJsonPath('code', 'auth.email_in_use');
});

it('logs in with correct credentials returning a flat token and user', function (): void {
    User::factory()->create([
        'email' => 'a@b.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'a@b.com',
        'password' => 'password123',
    ], ['Accept' => 'application/json']);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'token',
        'user' => ['id', 'name', 'email', 'phone', 'avatar_url'],
    ]);
    expect($response->json('token'))->not->toBeNull();
    expect($response->json())->not->toHaveKey('data');
});

it('allows login for an unverified account (verification is soft)', function (): void {
    User::factory()->unverified()->create([
        'email' => 'pending@b.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'pending@b.com',
        'password' => 'password123',
    ], ['Accept' => 'application/json']);

    $response->assertStatus(200);
    expect($response->json('token'))->not->toBeNull();
});

it('rejects login with a wrong password', function (): void {
    User::factory()->create([
        'email' => 'a@b.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'a@b.com',
        'password' => 'wrong-password',
    ], ['Accept' => 'application/json']);

    $response->assertStatus(401);
});

it('logs out an authenticated user', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->postJson('/api/v1/auth/logout', [], [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer ' . $token,
    ]);

    $response->assertNoContent();
});
