<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PayTabs Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for PayTabs payment gateway integration.
    | For sandbox testing, use the sandbox base URL and test credentials.
    |
    */

    // Server Key from PayTabs Dashboard
    'server_key' => env('PAYTABS_SERVER_KEY', ''),

    // Profile ID from PayTabs Dashboard
    'profile_id' => env('PAYTABS_PROFILE_ID', ''),

    // Base URL for PayTabs API
    // Sandbox: https://secure.paytabs.sa (for testing)
    // Production: https://secure.paytabs.sa
    'base_url' => env('PAYTABS_BASE_URL', 'https://secure.paytabs.sa'),

    // Currency code (ISO 4217)
    'currency' => env('PAYTABS_CURRENCY', 'SAR'),

    // Enable sandbox mode
    'sandbox' => env('PAYTABS_SANDBOX', true),

];
