<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MpesaController extends Controller
{
    protected $mpesa;

    public function __construct(MpesaService $mpesa)
    {
        $this->mpesa = $mpesa;
    }

    /**
     * Initiate M-Pesa STK Push for deposit
     */
    public function initiateDeposit(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'amount' => 'required|numeric|min:' . setting('min_recharge', 10),
        ]);

        // Check if M-Pesa is configured
        if (!$this->mpesa->isConfigured()) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'M-Pesa is not properly configured. Please contact support.',
            ], 500);
        }

        $userId = user('id');
        $phone = $request->phone;
        $amount = (int) $request->amount;

        // Generate unique account reference
        $accountRef = 'AV' . $userId . '_' . time();

        // Initiate STK Push
        $result = $this->mpesa->stkPush($phone, $amount, $accountRef);

        if ($result['success']) {
            // Create pending transaction record
            $transaction = new Transaction();
            $transaction->userid = $userId;
            $transaction->platform = 'M-Pesa';
            $transaction->transactionno = $result['checkout_request_id'];
            $transaction->type = 'credit';
            $transaction->amount = $amount;
            $transaction->category = 'recharge';
            $transaction->remark = 'M-Pesa STK Push Pending';
            $transaction->status = '0'; // Pending
            $transaction->save();

            // Store checkout request ID for later verification
            // We use the transaction ID as reference
            session([
                'mpesa_checkout_' . $result['checkout_request_id'] => [
                    'transaction_id' => $transaction->id,
                    'user_id' => $userId,
                    'amount' => $amount,
                    'phone' => $phone,
                    'created_at' => now(),
                ]
            ]);

            return response()->json([
                'isSuccess' => true,
                'message' => $result['customer_message'],
                'checkout_request_id' => $result['checkout_request_id'],
                'merchant_request_id' => $result['merchant_request_id'],
            ]);
        }

        return response()->json([
            'isSuccess' => false,
            'message' => $result['message'],
            'error_code' => $result['error_code'] ?? null,
        ], 400);
    }

    /**
     * Check M-Pesa transaction status
     */
    public function checkStatus(Request $request)
    {
        $request->validate([
            'checkout_request_id' => 'required|string',
        ]);

        $checkoutRequestId = $request->checkout_request_id;

        // Query M-Pesa for transaction status
        $result = $this->mpesa->stkQuery($checkoutRequestId);

        if ($result['success'] && $result['status'] === 'completed') {
            // Transaction successful - update our records
            $this->processSuccessfulPayment($checkoutRequestId);

            return response()->json([
                'isSuccess' => true,
                'status' => 'completed',
                'message' => 'Payment successful! Your account has been credited.',
            ]);
        }

        if (isset($result['status']) && $result['status'] === 'cancelled') {
            // Update transaction as cancelled
            $this->cancelTransaction($checkoutRequestId);

            return response()->json([
                'isSuccess' => false,
                'status' => 'cancelled',
                'message' => 'Transaction was cancelled.',
            ]);
        }

        return response()->json([
            'isSuccess' => false,
            'status' => $result['status'] ?? 'pending',
            'message' => $result['message'] ?? 'Transaction is still processing...',
        ]);
    }

    /**
     * M-Pesa Callback URL - receives payment confirmation from Safaricom
     */
    public function callback(Request $request)
    {
        // Log the raw callback for debugging
        Log::info('M-Pesa Callback Raw', ['data' => $request->all()]);

        $callbackData = $request->all();
        $result = $this->mpesa->processCallback($callbackData);

        if ($result['success']) {
            // Process successful payment
            $this->processSuccessfulPaymentFromCallback($result);
            
            Log::info('M-Pesa Payment Success', [
                'checkout_request_id' => $result['checkout_request_id'],
                'amount' => $result['amount'],
                'receipt' => $result['mpesa_receipt_number'],
            ]);
        } else {
            // Payment failed or was cancelled
            if (isset($result['checkout_request_id'])) {
                $this->cancelTransaction($result['checkout_request_id']);
            }
            
            Log::warning('M-Pesa Payment Failed', [
                'checkout_request_id' => $result['checkout_request_id'] ?? 'unknown',
                'result_code' => $result['result_code'] ?? 'unknown',
                'result_desc' => $result['result_desc'] ?? 'unknown',
            ]);
        }

        // Safaricom expects a response
        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Callback received successfully'
        ]);
    }

    /**
     * Process successful payment from STK Query
     */
    protected function processSuccessfulPayment($checkoutRequestId)
    {
        // Find the pending transaction
        $transaction = Transaction::where('transactionno', $checkoutRequestId)
            ->where('status', '0')
            ->first();

        if (!$transaction) {
            Log::warning('M-Pesa: Transaction not found for checkout', [
                'checkout_request_id' => $checkoutRequestId
            ]);
            return false;
        }

        // Prevent double processing
        if ($transaction->status === '1') {
            return true;
        }

        DB::beginTransaction();
        try {
            // Update transaction status
            $transaction->status = '1';
            $transaction->remark = 'Success via M-Pesa';
            $transaction->save();

            // Credit user wallet
            addwallet($transaction->userid, $transaction->amount);

            // Sync wallet to socket server for instant balance update
            syncWalletToSocket($transaction->userid);

            // Process referral commissions for first recharge
            $this->processReferralCommissions($transaction);

            DB::commit();

            Log::info('M-Pesa: Payment processed successfully', [
                'transaction_id' => $transaction->id,
                'user_id' => $transaction->userid,
                'amount' => $transaction->amount,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('M-Pesa: Failed to process payment', [
                'error' => $e->getMessage(),
                'checkout_request_id' => $checkoutRequestId,
            ]);
            return false;
        }
    }

    /**
     * Process successful payment from M-Pesa callback
     */
    protected function processSuccessfulPaymentFromCallback($callbackResult)
    {
        $checkoutRequestId = $callbackResult['checkout_request_id'];
        $mpesaReceipt = $callbackResult['mpesa_receipt_number'];

        // Find the pending transaction
        $transaction = Transaction::where('transactionno', $checkoutRequestId)
            ->where('status', '0')
            ->first();

        if (!$transaction) {
            Log::warning('M-Pesa Callback: Transaction not found', [
                'checkout_request_id' => $checkoutRequestId
            ]);
            return false;
        }

        DB::beginTransaction();
        try {
            // Update transaction with M-Pesa receipt
            $transaction->status = '1';
            $transaction->remark = 'Success - Receipt: ' . $mpesaReceipt;
            $transaction->save();

            // Credit user wallet
            addwallet($transaction->userid, $transaction->amount);

            // Sync wallet to socket server
            syncWalletToSocket($transaction->userid);

            // Process referral commissions
            $this->processReferralCommissions($transaction);

            DB::commit();

            Log::info('M-Pesa Callback: Payment processed', [
                'transaction_id' => $transaction->id,
                'receipt' => $mpesaReceipt,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('M-Pesa Callback: Processing failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Cancel a pending transaction
     */
    protected function cancelTransaction($checkoutRequestId)
    {
        Transaction::where('transactionno', $checkoutRequestId)
            ->where('status', '0')
            ->update([
                'status' => '2',
                'remark' => 'Cancelled by user or failed'
            ]);
    }

    /**
     * Process referral commissions for first recharge
     */
    protected function processReferralCommissions($transaction)
    {
        // Check if this is user's first successful recharge
        $previousRecharges = Transaction::where('userid', $transaction->userid)
            ->where('category', 'recharge')
            ->where('status', '1')
            ->where('id', '!=', $transaction->id)
            ->count();

        if ($previousRecharges > 0) {
            return; // Not first recharge
        }

        $userId = $transaction->userid;
        $amount = $transaction->amount;

        // Get user's referrer (level 1)
        $user = \App\Models\User::find($userId);
        if (!$user || !$user->promocode) {
            return;
        }

        $level1 = \App\Models\User::where('id', $user->promocode)->first();
        if ($level1) {
            $level1amount = ($amount / 100) * setting('level1commission', 5);
            addwallet($level1->id, $level1amount);
            addtransaction($level1->id, 'Level', date("ydmhsi"), 'credit', $level1amount, 'Level_bonus', 'Success', '1');

            // Level 2
            $level2 = \App\Models\User::where('id', $level1->promocode)->first();
            if ($level2) {
                $level2amount = ($amount / 100) * setting('level2commission', 2);
                addwallet($level2->id, $level2amount);
                addtransaction($level2->id, 'Level', date("ydmhsi"), 'credit', $level2amount, 'Level_bonus', 'Success', '1');

                // Level 3
                $level3 = \App\Models\User::where('id', $level2->promocode)->first();
                if ($level3) {
                    $level3amount = ($amount / 100) * setting('level3commission', 1);
                    addwallet($level3->id, $level3amount);
                    addtransaction($level3->id, 'Level', date("ydmhsi"), 'credit', $level3amount, 'Level_bonus', 'Success', '1');
                }
            }
        }
    }

    // ==========================================
    // M-PESA B2C WITHDRAWAL METHODS
    // ==========================================

    /**
     * Initiate M-Pesa B2C withdrawal
     */
    public function initiateWithdrawal(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'amount' => 'required|numeric|min:' . setting('min_withdraw', 100),
        ]);

        // Check if M-Pesa B2C is configured
        if (!$this->mpesa->isB2CConfigured()) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'M-Pesa withdrawals are not configured. Please contact support.',
            ], 500);
        }

        $userId = user('id');
        $phone = $request->phone;
        $amount = (int) $request->amount;

        // Check wallet balance
        $walletBalance = wallet($userId, 'num');
        if ($walletBalance < $amount) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Insufficient wallet balance. Available: KES ' . number_format($walletBalance, 2),
            ], 400);
        }

        // Check minimum withdrawal
        $minWithdraw = setting('min_withdraw', 100);
        if ($amount < $minWithdraw) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Minimum withdrawal amount is KES ' . $minWithdraw,
            ], 400);
        }

        // Generate unique transaction reference
        $transactionRef = 'WD' . $userId . '_' . time();

        DB::beginTransaction();
        try {
            // Deduct from wallet first (will be refunded if B2C fails)
            addwallet($userId, $amount, '-', false); // Don't sync to socket yet

            // Create pending withdrawal transaction
            $transaction = new Transaction();
            $transaction->userid = $userId;
            $transaction->platform = 'M-Pesa B2C';
            $transaction->transactionno = $transactionRef;
            $transaction->type = 'debit';
            $transaction->amount = $amount;
            $transaction->category = 'withdraw';
            $transaction->remark = 'M-Pesa withdrawal pending - Phone: ' . $phone;
            $transaction->status = '0'; // Pending
            $transaction->save();

            // Initiate B2C payment
            $result = $this->mpesa->b2cPayment(
                $phone,
                $amount,
                $transactionRef,
                'Aviator Withdrawal',
                'WD-' . $transaction->id
            );

            if ($result['success']) {
                // Update transaction with conversation ID
                $transaction->transactionno = $result['conversation_id'] ?? $transactionRef;
                $transaction->remark = 'M-Pesa B2C initiated - ConvID: ' . ($result['conversation_id'] ?? 'N/A');
                $transaction->save();

                DB::commit();

                // Sync wallet to socket
                syncWalletToSocket($userId);

                return response()->json([
                    'isSuccess' => true,
                    'message' => 'Withdrawal initiated! You will receive KES ' . number_format($amount) . ' on your M-Pesa shortly.',
                    'conversation_id' => $result['conversation_id'],
                    'transaction_id' => $transaction->id,
                ]);
            } else {
                // B2C failed - rollback
                DB::rollBack();
                
                // Refund the amount
                addwallet($userId, $amount, '+', true);

                return response()->json([
                    'isSuccess' => false,
                    'message' => $result['message'] ?? 'Failed to initiate M-Pesa withdrawal',
                    'error_code' => $result['error_code'] ?? null,
                ], 400);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('M-Pesa B2C Error', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'amount' => $amount,
            ]);

            return response()->json([
                'isSuccess' => false,
                'message' => 'An error occurred. Please try again.',
            ], 500);
        }
    }

    /**
     * M-Pesa B2C Result Callback - receives withdrawal results from Safaricom
     */
    public function b2cResult(Request $request)
    {
        Log::info('M-Pesa B2C Result Callback', ['data' => $request->all()]);

        $callbackData = $request->all();
        $result = $this->mpesa->processB2CCallback($callbackData);

        if ($result['success']) {
            // Withdrawal successful
            $this->processSuccessfulWithdrawal($result);
            
            Log::info('M-Pesa B2C Withdrawal Success', [
                'conversation_id' => $result['conversation_id'],
                'amount' => $result['amount'],
                'receipt' => $result['transaction_receipt'],
            ]);
        } else {
            // Withdrawal failed - refund user
            $this->handleFailedWithdrawal($result);
            
            Log::warning('M-Pesa B2C Withdrawal Failed', [
                'conversation_id' => $result['conversation_id'] ?? 'unknown',
                'result_code' => $result['result_code'] ?? 'unknown',
                'result_desc' => $result['result_desc'] ?? 'unknown',
            ]);
        }

        // Safaricom expects a response
        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Callback received successfully'
        ]);
    }

    /**
     * M-Pesa B2C Timeout Callback
     */
    public function b2cTimeout(Request $request)
    {
        Log::warning('M-Pesa B2C Timeout Callback', ['data' => $request->all()]);

        $callbackData = $request->all();
        $result = $this->mpesa->processB2CTimeout($callbackData);

        // Handle timeout - refund user
        $this->handleFailedWithdrawal($result);

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Timeout callback received'
        ]);
    }

    /**
     * Process successful B2C withdrawal
     */
    protected function processSuccessfulWithdrawal($result)
    {
        $conversationId = $result['conversation_id'];

        // Find transaction by conversation ID
        $transaction = Transaction::where('transactionno', $conversationId)
            ->where('category', 'withdraw')
            ->where('status', '0')
            ->first();

        if (!$transaction) {
            // Try to find by partial match in remark
            $transaction = Transaction::where('remark', 'like', '%' . $conversationId . '%')
                ->where('category', 'withdraw')
                ->where('status', '0')
                ->first();
        }

        if (!$transaction) {
            Log::warning('M-Pesa B2C: Transaction not found for withdrawal', [
                'conversation_id' => $conversationId
            ]);
            return false;
        }

        // Update transaction as successful
        $transaction->status = '1';
        $transaction->remark = 'Success - Receipt: ' . ($result['transaction_receipt'] ?? 'N/A') . 
                              ' | Phone: ' . ($result['receiver_phone'] ?? 'N/A');
        $transaction->save();

        // Sync wallet to socket
        syncWalletToSocket($transaction->userid);

        Log::info('M-Pesa B2C: Withdrawal completed', [
            'transaction_id' => $transaction->id,
            'user_id' => $transaction->userid,
            'amount' => $transaction->amount,
        ]);

        return true;
    }

    /**
     * Handle failed B2C withdrawal - refund user
     */
    protected function handleFailedWithdrawal($result)
    {
        $conversationId = $result['conversation_id'] ?? null;
        
        if (!$conversationId) {
            return false;
        }

        // Find transaction
        $transaction = Transaction::where('transactionno', $conversationId)
            ->where('category', 'withdraw')
            ->where('status', '0')
            ->first();

        if (!$transaction) {
            $transaction = Transaction::where('remark', 'like', '%' . $conversationId . '%')
                ->where('category', 'withdraw')
                ->where('status', '0')
                ->first();
        }

        if (!$transaction) {
            Log::warning('M-Pesa B2C: Failed transaction not found for refund', [
                'conversation_id' => $conversationId
            ]);
            return false;
        }

        DB::beginTransaction();
        try {
            // Mark transaction as failed
            $transaction->status = '2';
            $transaction->remark = 'Failed - ' . ($result['result_desc'] ?? 'Unknown error') . 
                                  (isset($result['is_timeout']) ? ' (Timeout)' : '');
            $transaction->save();

            // Refund the user
            addwallet($transaction->userid, $transaction->amount, '+');

            // Sync wallet to socket
            syncWalletToSocket($transaction->userid);

            DB::commit();

            Log::info('M-Pesa B2C: Withdrawal refunded', [
                'transaction_id' => $transaction->id,
                'user_id' => $transaction->userid,
                'amount' => $transaction->amount,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('M-Pesa B2C: Failed to process refund', [
                'error' => $e->getMessage(),
                'conversation_id' => $conversationId,
            ]);
            return false;
        }
    }

    /**
     * Check if M-Pesa B2C withdrawals are available
     */
    public function checkB2CAvailability()
    {
        return response()->json([
            'isSuccess' => true,
            'b2c_available' => $this->mpesa->isB2CConfigured(),
            'min_withdraw' => setting('min_withdraw', 100),
        ]);
    }
}
