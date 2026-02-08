<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PaystackService;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaystackController extends Controller
{
    protected $paystack;
    
    public function __construct(PaystackService $paystack)
    {
        $this->paystack = $paystack;
    }
    
    /**
     * Initialize a deposit transaction
     */
    public function initializeDeposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:' . config('paystack.min_deposit', 1),
            'email' => 'required|email'
        ]);
        
        if (!$this->paystack->isConfigured()) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Paystack payment is not available. Please contact support.'
            ], 503);
        }
        
        $userId = user('id');
        $amount = floatval($request->amount);
        $email = $request->email;
        
        // Generate unique reference
        $reference = 'DEP_' . $userId . '_' . time();
        
        // Create pending transaction record
        try {
            $transaction = new Transaction();
            $transaction->userid = $userId;
            $transaction->amount = $amount;
            $transaction->category = 'deposit';
            $transaction->platform = 'Paystack';
            $transaction->transactionno = $reference;
            $transaction->status = 'pending';
            $transaction->remark = 'Paystack deposit initiated - ' . $email;
            $transaction->save();
            
            // Initialize Paystack payment
            $result = $this->paystack->initializeTransaction(
                $amount,
                $email,
                $reference,
                [
                    'user_id' => $userId,
                    'transaction_id' => $transaction->id,
                    'custom_fields' => [
                        [
                            'display_name' => 'User ID',
                            'variable_name' => 'user_id',
                            'value' => $userId
                        ]
                    ]
                ]
            );
            
            if ($result['success']) {
                Log::info('Paystack deposit initialized', [
                    'user_id' => $userId,
                    'amount' => $amount,
                    'reference' => $reference
                ]);
                
                return response()->json([
                    'isSuccess' => true,
                    'message' => 'Redirecting to payment page...',
                    'authorization_url' => $result['authorization_url'],
                    'reference' => $result['reference']
                ]);
            }
            
            // Failed to initialize
            $transaction->status = 'failed';
            $transaction->remark = 'Initialization failed: ' . $result['message'];
            $transaction->save();
            
            return response()->json([
                'isSuccess' => false,
                'message' => $result['message']
            ], 400);
            
        } catch (\Exception $e) {
            Log::error('Paystack deposit initialization error', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to initialize payment: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Handle Paystack callback after payment
     */
    public function handleCallback(Request $request)
    {
        $reference = $request->query('reference');
        
        if (!$reference) {
            return redirect('/deposit?msg=Error&details=No reference provided');
        }
        
        // Verify the transaction
        $result = $this->paystack->verifyTransaction($reference);
        
        if (!$result['success']) {
            Log::error('Paystack verification failed', [
                'reference' => $reference,
                'message' => $result['message']
            ]);
            
            return redirect('/deposit?msg=Error&details=' . urlencode($result['message']));
        }
        
        // Check transaction status
        if ($result['status'] !== 'success') {
            Log::warning('Paystack transaction not successful', [
                'reference' => $reference,
                'status' => $result['status']
            ]);
            
            return redirect('/deposit?msg=Failed&details=Payment was not completed');
        }
        
        // Find the transaction
        $transaction = Transaction::where('transactionno', $reference)->first();
        
        if (!$transaction) {
            Log::error('Paystack: Transaction not found', [
                'reference' => $reference
            ]);
            
            return redirect('/deposit?msg=Error&details=Transaction not found');
        }
        
        // Check if already processed
        if ($transaction->status === 'success') {
            return redirect('/deposit?msg=Success&details=Payment already processed');
        }
        
        // Process the successful payment
        DB::beginTransaction();
        
        try {
            // Update transaction
            $transaction->status = 'success';
            $transaction->remark = 'Paid via ' . ($result['channel'] ?? 'Paystack') . ' - Verified';
            $transaction->save();
            
            // Credit user wallet
            $wallet = Wallet::where('userid', $transaction->userid)->first();
            
            if ($wallet) {
                $previousBalance = floatval($wallet->amount);
                $newBalance = $previousBalance + $transaction->amount;
                
                $wallet->amount = $newBalance;
                $wallet->save();
                
                Log::info('Paystack deposit successful', [
                    'user_id' => $transaction->userid,
                    'amount' => $transaction->amount,
                    'previous_balance' => $previousBalance,
                    'new_balance' => $newBalance,
                    'reference' => $reference
                ]);
            }
            
            DB::commit();
            
            return redirect('/deposit?msg=Success');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Paystack deposit processing error', [
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);
            
            return redirect('/deposit?msg=Error&details=' . urlencode($e->getMessage()));
        }
    }
    
    /**
     * Handle Paystack webhook (for server-to-server notifications)
     */
    public function handleWebhook(Request $request)
    {
        // Verify webhook signature
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();
        
        $computedSignature = hash_hmac('sha512', $payload, config('paystack.secret_key'));
        
        if ($signature !== $computedSignature) {
            Log::warning('Invalid Paystack webhook signature');
            return response()->json(['message' => 'Invalid signature'], 400);
        }
        
        $event = $request->input('event');
        $data = $request->input('data');
        
        Log::info('Paystack webhook received', [
            'event' => $event,
            'reference' => $data['reference'] ?? 'N/A'
        ]);
        
        // Handle different event types
        switch ($event) {
            case 'charge.success':
                $this->handleSuccessfulCharge($data);
                break;
                
            case 'transfer.success':
                $this->handleSuccessfulTransfer($data);
                break;
                
            case 'transfer.failed':
                $this->handleFailedTransfer($data);
                break;
                
            default:
                Log::info('Unhandled Paystack webhook event', ['event' => $event]);
        }
        
        return response()->json(['message' => 'Webhook received'], 200);
    }
    
    /**
     * Handle successful charge webhook
     */
    protected function handleSuccessfulCharge($data)
    {
        $reference = $data['reference'];
        $transaction = Transaction::where('transactionno', $reference)->first();
        
        if (!$transaction || $transaction->status === 'success') {
            return; // Already processed
        }
        
        DB::beginTransaction();
        
        try {
            $transaction->status = 'success';
            $transaction->remark = 'Webhook: Payment confirmed';
            $transaction->save();
            
            // Credit wallet
            $wallet = Wallet::where('userid', $transaction->userid)->first();
            if ($wallet) {
                $wallet->amount += $transaction->amount;
                $wallet->save();
            }
            
            DB::commit();
            
            Log::info('Paystack webhook: Deposit processed', [
                'reference' => $reference,
                'amount' => $transaction->amount
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Paystack webhook processing error', [
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Handle successful transfer webhook
     */
    protected function handleSuccessfulTransfer($data)
    {
        $reference = $data['reference'];
        $transaction = Transaction::where('transactionno', $reference)->first();
        
        if (!$transaction) {
            return;
        }
        
        $transaction->status = 'success';
        $transaction->remark = 'Webhook: Transfer successful';
        $transaction->save();
        
        Log::info('Paystack webhook: Transfer successful', [
            'reference' => $reference
        ]);
    }
    
    /**
     * Handle failed transfer webhook
     */
    protected function handleFailedTransfer($data)
    {
        $reference = $data['reference'];
        $transaction = Transaction::where('transactionno', $reference)->first();
        
        if (!$transaction) {
            return;
        }
        
        DB::beginTransaction();
        
        try {
            $transaction->status = 'failed';
            $transaction->remark = 'Webhook: Transfer failed - ' . ($data['message'] ?? 'Unknown error');
            $transaction->save();
            
            // Refund user
            $wallet = Wallet::where('userid', $transaction->userid)->first();
            if ($wallet) {
                $wallet->amount += $transaction->amount;
                $wallet->save();
            }
            
            DB::commit();
            
            Log::info('Paystack webhook: Transfer failed, user refunded', [
                'reference' => $reference
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Paystack failed transfer handling error', [
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get Paystack public key for frontend
     */
    public function getPublicKey()
    {
        return response()->json([
            'public_key' => $this->paystack->getPublicKey(),
            'currency' => config('paystack.currency'),
            'currency_symbol' => config('paystack.currency_symbol')
        ]);
    }
    
    /**
     * Check if Paystack is available
     */
    public function checkAvailability()
    {
        return response()->json([
            'available' => $this->paystack->isConfigured(),
            'min_deposit' => config('paystack.min_deposit'),
            'currency' => config('paystack.currency')
        ]);
    }
    
    /**
     * Initialize M-Pesa deposit via Paystack
     */
    public function initializeMpesaDeposit(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|regex:/^254[0-9]{9}$/',
            'amount' => 'required|numeric|min:' . config('paystack.min_deposit', 1),
            'email' => 'required|email'
        ]);
        
        if (!$this->paystack->isConfigured()) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Paystack is not configured. Please contact support.'
            ], 503);
        }
        
        $userId = user('id');
        $phone = $request->phone;
        $amount = floatval($request->amount);
        $email = $request->email;
        
        // Generate unique reference
        $reference = 'MPESA_' . $userId . '_' . time();
        
        // Create pending transaction record
        try {
            $transaction = new Transaction();
            $transaction->userid = $userId;
            $transaction->amount = $amount;
            $transaction->category = 'deposit';
            $transaction->platform = 'Paystack M-Pesa';
            $transaction->transactionno = $reference;
            $transaction->status = 'pending';
            $transaction->remark = 'M-Pesa deposit via Paystack - ' . $phone;
            $transaction->save();
            
            // Initialize Paystack mobile money payment
            $result = $this->paystack->initializeMobileMoney(
                $amount,
                $phone,
                $email,
                $reference
            );
            
            if ($result['success']) {
                Log::info('Paystack M-Pesa deposit initialized', [
                    'user_id' => $userId,
                    'phone' => $phone,
                    'amount' => $amount,
                    'reference' => $reference
                ]);
                
                return response()->json([
                    'isSuccess' => true,
                    'message' => 'Redirecting to M-Pesa payment...',
                    'authorization_url' => $result['authorization_url'],
                    'reference' => $result['reference']
                ]);
            }
            
            // Failed to initialize
            $transaction->status = 'failed';
            $transaction->remark = 'Initialization failed: ' . $result['message'];
            $transaction->save();
            
            return response()->json([
                'isSuccess' => false,
                'message' => $result['message']
            ], 400);
            
        } catch (\Exception $e) {
            Log::error('Paystack M-Pesa deposit initialization error', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to initialize payment: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Initialize M-Pesa withdrawal via Paystack
     */
    public function initializeMpesaWithdrawal(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|regex:/^254[0-9]{9}$/',
            'amount' => 'required|numeric|min:' . config('paystack.min_withdrawal', 1)
        ]);
        
        if (!$this->paystack->isConfigured()) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Paystack is not configured. Please contact support.'
            ], 503);
        }
        
        $userId = user('id');
        $phone = $request->phone;
        $amount = floatval($request->amount);
        $userName = user('name');
        
        // Check wallet balance
        $wallet = Wallet::where('userid', $userId)->first();
        if (!$wallet || $wallet->amount < $amount) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Insufficient balance. Your balance: KSh ' . ($wallet->amount ?? 0)
            ], 400);
        }
        
        DB::beginTransaction();
        
        try {
            // Deduct from wallet
            $wallet->amount -= $amount;
            $wallet->save();
            
            // Create transaction
            $reference = 'WITHDRAW_' . $userId . '_' . time();
            $transaction = new Transaction();
            $transaction->userid = $userId;
            $transaction->amount = $amount;
            $transaction->category = 'withdrawal';
            $transaction->platform = 'Paystack M-Pesa';
            $transaction->transactionno = $reference;
            $transaction->status = 'pending';
            $transaction->remark = 'M-Pesa withdrawal via Paystack - ' . $phone;
            $transaction->save();
            
            // Create M-Pesa recipient
            $recipientResult = $this->paystack->createMpesaRecipient($phone, $userName);
            
            if (!$recipientResult['success']) {
                throw new \Exception($recipientResult['message']);
            }
            
            $recipientCode = $recipientResult['recipient_code'];
            
            // Initiate transfer
            $transferResult = $this->paystack->initiateTransfer(
                $recipientCode,
                $amount,
                'Withdrawal to M-Pesa',
                $reference
            );
            
            if ($transferResult['success']) {
                $transaction->status = 'success';
                $transaction->remark = 'M-Pesa withdrawal successful - ' . $phone;
                $transaction->save();
                
                DB::commit();
                
                Log::info('Paystack M-Pesa withdrawal successful', [
                    'user_id' => $userId,
                    'phone' => $phone,
                    'amount' => $amount,
                    'reference' => $reference
                ]);
                
                return response()->json([
                    'isSuccess' => true,
                    'message' => 'Withdrawal successful! Funds sent to your M-Pesa number: ' . $phone
                ]);
            }
            
            throw new \Exception($transferResult['message'] ?? 'Transfer failed');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Refund wallet
            $wallet->amount += $amount;
            $wallet->save();
            
            if (isset($transaction)) {
                $transaction->status = 'failed';
                $transaction->remark = 'Failed: ' . $e->getMessage();
                $transaction->save();
            }
            
            Log::error('Paystack M-Pesa withdrawal error', [
                'user_id' => $userId,
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'isSuccess' => false,
                'message' => 'Withdrawal failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
