<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PaystackService;
use App\Support\ViserMartPaymentService;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaystackController extends Controller
{
    protected $paystack;
    protected $viserMart;

    public function __construct(PaystackService $paystack, ViserMartPaymentService $viserMart)
    {
        $this->paystack = $paystack;
        $this->viserMart = $viserMart;
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

        $useProxy = !empty(env('VISERMART_BASE_URL'));

        if (!$useProxy && !$this->paystack->isConfigured()) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Payment gateway is not available. Please contact support.'
            ], 503);
        }

        $userId = user('id');
        $amount = floatval($request->amount);
        $email = $request->email;
        $reference = 'DEP_' . $userId . '_' . time();

        try {
            $transaction = new Transaction();
            $transaction->userid = $userId;
            $transaction->amount = $amount;
            $transaction->category = 'recharge'; // Using 'recharge' to match Adminapi
            $transaction->platform = $useProxy ? 'Paystack Card' : 'Paystack';
            $transaction->transactionno = $reference;
            $transaction->status = 'pending';
            $transaction->remark = ($useProxy ? 'Paystack' : 'Paystack') . ' card deposit initiated';
            $transaction->save();

            if ($useProxy) {
                $result = $this->viserMart->initiatePayment([
                    'amount' => $amount,
                    'reference' => $reference,
                    'email' => $email,
                    'currency' => config('paystack.currency', 'KES'),
                    'return_url' => route('paystack.callback') . '?reference=' . $reference,
                ]);

                if (isset($result['status']) && $result['status'] === 'success') {
                    return response()->json([
                        'isSuccess' => true,
                        'message' => 'Redirecting to payment page...',
                        'authorization_url' => $result['checkout_url'],
                        'reference' => $reference
                    ]);
                }
                $errorMessage = $result['error'] ?? 'ViserMart initiation failed';
            } else {
                $result = $this->paystack->initializeTransaction($amount, $email, $reference, [
                    'user_id' => $userId,
                    'transaction_id' => $transaction->id,
                ]);

                if ($result['success']) {
                    return response()->json([
                        'isSuccess' => true,
                        'message' => 'Redirecting to payment page...',
                        'authorization_url' => $result['authorization_url'],
                        'reference' => $result['reference']
                    ]);
                }
                $errorMessage = $result['message'];
            }

            $transaction->status = 'failed'; // 2 = Failed
            $transaction->remark = 'Initialization failed: ' . $errorMessage;
            $transaction->save();

            return response()->json(['isSuccess' => false, 'message' => $errorMessage], 400);

        } catch (\Exception $e) {
            Log::error('Deposit initialization error: ' . $e->getMessage());
            return response()->json(['isSuccess' => false, 'message' => 'Internal Error'], 500);
        }
    }
    
    public function handleCallback(Request $request)
    {
        $reference = $request->query('reference');
        if (!$reference) return redirect('/deposit?msg=Error&details=No reference');

        $useProxy = !empty(env('VISERMART_BASE_URL'));
        $isPaid = false;
        $channel = 'Unknown';

        if ($useProxy) {
            $result = $this->viserMart->verifyPayment($reference);
            Log::info('ViserMart Callback Verify:', ['res' => $result]);
            
            $isPaid = isset($result['status']) && 
                      $result['status'] === 'success' && 
                      isset($result['transaction']['status']) && 
                      $result['transaction']['status'] === 'paid';
            $channel = 'Paystack';
        } else {
            $result = $this->paystack->verifyTransaction($reference);
            Log::info('Paystack Callback Verify:', ['res' => $result]);
            
            $isPaid = $result['success'] && isset($result['status']) && $result['status'] === 'success';
            $channel = $result['channel'] ?? 'Paystack';
        }

        if (!$isPaid) {
            Log::warning('Payment verification failed or cancelled', ['ref' => $reference]);
            // Update transaction to failed if it's still pending
            Transaction::where('transactionno', $reference)->where('status', '0')->update([
                'status' => '2',
                'remark' => 'Verification failed or cancelled'
            ]);
            return redirect('/deposit?msg=Failed&details=Payment was not successful');
        }

        $transaction = Transaction::where('transactionno', $reference)->first();
        if (!$transaction) return redirect('/deposit?msg=Error&details=Not found');
        
        // Check for success
        if ($transaction->status === '1' || $transaction->status === 'success') {
            return redirect('/deposit?msg=Success&details=Already processed');
        }

        DB::beginTransaction();
        try {
            $transaction->status = 'success';
            $transaction->remark = 'Paid via ' . $channel . ' - Verified';
            $transaction->save();
            
            addwallet($transaction->userid, $transaction->amount, "+");
            
            DB::commit();
            Log::info('Deposit confirmed:', ['ref' => $reference, 'user' => $transaction->userid]);
            return redirect('/deposit?msg=Success');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Deposit processing error: ' . $e->getMessage());
            return redirect('/deposit?msg=Error&details=' . urlencode($e->getMessage()));
        }
    }
    
    public function handleWebhook(Request $request)
    {
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();
        $secret = config('paystack.secret_key');
        
        if (!$secret || $signature !== hash_hmac('sha512', $payload, $secret)) {
            return response()->json(['message' => 'Invalid signature'], 400);
        }
        
        $event = $request->input('event');
        $data = $request->input('data');
        
        if ($event === 'charge.success' && isset($data['status']) && $data['status'] === 'success') {
            $this->handleSuccessfulCharge($data);
        }
        
        return response()->json(['message' => 'Webhook received'], 200);
    }
    
    protected function handleSuccessfulCharge($data)
    {
        $reference = $data['reference'];
        $transaction = Transaction::where('transactionno', $reference)->first();
        
        if (!$transaction || $transaction->status === '1' || $transaction->status === 'success') return;
        
        DB::beginTransaction();
        try {
            $transaction->status = 'success';
            $transaction->remark = 'Webhook: Payment confirmed';
            $transaction->save();
            addwallet($transaction->userid, $transaction->amount, "+");
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Webhook processing error: ' . $e->getMessage());
        }
    }
    
    public function getPublicKey()
    {
        return response()->json([
            'public_key' => $this->paystack->getPublicKey(),
            'currency' => config('paystack.currency'),
            'currency_symbol' => config('paystack.currency_symbol')
        ]);
    }
    
    public function checkAvailability()
    {
        return response()->json([
            'available' => $this->paystack->isConfigured(),
            'min_deposit' => config('paystack.min_deposit'),
            'currency' => config('paystack.currency')
        ]);
    }
    
    public function initializeMpesaDeposit(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'amount' => 'required|numeric|min:' . config('paystack.min_deposit', 1),
            'email' => 'required|email'
        ]);
        
        $useProxy = !empty(env('VISERMART_BASE_URL'));
        if (!$useProxy && !$this->paystack->isConfigured()) {
            return response()->json(['isSuccess' => false, 'message' => 'Paystack not configured'], 503);
        }
        
        $userId = user('id');
        $phone = $request->phone;
        $amount = floatval($request->amount);
        $email = $request->email;
        $reference = 'MPESA_' . $userId . '_' . time();
        
        try {
            $transaction = new Transaction();
            $transaction->userid = $userId;
            $transaction->amount = $amount;
            $transaction->category = 'recharge';
            $transaction->platform = $useProxy ? 'Paystack M-Pesa' : 'Paystack M-Pesa';
            $transaction->transactionno = $reference;
            $transaction->status = 'pending';
            $transaction->remark = 'M-Pesa deposit via ' . ($useProxy ? 'Proxy' : 'Paystack');
            $transaction->save();
            
            if ($useProxy) {
                $result = $this->viserMart->initiatePayment([
                    'amount' => $amount,
                    'reference' => $reference,
                    'email' => $email,
                    'currency' => 'KES',
                    'channels' => ['mobile_money'],
                    'return_url' => route('paystack.callback') . '?reference=' . $reference,
                    'metadata' => ['phone_number' => $phone, 'payment_method' => 'mpesa']
                ]);

                if (isset($result['status']) && $result['status'] === 'success') {
                    return response()->json([
                        'isSuccess' => true,
                        'message' => 'Redirecting...',
                        'authorization_url' => $result['checkout_url'],
                        'reference' => $reference
                    ]);
                }
                $errorMessage = $result['error'] ?? 'ViserMart failed';
            } else {
                $result = $this->paystack->initializeMobileMoney($amount, $phone, $email, $reference);
                if ($result['success']) {
                    return response()->json([
                        'isSuccess' => true,
                        'message' => 'Redirecting...',
                        'authorization_url' => $result['authorization_url'],
                        'reference' => $result['reference']
                    ]);
                }
                $errorMessage = $result['message'];
            }
            
            $transaction->status = 'failed';
            $transaction->remark = 'Failed: ' . $errorMessage;
            $transaction->save();
            return response()->json(['isSuccess' => false, 'message' => $errorMessage], 400);
            
        } catch (\Exception $e) {
            Log::error('M-Pesa deposit initialization error', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['isSuccess' => false, 'message' => 'Internal Error: ' . $e->getMessage()], 500);
        }
    }
    
    public function initializeMpesaWithdrawal(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|regex:/^254[0-9]{9}$/',
            'amount' => 'required|numeric|min:' . config('paystack.min_withdrawal', 1)
        ]);
        
        $useProxy = !empty(env('VISERMART_BASE_URL'));
        if (!$useProxy && !$this->paystack->isConfigured()) {
            return response()->json(['isSuccess' => false, 'message' => 'Not configured'], 503);
        }
        
        $userId = user('id');
        $phone = $request->phone;
        $amount = floatval($request->amount);
        $userName = user('name');
        
        $wallet = Wallet::where('userid', $userId)->first();
        if (!$wallet || $wallet->amount < $amount) {
            return response()->json(['isSuccess' => false, 'message' => 'Insufficient balance'], 400);
        }
        
        DB::beginTransaction();
        try {
            if (addwallet($userId, $amount, "-") === false) throw new \Exception('Insufficient balance');
            
            $reference = 'WITHDRAW_' . $userId . '_' . time();
            $transaction = new Transaction();
            $transaction->userid = $userId;
            $transaction->amount = $amount;
            $transaction->category = 'withdraw';
            $transaction->platform = $useProxy ? 'Paystack M-Pesa' : 'Paystack M-Pesa';
            $transaction->transactionno = $reference;
            $transaction->status = 'pending';
            $transaction->remark = 'M-Pesa withdrawal initiated';
            $transaction->save();
            
            if ($useProxy) {
                $result = $this->viserMart->initiateTransfer([
                    'amount' => $amount, 'reference' => $reference, 'phone' => $phone, 'name' => $userName, 'currency' => 'KES', 'email' => user('email')
                ]);

                if (isset($result['status']) && $result['status'] === 'success') {
                    $transaction->status = 'success';
                    $transaction->save();
                    DB::commit();
                    return response()->json(['isSuccess' => true, 'message' => 'Withdrawal initiated!']);
                }
                throw new \Exception($result['error'] ?? 'Transfer failed');
            } else {
                $recipientResult = $this->paystack->createMpesaRecipient($phone, $userName);
                if (!$recipientResult['success']) throw new \Exception($recipientResult['message']);
                
                $transferResult = $this->paystack->initiateTransfer($recipientResult['recipient_code'], $amount, 'Withdrawal', $reference);
                if ($transferResult['success']) {
                    $transaction->status = 'success';
                    $transaction->save();
                    DB::commit();
                    return response()->json(['isSuccess' => true, 'message' => 'Withdrawal successful!']);
                }
                throw new \Exception($transferResult['message'] ?? 'Transfer failed');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            addwallet($userId, $amount, "+");
            if (isset($transaction)) {
                $transaction->status = 'failed';
                $transaction->remark = 'Failed: ' . $e->getMessage();
                $transaction->save();
            }
            return response()->json(['isSuccess' => false, 'message' => 'Withdrawal failed: ' . $e->getMessage()], 500);
        }
    }
}
