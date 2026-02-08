<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Paystack Secret Key
    |--------------------------------------------------------------------------
    |
    | Your Paystack secret key from your Paystack Dashboard
    | https://dashboard.paystack.com/#/settings/developer
    |
    */
    'secret_key' => env('PAYSTACK_SECRET_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Paystack Public Key
    |--------------------------------------------------------------------------
    |
    | Your Paystack public key from your Paystack Dashboard
    |
    */
    'public_key' => env('PAYSTACK_PUBLIC_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Payment Callback URL
    |--------------------------------------------------------------------------
    |
    | URL where Paystack will redirect users after payment
    |
    */
    'callback_url' => env('PAYSTACK_CALLBACK_URL', env('APP_URL') . '/paystack/callback'),

    /*
    |--------------------------------------------------------------------------
    | Minimum Deposit Amount (in KES - Set to 1 for testing)
    |--------------------------------------------------------------------------
    */
    'min_deposit' => env('PAYSTACK_MIN_DEPOSIT', 1),

    /*
    |--------------------------------------------------------------------------
    | Minimum Withdrawal Amount (in KES - Set to 1 for testing)
    |--------------------------------------------------------------------------
    */
    'min_withdrawal' => env('PAYSTACK_MIN_WITHDRAWAL', 1),

    /*
    |--------------------------------------------------------------------------
    | Merchant Email
    |--------------------------------------------------------------------------
    |
    | Your business email for receiving notifications
    |
    */
    'merchant_email' => env('PAYSTACK_MERCHANT_EMAIL', 'support@yourdomain.com'),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | Supported: NGN (Nigerian Naira), GHS (Ghanaian Cedi), ZAR (South African Rand), USD
    |
    */
    'currency' => env('PAYSTACK_CURRENCY', 'NGN'),

    /*
    |--------------------------------------------------------------------------
    | Currency Symbol
    |--------------------------------------------------------------------------
    */
    'currency_symbol' => env('PAYSTACK_CURRENCY_SYMBOL', '₦'),
];
