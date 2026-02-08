<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    protected $secretKey;
    protected $publicKey;
    protected $baseUrl;
    protected $callbackUrl;
    
    public function __construct()
    {
        $this->secretKey = config('paystack.secret_key');
        $this->publicKey = config('paystack.public_key');
        $this->baseUrl = 'https://api.paystack.co';
        $this->callbackUrl = config('paystack.callback_url');
    }
    
    /**
     * Check if Paystack is properly configured
     */
    public function isConfigured()
    {
        return !empty($this->secretKey) && !empty($this->publicKey);
    }
    
    /**
     * Initialize a payment transaction
     * 
     * @param float $amount Amount in Naira (NGN)
     * @param string $email Customer email
     * @param string $reference Unique transaction reference
     * @param array $metadata Additional data
     * @return array
     */
    public function initializeTransaction($amount, $email, $reference = null, $metadata = [])
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Paystack is not configured'
            ];
        }
        
        // Generate reference if not provided
        if (!$reference) {
            $reference = 'TXN_' . time() . '_' . uniqid();
        }
        
        // Convert amount to kobo (Paystack uses smallest currency unit)
        $amountInKobo = $amount * 100;
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/transaction/initialize', [
                'email' => $email,
                'amount' => $amountInKobo,
                'reference' => $reference,
                'callback_url' => $this->callbackUrl,
                'metadata' => $metadata,
                'currency' => 'NGN' // Nigerian Naira
            ]);
            
            $data = $response->json();
            
            if ($response->successful() && $data['status'] === true) {
                Log::info('Paystack transaction initialized', [
                    'reference' => $reference,
                    'amount' => $amount,
                    'email' => $email
                ]);
                
                return [
                    'success' => true,
                    'authorization_url' => $data['data']['authorization_url'],
                    'access_code' => $data['data']['access_code'],
                    'reference' => $data['data']['reference']
                ];
            }
            
            Log::error('Paystack initialization failed', [
                'response' => $data,
                'status' => $response->status()
            ]);
            
            return [
                'success' => false,
                'message' => $data['message'] ?? 'Failed to initialize transaction'
            ];
            
        } catch (\Exception $e) {
            Log::error('Paystack initialization exception', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verify a transaction
     * 
     * @param string $reference Transaction reference
     * @return array
     */
    public function verifyTransaction($reference)
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Paystack is not configured'
            ];
        }
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->get($this->baseUrl . '/transaction/verify/' . $reference);
            
            $data = $response->json();
            
            if ($response->successful() && $data['status'] === true) {
                $transactionData = $data['data'];
                
                Log::info('Paystack transaction verified', [
                    'reference' => $reference,
                    'status' => $transactionData['status'],
                    'amount' => $transactionData['amount'] / 100
                ]);
                
                return [
                    'success' => true,
                    'status' => $transactionData['status'], // success, failed, abandoned
                    'amount' => $transactionData['amount'] / 100, // Convert from kobo to naira
                    'currency' => $transactionData['currency'],
                    'reference' => $transactionData['reference'],
                    'paid_at' => $transactionData['paid_at'] ?? null,
                    'customer' => [
                        'email' => $transactionData['customer']['email'] ?? null,
                        'customer_code' => $transactionData['customer']['customer_code'] ?? null
                    ],
                    'metadata' => $transactionData['metadata'] ?? [],
                    'channel' => $transactionData['channel'] ?? null, // card, bank, ussd, etc
                ];
            }
            
            Log::error('Paystack verification failed', [
                'reference' => $reference,
                'response' => $data
            ]);
            
            return [
                'success' => false,
                'message' => $data['message'] ?? 'Verification failed'
            ];
            
        } catch (\Exception $e) {
            Log::error('Paystack verification exception', [
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Verification error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Initialize a transfer (withdrawal/payout)
     * 
     * @param string $recipientCode Recipient code from Paystack
     * @param float $amount Amount in Naira
     * @param string $reason Transfer reason
     * @param string $reference Unique reference
     * @return array
     */
    public function initiateTransfer($recipientCode, $amount, $reason = 'Withdrawal', $reference = null)
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Paystack is not configured'
            ];
        }
        
        if (!$reference) {
            $reference = 'TRF_' . time() . '_' . uniqid();
        }
        
        // Convert to kobo
        $amountInKobo = $amount * 100;
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/transfer', [
                'source' => 'balance',
                'reason' => $reason,
                'amount' => $amountInKobo,
                'recipient' => $recipientCode,
                'reference' => $reference
            ]);
            
            $data = $response->json();
            
            if ($response->successful() && $data['status'] === true) {
                Log::info('Paystack transfer initiated', [
                    'reference' => $reference,
                    'amount' => $amount,
                    'recipient' => $recipientCode
                ]);
                
                return [
                    'success' => true,
                    'transfer_code' => $data['data']['transfer_code'],
                    'reference' => $data['data']['reference'],
                    'status' => $data['data']['status'] // pending, success, failed
                ];
            }
            
            Log::error('Paystack transfer failed', [
                'response' => $data
            ]);
            
            return [
                'success' => false,
                'message' => $data['message'] ?? 'Transfer failed'
            ];
            
        } catch (\Exception $e) {
            Log::error('Paystack transfer exception', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Transfer error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Create a transfer recipient
     * 
     * @param string $accountNumber Bank account number
     * @param string $bankCode Bank code
     * @param string $name Account holder name
     * @return array
     */
    public function createTransferRecipient($accountNumber, $bankCode, $name)
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Paystack is not configured'
            ];
        }
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/transferrecipient', [
                'type' => 'nuban',
                'name' => $name,
                'account_number' => $accountNumber,
                'bank_code' => $bankCode,
                'currency' => 'NGN'
            ]);
            
            $data = $response->json();
            
            if ($response->successful() && $data['status'] === true) {
                return [
                    'success' => true,
                    'recipient_code' => $data['data']['recipient_code'],
                    'details' => $data['data']['details']
                ];
            }
            
            return [
                'success' => false,
                'message' => $data['message'] ?? 'Failed to create recipient'
            ];
            
        } catch (\Exception $e) {
            Log::error('Paystack create recipient exception', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get list of supported banks
     * 
     * @return array
     */
    public function getBanks()
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Paystack is not configured'
            ];
        }
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->get($this->baseUrl . '/bank', [
                'country' => 'nigeria',
                'perPage' => 100
            ]);
            
            $data = $response->json();
            
            if ($response->successful() && $data['status'] === true) {
                return [
                    'success' => true,
                    'banks' => $data['data']
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to fetch banks'
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verify bank account number
     * 
     * @param string $accountNumber
     * @param string $bankCode
     * @return array
     */
    public function verifyAccountNumber($accountNumber, $bankCode)
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Paystack is not configured'
            ];
        }
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->get($this->baseUrl . '/bank/resolve', [
                'account_number' => $accountNumber,
                'bank_code' => $bankCode
            ]);
            
            $data = $response->json();
            
            if ($response->successful() && $data['status'] === true) {
                return [
                    'success' => true,
                    'account_name' => $data['data']['account_name'],
                    'account_number' => $data['data']['account_number']
                ];
            }
            
            return [
                'success' => false,
                'message' => $data['message'] ?? 'Account verification failed'
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get public key for frontend
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }
    
    /**
     * Initialize Mobile Money (M-Pesa) payment
     * 
     * @param float $amount Amount in KES
     * @param string $phone M-Pesa phone number (254XXXXXXXXX)
     * @param string $email Customer email
     * @param string $reference Unique reference
     * @return array
     */
    public function initializeMobileMoney($amount, $phone, $email, $reference = null)
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Paystack is not configured'
            ];
        }
        
        if (!$reference) {
            $reference = 'MPESA_' . time() . '_' . uniqid();
        }
        
        // Convert amount to kobo (cents)
        $amountInKobo = $amount * 100;
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/transaction/initialize', [
                'email' => $email,
                'amount' => $amountInKobo,
                'reference' => $reference,
                'currency' => 'KES',
                'channels' => ['mobile_money'], // Only allow mobile money
                'metadata' => [
                    'phone_number' => $phone,
                    'payment_method' => 'mpesa',
                    'custom_fields' => [
                        [
                            'display_name' => 'Phone Number',
                            'variable_name' => 'phone_number',
                            'value' => $phone
                        ]
                    ]
                ],
                'callback_url' => $this->callbackUrl
            ]);
            
            $data = $response->json();
            
            if ($response->successful() && $data['status'] === true) {
                Log::info('Paystack M-Pesa initialized', [
                    'reference' => $reference,
                    'amount' => $amount,
                    'phone' => $phone
                ]);
                
                return [
                    'success' => true,
                    'authorization_url' => $data['data']['authorization_url'],
                    'access_code' => $data['data']['access_code'],
                    'reference' => $data['data']['reference']
                ];
            }
            
            return [
                'success' => false,
                'message' => $data['message'] ?? 'Failed to initialize payment'
            ];
            
        } catch (\Exception $e) {
            Log::error('Paystack M-Pesa initialization error', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Create M-Pesa transfer recipient
     * 
     * @param string $phone M-Pesa phone number (254XXXXXXXXX)
     * @param string $name Account holder name
     * @return array
     */
    public function createMpesaRecipient($phone, $name)
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Paystack is not configured'
            ];
        }
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/transferrecipient', [
                'type' => 'mobile_money',
                'name' => $name,
                'phone' => $phone,
                'currency' => 'KES',
                'metadata' => [
                    'mobile_money_provider' => 'mpesa'
                ]
            ]);
            
            $data = $response->json();
            
            if ($response->successful() && $data['status'] === true) {
                return [
                    'success' => true,
                    'recipient_code' => $data['data']['recipient_code'],
                    'details' => $data['data']
                ];
            }
            
            return [
                'success' => false,
                'message' => $data['message'] ?? 'Failed to create recipient'
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
}
