<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MpesaService
{
    protected $environment;
    protected $consumerKey;
    protected $consumerSecret;
    protected $shortcode;
    protected $passkey;
    protected $callbackUrl;
    protected $transactionType;
    protected $accountReference;
    protected $transactionDesc;

    public function __construct()
    {
        $this->environment = config('mpesa.environment', 'sandbox');
        $this->consumerKey = config('mpesa.consumer_key');
        $this->consumerSecret = config('mpesa.consumer_secret');
        $this->shortcode = config('mpesa.shortcode');
        $this->passkey = config('mpesa.passkey');
        $this->callbackUrl = config('mpesa.callback_url');
        $this->transactionType = config('mpesa.transaction_type');
        $this->accountReference = config('mpesa.account_reference');
        $this->transactionDesc = config('mpesa.transaction_desc');
    }

    /**
     * Get the API URL based on environment
     */
    protected function getUrl($type)
    {
        return config("mpesa.urls.{$this->environment}.{$type}");
    }

    /**
     * Generate OAuth Access Token
     */
    public function getAccessToken()
    {
        // Cache the token for 55 minutes (tokens expire in 1 hour)
        return Cache::remember('mpesa_access_token', 55 * 60, function () {
            $url = $this->getUrl('auth');
            
            $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
                ->get($url);

            if ($response->successful()) {
                $result = $response->json();
                return $result['access_token'] ?? null;
            }

            Log::error('M-Pesa Auth Error', [
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return null;
        });
    }

    /**
     * Generate password for STK Push
     */
    protected function generatePassword()
    {
        $timestamp = $this->getTimestamp();
        return base64_encode($this->shortcode . $this->passkey . $timestamp);
    }

    /**
     * Get current timestamp in required format
     */
    protected function getTimestamp()
    {
        return date('YmdHis');
    }

    /**
     * Format phone number to 254XXXXXXXXX format
     */
    public function formatPhoneNumber($phone)
    {
        // Remove any spaces, dashes, or special characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Handle different formats
        if (strlen($phone) === 9) {
            // Format: 7XXXXXXXX - add country code
            $phone = '254' . $phone;
        } elseif (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            // Format: 07XXXXXXXX - replace leading 0 with 254
            $phone = '254' . substr($phone, 1);
        } elseif (strlen($phone) === 12 && substr($phone, 0, 3) === '254') {
            // Already in correct format
        } elseif (strlen($phone) === 13 && substr($phone, 0, 4) === '+254') {
            // Remove the + sign
            $phone = substr($phone, 1);
        } else {
            // Return as is and let M-Pesa validate
            Log::warning('M-Pesa: Unusual phone format', ['phone' => $phone]);
        }

        return $phone;
    }

    /**
     * Initiate STK Push (Lipa Na M-Pesa Online)
     * 
     * @param string $phone Customer phone number
     * @param float $amount Amount to charge
     * @param string $accountRef Account reference (optional, uses user ID if not provided)
     * @param string $description Transaction description (optional)
     * @return array
     */
    public function stkPush($phone, $amount, $accountRef = null, $description = null)
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            return [
                'success' => false,
                'message' => 'Failed to authenticate with M-Pesa',
                'error_code' => 'AUTH_FAILED'
            ];
        }

        $timestamp = $this->getTimestamp();
        $password = $this->generatePassword();
        $formattedPhone = $this->formatPhoneNumber($phone);

        $payload = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => $this->transactionType,
            'Amount' => (int) $amount, // M-Pesa requires integer amount
            'PartyA' => $formattedPhone,
            'PartyB' => $this->shortcode,
            'PhoneNumber' => $formattedPhone,
            'CallBackURL' => $this->callbackUrl,
            'AccountReference' => $accountRef ?? $this->accountReference,
            'TransactionDesc' => $description ?? $this->transactionDesc,
        ];

        Log::info('M-Pesa STK Push Request', [
            'phone' => $formattedPhone,
            'amount' => $amount,
            'account_ref' => $accountRef ?? $this->accountReference
        ]);

        $url = $this->getUrl('stkpush');

        $response = Http::withToken($accessToken)
            ->post($url, $payload);

        $result = $response->json();

        Log::info('M-Pesa STK Push Response', ['response' => $result]);

        if ($response->successful() && isset($result['ResponseCode']) && $result['ResponseCode'] === '0') {
            return [
                'success' => true,
                'message' => 'STK Push sent successfully. Please check your phone.',
                'merchant_request_id' => $result['MerchantRequestID'] ?? null,
                'checkout_request_id' => $result['CheckoutRequestID'] ?? null,
                'response_code' => $result['ResponseCode'],
                'response_description' => $result['ResponseDescription'] ?? '',
                'customer_message' => $result['CustomerMessage'] ?? 'Please enter your M-Pesa PIN to complete the transaction',
            ];
        }

        return [
            'success' => false,
            'message' => $result['errorMessage'] ?? $result['ResponseDescription'] ?? 'Failed to initiate M-Pesa payment',
            'error_code' => $result['errorCode'] ?? $result['ResponseCode'] ?? 'UNKNOWN',
            'response' => $result
        ];
    }

    /**
     * Query STK Push transaction status
     * 
     * @param string $checkoutRequestId The CheckoutRequestID from STK Push response
     * @return array
     */
    public function stkQuery($checkoutRequestId)
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            return [
                'success' => false,
                'message' => 'Failed to authenticate with M-Pesa',
                'error_code' => 'AUTH_FAILED'
            ];
        }

        $timestamp = $this->getTimestamp();
        $password = $this->generatePassword();

        $payload = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId,
        ];

        $url = $this->getUrl('stkquery');

        $response = Http::withToken($accessToken)
            ->post($url, $payload);

        $result = $response->json();

        Log::info('M-Pesa STK Query Response', ['response' => $result]);

        if ($response->successful()) {
            $resultCode = $result['ResultCode'] ?? null;
            
            // ResultCode 0 means successful transaction
            if ($resultCode === '0' || $resultCode === 0) {
                return [
                    'success' => true,
                    'status' => 'completed',
                    'message' => 'Transaction completed successfully',
                    'result_code' => $resultCode,
                    'result_desc' => $result['ResultDesc'] ?? '',
                ];
            }
            
            // ResultCode 1032 means cancelled by user
            if ($resultCode === '1032' || $resultCode === 1032) {
                return [
                    'success' => false,
                    'status' => 'cancelled',
                    'message' => 'Transaction was cancelled by user',
                    'result_code' => $resultCode,
                    'result_desc' => $result['ResultDesc'] ?? '',
                ];
            }

            // Other result codes indicate failure or pending
            return [
                'success' => false,
                'status' => 'failed',
                'message' => $result['ResultDesc'] ?? 'Transaction failed',
                'result_code' => $resultCode,
                'result_desc' => $result['ResultDesc'] ?? '',
            ];
        }

        return [
            'success' => false,
            'status' => 'error',
            'message' => $result['errorMessage'] ?? 'Failed to query transaction status',
            'error_code' => $result['errorCode'] ?? 'UNKNOWN',
        ];
    }

    /**
     * Process M-Pesa callback data
     * 
     * @param array $callbackData Raw callback data from M-Pesa
     * @return array Parsed callback data
     */
    public function processCallback($callbackData)
    {
        Log::info('M-Pesa Callback Received', ['data' => $callbackData]);

        $stkCallback = $callbackData['Body']['stkCallback'] ?? null;

        if (!$stkCallback) {
            return [
                'success' => false,
                'message' => 'Invalid callback data'
            ];
        }

        $merchantRequestId = $stkCallback['MerchantRequestID'] ?? null;
        $checkoutRequestId = $stkCallback['CheckoutRequestID'] ?? null;
        $resultCode = $stkCallback['ResultCode'] ?? null;
        $resultDesc = $stkCallback['ResultDesc'] ?? null;

        // ResultCode 0 means successful
        if ($resultCode === 0 || $resultCode === '0') {
            $callbackMetadata = $stkCallback['CallbackMetadata']['Item'] ?? [];
            
            $metadata = [];
            foreach ($callbackMetadata as $item) {
                $metadata[$item['Name']] = $item['Value'] ?? null;
            }

            return [
                'success' => true,
                'merchant_request_id' => $merchantRequestId,
                'checkout_request_id' => $checkoutRequestId,
                'result_code' => $resultCode,
                'result_desc' => $resultDesc,
                'amount' => $metadata['Amount'] ?? null,
                'mpesa_receipt_number' => $metadata['MpesaReceiptNumber'] ?? null,
                'transaction_date' => $metadata['TransactionDate'] ?? null,
                'phone_number' => $metadata['PhoneNumber'] ?? null,
            ];
        }

        return [
            'success' => false,
            'merchant_request_id' => $merchantRequestId,
            'checkout_request_id' => $checkoutRequestId,
            'result_code' => $resultCode,
            'result_desc' => $resultDesc,
        ];
    }

    /**
     * Check if M-Pesa is properly configured (for STK Push deposits)
     */
    public function isConfigured()
    {
        return !empty($this->consumerKey) 
            && !empty($this->consumerSecret) 
            && !empty($this->shortcode) 
            && !empty($this->passkey)
            && !empty($this->callbackUrl);
    }

    /**
     * Check if M-Pesa B2C is properly configured (for withdrawals)
     */
    public function isB2CConfigured()
    {
        return !empty($this->consumerKey) 
            && !empty($this->consumerSecret) 
            && !empty($this->shortcode) 
            && !empty(config('mpesa.b2c.initiator_name'))
            && !empty(config('mpesa.b2c.security_credential'))
            && !empty(config('mpesa.b2c.result_url'))
            && !empty(config('mpesa.b2c.timeout_url'));
    }

    /**
     * Get B2C API URL based on environment
     */
    protected function getB2CUrl()
    {
        return config("mpesa.b2c_urls.{$this->environment}");
    }

    /**
     * Initiate B2C Payment (Business to Customer) - For Withdrawals
     * 
     * @param string $phone Customer phone number to receive money
     * @param float $amount Amount to send
     * @param string $transactionId Unique transaction ID for tracking
     * @param string $remarks Optional remarks
     * @param string $occasion Optional occasion
     * @return array
     */
    public function b2cPayment($phone, $amount, $transactionId, $remarks = null, $occasion = null)
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            return [
                'success' => false,
                'message' => 'Failed to authenticate with M-Pesa',
                'error_code' => 'AUTH_FAILED'
            ];
        }

        $formattedPhone = $this->formatPhoneNumber($phone);
        
        $payload = [
            'InitiatorName' => config('mpesa.b2c.initiator_name'),
            'SecurityCredential' => config('mpesa.b2c.security_credential'),
            'CommandID' => config('mpesa.b2c.command_id', 'BusinessPayment'),
            'Amount' => (int) $amount,
            'PartyA' => $this->shortcode,
            'PartyB' => $formattedPhone,
            'Remarks' => $remarks ?? config('mpesa.b2c.remarks', 'Withdrawal'),
            'QueueTimeOutURL' => config('mpesa.b2c.timeout_url'),
            'ResultURL' => config('mpesa.b2c.result_url'),
            'Occasion' => $occasion ?? config('mpesa.b2c.occasion', $transactionId),
        ];

        Log::info('M-Pesa B2C Request', [
            'phone' => $formattedPhone,
            'amount' => $amount,
            'transaction_id' => $transactionId
        ]);

        $url = $this->getB2CUrl();

        $response = Http::withToken($accessToken)
            ->post($url, $payload);

        $result = $response->json();

        Log::info('M-Pesa B2C Response', ['response' => $result]);

        if ($response->successful() && isset($result['ResponseCode']) && $result['ResponseCode'] === '0') {
            return [
                'success' => true,
                'message' => 'Withdrawal initiated successfully',
                'conversation_id' => $result['ConversationID'] ?? null,
                'originator_conversation_id' => $result['OriginatorConversationID'] ?? null,
                'response_code' => $result['ResponseCode'],
                'response_description' => $result['ResponseDescription'] ?? '',
            ];
        }

        return [
            'success' => false,
            'message' => $result['errorMessage'] ?? $result['ResponseDescription'] ?? 'Failed to initiate withdrawal',
            'error_code' => $result['errorCode'] ?? $result['ResponseCode'] ?? 'UNKNOWN',
            'response' => $result
        ];
    }

    /**
     * Process B2C callback data (for withdrawal results)
     * 
     * @param array $callbackData Raw callback data from M-Pesa
     * @return array Parsed callback data
     */
    public function processB2CCallback($callbackData)
    {
        Log::info('M-Pesa B2C Callback Received', ['data' => $callbackData]);

        $result = $callbackData['Result'] ?? null;

        if (!$result) {
            return [
                'success' => false,
                'message' => 'Invalid B2C callback data'
            ];
        }

        $resultType = $result['ResultType'] ?? null;
        $resultCode = $result['ResultCode'] ?? null;
        $resultDesc = $result['ResultDesc'] ?? null;
        $conversationId = $result['ConversationID'] ?? null;
        $originatorConversationId = $result['OriginatorConversationID'] ?? null;
        $transactionId = $result['TransactionID'] ?? null;

        // ResultCode 0 means successful
        if ($resultCode === 0 || $resultCode === '0') {
            // Parse result parameters
            $resultParams = $result['ResultParameters']['ResultParameter'] ?? [];
            
            $params = [];
            foreach ($resultParams as $param) {
                $params[$param['Key']] = $param['Value'] ?? null;
            }

            return [
                'success' => true,
                'conversation_id' => $conversationId,
                'originator_conversation_id' => $originatorConversationId,
                'transaction_id' => $transactionId,
                'result_code' => $resultCode,
                'result_desc' => $resultDesc,
                'amount' => $params['TransactionAmount'] ?? null,
                'receiver_phone' => $params['ReceiverPartyPublicName'] ?? null,
                'transaction_receipt' => $params['TransactionReceipt'] ?? null,
                'transaction_completed_time' => $params['TransactionCompletedDateTime'] ?? null,
                'b2c_utility_balance' => $params['B2CUtilityAccountAvailableFunds'] ?? null,
                'b2c_working_balance' => $params['B2CWorkingAccountAvailableFunds'] ?? null,
            ];
        }

        return [
            'success' => false,
            'conversation_id' => $conversationId,
            'originator_conversation_id' => $originatorConversationId,
            'result_code' => $resultCode,
            'result_desc' => $resultDesc,
        ];
    }

    /**
     * Process B2C timeout callback
     * 
     * @param array $callbackData Raw timeout callback data
     * @return array
     */
    public function processB2CTimeout($callbackData)
    {
        Log::warning('M-Pesa B2C Timeout Received', ['data' => $callbackData]);

        $result = $callbackData['Result'] ?? null;

        return [
            'success' => false,
            'is_timeout' => true,
            'conversation_id' => $result['ConversationID'] ?? null,
            'originator_conversation_id' => $result['OriginatorConversationID'] ?? null,
            'result_code' => $result['ResultCode'] ?? null,
            'result_desc' => $result['ResultDesc'] ?? 'Transaction timed out',
        ];
    }
}
