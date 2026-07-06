<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | API Message Language Lines
    |--------------------------------------------------------------------------
    |
    | Custom message keys returned by the MODIST API across the various
    | controllers and actions. Resolved through the Accept-Language header.
    |
    */

    // Generic
    'unauthenticated' => 'Unauthenticated.',
    'not_found' => 'Resource not found.',
    'server_error' => 'Something went wrong. Please try again.',

    // Authentication
    'invalid_credentials' => 'The provided credentials are incorrect.',
    'invalid_refresh_token' => 'The refresh token is invalid or has expired.',
    'logged_out' => 'Logged out successfully.',
    'email_in_use' => 'An account with this email already exists.',
    'email_not_verified' => 'Please verify your email before signing in.',

    // Sign-up email verification (OTP)
    'otp_invalid' => 'That code is incorrect or has expired.',
    'verification_code_sent' => 'A verification code has been sent to your email.',
    'verification_code_resent' => 'A new verification code has been sent.',
    'verification_code_subject' => 'Your MODIST verification code',
    'verification_code_intro' => 'Your MODIST email verification code is',
    'otp_expiry_notice' => 'It expires in :minutes minutes. If you did not request this, ignore this email.',

    // Password reset
    'reset_code_sent' => 'If the account exists, a reset code has been sent.',
    'reset_code_invalid' => 'The reset code is invalid or has expired.',
    'reset_code_verified' => 'Reset code verified.',
    'reset_code_resent' => 'A new reset code has been sent.',
    'reset_code_subject' => 'Your MODIST password reset code',
    'reset_code_intro' => 'Your MODIST password reset code is',
    'password_reset' => 'Your password has been reset.',
    'no_verified_code' => 'No verified reset code found for this email.',

    // Reviews
    'review_created' => 'Your review has been submitted.',

    // Catalog
    'product_not_found' => 'Product not found.',

    // Cart
    'cart_empty' => 'Your cart is empty.',
    'cart_line_not_found' => 'Cart line not found.',
    'promo_invalid' => 'This promo code is invalid or has expired.',

    // Checkout & payments
    'payment_declined' => 'Your payment was declined.',
    'unsupported_payment_method' => 'This payment method is not supported.',

];
