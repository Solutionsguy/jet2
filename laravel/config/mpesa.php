<?php

return [
    /*
    |--------------------------------------------------------------------------
    | M-Pesa Environment
    |--------------------------------------------------------------------------
    | Set to 'sandbox' for testing or 'production' for live transactions
    */
    'environment' => env('MPESA_ENVIRONMENT', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | M-Pesa Consumer Key
    |--------------------------------------------------------------------------
    | Your M-Pesa API Consumer Key from Safaricom Developer Portal
    */
    'consumer_key' => env('MPESA_CONSUMER_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | M-Pesa Consumer Secret
    |--------------------------------------------------------------------------
    | Your M-Pesa API Consumer Secret from Safaricom Developer Portal
    */
    'consumer_secret' => env('MPESA_CONSUMER_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | M-Pesa Shortcode (Paybill/Till Number)
    |--------------------------------------------------------------------------
    | Your M-Pesa Business Shortcode (Paybill or Till Number)
    */
    'shortcode' => env('MPESA_SHORTCODE', ''),

    /*
    |--------------------------------------------------------------------------
    | M-Pesa Passkey
    |--------------------------------------------------------------------------
    | Your M-Pesa Lipa Na M-Pesa Online Passkey
    */
    'passkey' => env('MPESA_PASSKEY', ''),

    /*
    |--------------------------------------------------------------------------
    | M-Pesa Callback URL
    |--------------------------------------------------------------------------
    | The URL where M-Pesa will send transaction results
    | Must be publicly accessible (HTTPS required in production)
    */
    'callback_url' => env('MPESA_CALLBACK_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | M-Pesa API URLs
    |--------------------------------------------------------------------------
    */
    'urls' => [
        'sandbox' => [
            'auth' => 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials',
            'stkpush' => 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest',
            'stkquery' => 'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query',
        ],
        'production' => [
            'auth' => 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials',
            'stkpush' => 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest',
            'stkquery' => 'https://api.safaricom.co.ke/mpesa/stkpushquery/v1/query',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction Type
    |--------------------------------------------------------------------------
    | CustomerPayBillOnline for Paybill, CustomerBuyGoodsOnline for Till
    */
    'transaction_type' => env('MPESA_TRANSACTION_TYPE', 'CustomerPayBillOnline'),

    /*
    |--------------------------------------------------------------------------
    | Account Reference
    |--------------------------------------------------------------------------
    | The account reference shown to the customer (your business name)
    */
    'account_reference' => env('MPESA_ACCOUNT_REFERENCE', 'Aviator'),

    /*
    |--------------------------------------------------------------------------
    | Transaction Description
    |--------------------------------------------------------------------------
    */
    'transaction_desc' => env('MPESA_TRANSACTION_DESC', 'Deposit to Aviator'),

    /*
    |--------------------------------------------------------------------------
    | B2C (Business to Customer) Configuration - For Withdrawals
    |--------------------------------------------------------------------------
    */
    'b2c' => [
        // B2C Initiator Name (provided by Safaricom)
        'initiator_name' => env('MPESA_B2C_INITIATOR_NAME', ''),
        
        // B2C Security Credential (encrypted initiator password)
        'security_credential' => env('MPESA_B2C_SECURITY_CREDENTIAL', ''),
        
        // B2C Command ID: 'BusinessPayment', 'SalaryPayment', or 'PromotionPayment'
        'command_id' => env('MPESA_B2C_COMMAND_ID', 'BusinessPayment'),
        
        // B2C Result URL - receives successful transaction results
        'result_url' => env('MPESA_B2C_RESULT_URL', ''),
        
        // B2C Timeout URL - receives timeout notifications
        'timeout_url' => env('MPESA_B2C_TIMEOUT_URL', ''),
        
        // B2C Remarks (shown in M-Pesa statement)
        'remarks' => env('MPESA_B2C_REMARKS', 'Aviator Withdrawal'),
        
        // B2C Occasion (optional reference)
        'occasion' => env('MPESA_B2C_OCCASION', 'Withdrawal'),
    ],

    /*
    |--------------------------------------------------------------------------
    | B2C API URLs
    |--------------------------------------------------------------------------
    */
    'b2c_urls' => [
        'sandbox' => 'https://sandbox.safaricom.co.ke/mpesa/b2c/v1/paymentrequest',
        'production' => 'https://api.safaricom.co.ke/mpesa/b2c/v1/paymentrequest',
    ],
];
