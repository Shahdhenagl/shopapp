<?php

declare(strict_types=1);

return [
    // Time-to-live for a reset code, in minutes.
    'ttl_minutes' => (int) env('OTP_TTL_MINUTES', 10),

    // Number of digits in the generated code. The Flutter client's OTP screens
    // expect a 4-digit code for both registration and password reset
    // (BACKEND.md §4), so 4 is the default.
    'length' => (int) env('OTP_LENGTH', 4),

    // Maximum verify attempts before a code is invalidated.
    'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),
];
