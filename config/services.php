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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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
      'mailgun' => [
      'domain' => env('MAILGUN_DOMAIN'),
      'secret' => env('MAILGUN_SECRET'),
      'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
      'scheme' => 'https',
  ],

    // Hubtel "verify MoMo name" lookup (Employees > Cash > Verify). Keys belong in .env, never in code.
    'hubtel_momo' => [
        'merchant_account' => env('HUBTEL_MOMO_MERCHANT_ACCOUNT'),
        'client_id' => env('HUBTEL_MOMO_CLIENT_ID'),
        'client_secret' => env('HUBTEL_MOMO_CLIENT_SECRET'),
        'verify_url' => env('HUBTEL_MOMO_VERIFY_URL', 'https://rnv.hubtel.com/v2/merchantaccount/merchants/{merchant}/mobilemoney/verify'),
        'timeout' => (int) env('HUBTEL_MOMO_TIMEOUT', 15),
    ],

];
