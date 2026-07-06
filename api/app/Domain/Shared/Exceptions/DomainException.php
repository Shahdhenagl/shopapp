<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Base class for business-rule violations.
 *
 * Carries an HTTP status, an optional stable machine code, and an optional
 * validation-style error bag so the global JSON exception handler in
 * bootstrap/app.php can render the { code?, message, errors? } envelope the
 * Flutter client expects (BACKEND.md §2).
 */
class DomainException extends RuntimeException
{
    /**
     * @param  array<string, array<int, string>>  $errors
     */
    public function __construct(
        string $message,
        private readonly int $status = 422,
        private readonly array $errors = [],
        private readonly ?string $errorCode = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function status(): int
    {
        return $this->status;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Stable, locale/tenant-independent machine code from the client's error
     * catalog (BACKEND.md §2). Null when no specific code applies — the client
     * then falls back to the operation's default message.
     */
    public function errorCode(): ?string
    {
        return $this->errorCode;
    }
}
