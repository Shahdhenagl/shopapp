<?php

declare(strict_types=1);

use App\Domain\Auth\Contracts\OtpStore;

beforeEach(function (): void {
    $this->store = app(OtpStore::class);
});

it('issues a numeric code', function (): void {
    $code = $this->store->issue('user@example.com');

    expect($code)->toBeString();
    expect(ctype_digit($code))->toBeTrue();
});

it('verifies a correct code and marks it verified', function (): void {
    $email = 'user@example.com';
    $code = $this->store->issue($email);

    expect($this->store->verify($email, $code))->toBeTrue();
    expect($this->store->hasVerified($email))->toBeTrue();
});

it('rejects a wrong code', function (): void {
    $email = 'user@example.com';
    $this->store->issue($email);

    expect($this->store->verify($email, '000000'))->toBeFalse();
});

it('consume invalidates a verified code', function (): void {
    $email = 'user@example.com';
    $code = $this->store->issue($email);

    expect($this->store->verify($email, $code))->toBeTrue();
    expect($this->store->hasVerified($email))->toBeTrue();

    $this->store->consume($email);

    expect($this->store->hasVerified($email))->toBeFalse();
});
