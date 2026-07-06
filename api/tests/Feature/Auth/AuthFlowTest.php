<?php

declare(strict_types=1);

use App\Domain\Auth\Mail\SendOtpMail;
use App\Domain\Auth\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

it('registers an unverified user without a token and emails an OTP', function (): void {
    Mail::fake();

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '+201234567890',
        'password' => 'password123',
    ], ['Accept' => 'application/json']);

    $response->assertStatus(201);

    // NO token on register — sign-up is OTP-gated (§4).
    expect($response->json())->not->toHaveKey('token');

    $this->assertDatabaseHas('users', ['email' => 'john@example.com', 'email_verified_at' => null]);

    Mail::assertQueued(SendOtpMail::class, fn (SendOtpMail $mail): bool => $mail->purpose === 'email_verification');
});

it('verifies the sign-up OTP and returns a flat token and user', function (): void {
    Mail::fake();

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Jane',
        'email' => 'jane@example.com',
        'phone' => '+201234567890',
        'password' => 'password123',
    ], ['Accept' => 'application/json'])->assertStatus(201);

    $code = null;
    Mail::assertQueued(SendOtpMail::class, function (SendOtpMail $mail) use (&$code): bool {
        $code = $mail->code;

        return true;
    });

    $response = $this->postJson('/api/v1/auth/register/verify', [
        'email' => 'jane@example.com',
        'code' => $code,
    ], ['Accept' => 'application/json']);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'token',
        'user' => ['id', 'name', 'email', 'phone', 'avatar_url'],
    ]);
    expect($response->json('token'))->not->toBeNull();
    $this->assertDatabaseMissing('users', ['email' => 'jane@example.com', 'email_verified_at' => null]);
});

it('rejects a wrong sign-up OTP with 422 and code otp.invalid', function (): void {
    Mail::fake();

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Bad Code',
        'email' => 'badcode@example.com',
        'password' => 'password123',
    ], ['Accept' => 'application/json'])->assertStatus(201);

    $response = $this->postJson('/api/v1/auth/register/verify', [
        'email' => 'badcode@example.com',
        'code' => '0000',
    ], ['Accept' => 'application/json']);

    $response->assertStatus(422);
    $response->assertJsonPath('code', 'otp.invalid');
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

it('rejects login for an unverified account with 403', function (): void {
    User::factory()->unverified()->create([
        'email' => 'pending@b.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'pending@b.com',
        'password' => 'password123',
    ], ['Accept' => 'application/json']);

    $response->assertStatus(403);
    $response->assertJsonPath('code', 'auth.email_not_verified');
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
