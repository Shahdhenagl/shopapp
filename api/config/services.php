<?php

declare(strict_types=1);

return [
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    // Social sign-in. Facebook tokens are verified against the Graph API (no
    // secret needed for token→profile). Google id tokens are validated against
    // tokeninfo and their audience checked against these client ids (comma
    // separated: the web/iOS/Android OAuth client ids the app may present).
    'google' => [
        'client_ids' => array_values(array_filter(
            array_map('trim', explode(',', (string) env('GOOGLE_CLIENT_IDS', '')))
        )),
    ],

    'facebook' => [
        'app_id' => env('FACEBOOK_APP_ID'),
        'app_secret' => env('FACEBOOK_APP_SECRET'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
];
