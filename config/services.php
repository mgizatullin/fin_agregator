<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'leadgid' => [
        'base_url' => 'https://api.leadgid.com/offers/v1/affiliates/',
        'token' => env('LEADGID_API_TOKEN'),
    ],

    'yandex_maps' => [
        'key' => env('YANDEX_MAPS_API_KEY'),
    ],

    'deposit_calculator' => [
        'cb_key_rate_percent' => (float) env('DEPOSIT_CB_KEY_RATE', 18),
    ],

    'banki' => [
        'login' => env('BANKI_LOGIN', ''),
        'password' => env('BANKI_PASSWORD', ''),
    ],

];
