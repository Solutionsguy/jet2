<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\ViserMartPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ViserMartWebhookController extends Controller
{
    protected $service;

    public function __construct(ViserMartPaymentService $service)
    {
        $this->service = $service;
    }

    /**
     * Handle ViserMart Webhook
     */
    public function handle(Request $request)
    {
        $signature = $request->header('X-Aetheric-Signature');
        $payload = $request->all();
        $rawPayload = $request->getContent();

        if (!$signature || !$this->service->verifySignatureRaw($rawPayload, $signature)) {
            Log::warning('Unauthorized ViserMart Webhook attempt', ['sig' => $signature]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $reference = $payload['external_reference'];
        $status = $payload['status'];

        if ($status !== 'paid') {
            return response()->json(['status' => 'ignored']);
        }

        // Find the transaction in Jet2Qwerty
        $transaction = Transaction::where('transactionno', $reference)->first();

        if (!$transaction) {
            Log::error('Transaction not found for ViserMart webhook', ['ref' => $reference]);
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        if ($transaction->status === 'success') { 
            return response()->json(['status' => 'already_processed']);
        }

        DB::beginTransaction();
        try {
            Log::info('Processing ViserMart payment confirmation', ['ref' => $reference]);
            
            // Update transaction
            $transaction->status = 'success';
            $transaction->remark = 'Paid via ViserMart Proxy - Confirmed';
            $transaction->save();

            // Credit user wallet
            $wallet = Wallet::where('userid', $transaction->userid)->first();
            if ($wallet) {
                $previousBalance = floatval($wallet->amount);
                $newBalance = $previousBalance + floatval($transaction->amount);
                $wallet->amount = $newBalance;
                $wallet->save();

                Log::info('ViserMart Proxy deposit successful', [
                    'user_id' => $transaction->userid,
                    'amount' => $transaction->amount,
                    'new_balance' => $newBalance,
                    'reference' => $reference
                ]);
            }

            DB::commit();
            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing ViserMart payment', [
                'ref' => $reference,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Internal processing error'], 500);
        }
    }
}