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
        try {
            $payload = [
                'external_reference' => $data['reference'],
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'KES',
                'customer_email' => $data['email'],
                'callback_url' => $data['callback_url'] ?? route('ipn.visermart'),
                'return_url' => $data['return_url'] ?? null,
            ];

            // Merge extra metadata and channels if provided
            if (isset($data['metadata'])) {
                $payload['metadata'] = $data['metadata'];
            }
            if (isset($data['channels'])) {
                $payload['channels'] = $data['channels'];
            }

            $request = Http::withHeaders([
                'X-Aetheric-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]);

            // Only bypass SSL for local/proxy domains to maintain security for true live environments
            // If we're on local XAMPP connecting to docuworld.store, we usually need to bypass
            if (str_contains($this->baseUrl, 'localhost') || 
                str_contains($this->baseUrl, '127.0.0.1') || 
                str_contains($this->baseUrl, 'docuworld.store')) {
                $request->withoutVerifying();
            }

            $response = $request->post($this->baseUrl . '/api/external/payment/initiate', $payload);

            if ($response->successful()) {
                return $response->json();
            }

            $errorBody = $response->body();
            Log::error('ViserMart payment initiation failed', [
                'status' => $response->status(),
                'body' => $errorBody,
                'url' => $this->baseUrl . '/api/external/payment/initiate',
                'data' => $data
            ]);

            return ['error' => 'Could not initiate payment with ViserMart. Status: ' . $response->status() . ' Response: ' . $errorBody];
        } catch (\Exception $e) {
            Log::error('Exception during ViserMart payment initiation', [
                'message' => $e->getMessage(),
                'url' => $this->baseUrl . '/api/external/payment/initiate',
                'trace' => $e->getTraceAsString()
            ]);
            return ['error' => 'Payment service connection error: ' . $e->getMessage()];
        }
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