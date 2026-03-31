<?php

$mode = env('MOYASAR_MODE', 'test');

$apiKey = $mode === 'live'
    ? env('MOYASAR_LIVE_API_KEY', env('MOYASAR_API_KEY'))
    : env('MOYASAR_TEST_API_KEY', env('MOYASAR_API_KEY'));

$publishableKey = $mode === 'live'
    ? env('MOYASAR_LIVE_PUBLISHABLE_KEY', env('MOYASAR_PUBLISHABLE_KEY'))
    : env('MOYASAR_TEST_PUBLISHABLE_KEY', env('MOYASAR_PUBLISHABLE_KEY'));

return [
    /*
    |--------------------------------------------------------------------------
    | Moyasar API Configuration
    |--------------------------------------------------------------------------
    |
    | API keys for Moyasar payment gateway integration
    | Get your keys from: https://dashboard.moyasar.com/settings/api-keys
    |
    */

    'api_key' => $apiKey,
    'publishable_key' => $publishableKey,
    'webhook_secret' => env('MOYASAR_WEBHOOK_SECRET'),
    'mode' => $mode,

    /*
    |--------------------------------------------------------------------------
    | Moyasar API Endpoints
    |--------------------------------------------------------------------------
    */

    'base_url' => 'https://api.moyasar.com/v1',

    /*
    |--------------------------------------------------------------------------
    | Invoice Settings
    |--------------------------------------------------------------------------
    */

    'invoice' => [
        'currency' => env('MOYASAR_CURRENCY', 'SAR'),
        'expires_at_days' => 7, // Invoice expires after 7 days
    ],

    /*
    |--------------------------------------------------------------------------
    | Wallet Recharge Limits (in SAR)
    |--------------------------------------------------------------------------
    */

    'recharge' => [
        'min_amount' => 10,
        'max_amount' => 10000,
    ],
];
