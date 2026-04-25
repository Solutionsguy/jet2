<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ViserMartPaymentService
{
    protected $baseUrl;
    protected $apiKey;
    protected $webhookSecret;

    public function __construct()
    {
        $this->baseUrl = env('VISERMART_BASE_URL');
        $this->apiKey = env('VISERMART_API_KEY');
        $this->webhookSecret = env('VISERMART_WEBHOOK_SECRET');
    }

    /**
     * Initiate payment on ViserMart
     */
    public function initiatePayment($data)
    {
        $maxRetries = 3;
        $attempt = 0;
        $lastError = 'Unknown error';

        // Check payment mode: 'paystack' or 'mpesa'
        $mode = \App\Models\Setting::where('category', 'payment_gateway_mode')->value('value') ?? 'paystack';
        $endpoint = ($mode === 'mpesa') ? '/api/external/payment/mpesa-stk' : '/api/external/payment/initiate';

        while ($attempt < $maxRetries) {
            $attempt++;
            try {
                $payload = [
                    'external_reference' => $data['reference'],
                    'amount' => $data['amount'],
                    'currency' => $data['currency'] ?? 'KES',
                    'customer_email' => $data['email'],
                    'callback_url' => $data['callback_url'] ?? route('ipn.visermart'),
                    'return_url' => $data['return_url'] ?? null,
                ];

                // If M-Pesa mode, we need the phone number
                if ($mode === 'mpesa') {
                    $payload['phone'] = $data['phone'] ?? $data['metadata']['phone_number'] ?? null;
                    if (!$payload['phone']) {
                        return ['error' => 'Phone number is required for direct M-Pesa STK Push.'];
                    }
                }

                if (isset($data['metadata'])) $payload['metadata'] = $data['metadata'];
                if (isset($data['channels'])) $payload['channels'] = $data['channels'];

                $request = Http::withHeaders([
                    'X-Aetheric-Key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->timeout(20) // Give the proxy 20 seconds to respond
                ->connectTimeout(10); // Wait 10 seconds for initial connection

                if (str_contains($this->baseUrl, 'localhost') || 
                    str_contains($this->baseUrl, '127.0.0.1') || 
                    str_contains($this->baseUrl, 'docuworld.store')) {
                    $request->withoutVerifying();
                }

                $response = $request->post($this->baseUrl . $endpoint, $payload);

                if ($response->successful()) {
                    $resData = $response->json();
                    
                    // Standardize response for the controller
                    if ($mode === 'mpesa') {
                        $resData['status'] = 'success';
                        $resData['is_stk'] = true;
                    }
                    
                    return $resData;
                }

                $errorBody = $response->body();
                $lastError = "Status: " . $response->status() . " Body: " . $errorBody;
                
                Log::warning("ViserMart ($mode) Initiation attempt $attempt failed", [
                    'status' => $response->status(),
                    'error' => $errorBody
                ]);

            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                Log::error("ViserMart Connection attempt $attempt failed", ['error' => $lastError]);
            }

            // Wait briefly before retrying (0.5s, 1s)
            if ($attempt < $maxRetries) usleep(500000 * $attempt);
        }

        return ['error' => 'ViserMart not reachable after several attempts. Last error: ' . $lastError];
    }

    /**
     * Verify payment on ViserMart
     */
    public function verifyPayment($reference)
    {
        try {
            $request = Http::withHeaders([
                'X-Aetheric-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]);

            if (str_contains($this->baseUrl, 'localhost') || 
                str_contains($this->baseUrl, '127.0.0.1') || 
                str_contains($this->baseUrl, 'docuworld.store')) {
                $request->withoutVerifying();
            }

            $response = $request->post($this->baseUrl . '/api/external/payment/verify', [
                'external_reference' => $reference
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('ViserMart payment verification failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'reference' => $reference
            ]);

            return ['status' => 'error', 'message' => 'Verification failed'];
        } catch (\Exception $e) {
            Log::error('Exception during ViserMart payment verification', [
                'message' => $e->getMessage()
            ]);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Initiate transfer (withdrawal) on ViserMart
     */
    public function initiateTransfer($data)
    {
        try {
            $request = Http::withHeaders([
                'X-Aetheric-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]);

            if (str_contains($this->baseUrl, 'localhost') || 
                str_contains($this->baseUrl, '127.0.0.1') || 
                str_contains($this->baseUrl, 'docuworld.store')) {
                $request->withoutVerifying();
            }

            $response = $request->post($this->baseUrl . '/api/external/payment/transfer', [
                'external_reference' => $data['reference'],
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'KES',
                'phone' => $data['phone'],
                'customer_name' => $data['name'],
                'customer_email' => $data['email'] ?? null,
                'callback_url' => $data['callback_url'] ?? route('ipn.visermart'),
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('ViserMart transfer initiation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'data' => $data
            ]);

            return ['error' => 'Could not initiate transfer with ViserMart'];
        } catch (\Exception $e) {
            Log::error('Exception during ViserMart transfer initiation', [
                'message' => $e->getMessage()
            ]);
            return ['error' => 'Transfer service connection error'];
        }
    }

    /**
     * Verify ViserMart Webhook Signature using raw payload
     */
    public function verifySignatureRaw($rawPayload, $signature)
    {
        $expectedSignature = hash_hmac('sha256', $rawPayload, $this->webhookSecret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify ViserMart Webhook Signature using array (Legacy)
     */
    public function verifySignature($payload, $signature)
    {
        $expectedSignature = hash_hmac('sha256', json_encode($payload), $this->webhookSecret);
        return hash_equals($expectedSignature, $signature);
    }
}