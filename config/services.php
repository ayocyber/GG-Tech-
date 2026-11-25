<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    /*
    | PayGate Global (Flooz / T-Money). "demo" uses a fake payment page on this
    | site so checkout can be tested without real money; "live" sends customers
    | to PayGate with the API key from the merchant dashboard.
    */
    'paygate' => [
        'mode' => env('PAYGATE_MODE', 'demo'),
        'api_key' => env('PAYGATE_API_KEY'),
        'base_url' => env('PAYGATE_BASE_URL', 'https://paygateglobal.com'),
    ],

    /*
    | Automatic exchange rates for the display currencies: European Central Bank
    | rates through Frankfurter (free, no key). Rates older than the limit are
    | refreshed in the background; staff can override any rate in the admin.
    */
    'exchange_rates' => [
        'url' => env('EXCHANGE_RATES_URL', 'https://api.frankfurter.dev/v1'),
        'stale_after_hours' => (int) env('EXCHANGE_RATES_STALE_AFTER_HOURS', 12),
    ],

    /*
    | FedaPay (mobile money and cards). These apply until staff save keys under
    | Settings > Payments in the admin. "sandbox" uses FedaPay's test API.
    */
    'fedapay' => [
        'mode' => env('FEDAPAY_MODE', 'sandbox'),
        'secret_key' => env('FEDAPAY_SECRET_KEY'),
        'webhook_secret' => env('FEDAPAY_WEBHOOK_SECRET'),
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

];
