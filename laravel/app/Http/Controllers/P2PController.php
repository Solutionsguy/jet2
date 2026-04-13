<?php

namespace App\Http\Controllers;

use App\Models\P2PPeer;
use App\Models\P2PWithdrawal;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class P2PController extends Controller
{
    /**
     * Start the P2P search process
     */
    public function startSearch(Request $request)
    {
        try {
            $minWithdraw = setting('min_withdraw');
            // If setting is null or empty, use a safe default like 100
            $minWithdrawValue = ($minWithdraw !== null && $minWithdraw !== '') ? $minWithdraw : 100;

            $request->validate([
                'amount' => 'required|numeric|min:' . $minWithdrawValue
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Validation failed: ' . implode(', ', \Illuminate\Support\Arr::flatten($e->errors()))
            ], 422);
        } catch (\Exception $e) {
            Log::error('P2P Validation Error: ' . $e->getMessage());
            return response()->json([
                'isSuccess' => false,
                'message' => 'Configuration error. Please contact admin.'
            ], 500);
        }

        $userId = user('id');
        $amount = floatval($request->amount);

        // Check wallet balance
        $wallet = Wallet::where('userid', $userId)->first();
        if (!$wallet || $wallet->amount < $amount) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Insufficient balance.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            Log::info("P2P Search started for user $userId, amount $amount");

            // Deduct from wallet using atomic helper
            if (addwallet($userId, $amount, "-") === false) {
                throw new \Exception('Insufficient balance');
            }

            // Create reference
            $reference = 'P2P_' . $userId . '_' . time();

            // Create main transaction record
            $transaction = new Transaction();
            $transaction->userid = $userId;
            $transaction->amount = $amount;
            $transaction->type = 'debit';
            $transaction->category = 'withdrawal';
            $transaction->platform = 'P2P Withdrawal';
            $transaction->transactionno = $reference;
            $transaction->status = '0'; // Pending
            $transaction->remark = 'P2P Search started';
            $transaction->save();

            // Create P2P specific record
            $p2p = P2PWithdrawal::create([
                'user_id' => $userId,
                'amount' => $amount,
                'reference' => $reference,
                'status' => 'searching'
            ]);

            DB::commit();
            Log::info("P2P Transaction committed: $reference");

            return response()->json([
                'isSuccess' => true,
                'reference' => $reference,
                'message' => 'Search started...'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('P2P Search Error: ' . $e->getMessage());
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to initiate search.'
            ], 500);
        }
    }

    /**
     * Poll status and attempt matching
     */
    public function getMatchStatus($reference)
    {
        $p2p = P2PWithdrawal::where('reference', $reference)->first();

        if (!$p2p) {
            return response()->json(['error' => 'Not found'], 404);
        }

        // If still searching, try to match
        if ($p2p->status === 'searching') {
            // Randomly succeed matching after some time or immediately for testing
            // Picking an online peer within limits
            $peer = P2PPeer::where('status', 1)
                ->where('min_limit', '<=', $p2p->amount)
                ->where('max_limit', '>=', $p2p->amount)
                ->inRandomOrder()
                ->first();

            if ($peer) {
                $p2p->update([
                    'peer_id' => $peer->id,
                    'status' => 'matched',
                    'matched_at' => now()
                ]);
                
                // Update transaction remark
                Transaction::where('transactionno', $reference)->update([
                    'remark' => 'Matched with Peer: ' . $peer->name
                ]);
            }
        }

        return response()->json([
            'status' => $p2p->status,
            'peer' => $p2p->peer ? [
                'name' => $p2p->peer->name,
                'phone' => $p2p->peer->phone,
                'success_rate' => $p2p->peer->success_rate,
                'avg_time' => $p2p->peer->avg_time,
            ] : null
        ]);
    }

    /**
     * Cancel search and refund wallet
     */
    public function cancelSearch($reference)
    {
        $p2p = P2PWithdrawal::where('reference', $reference)
            ->whereIn('status', ['searching', 'matched'])
            ->first();

        if (!$p2p) {
            return response()->json(['error' => 'Cannot cancel this transaction'], 400);
        }

        DB::beginTransaction();
        try {
            Log::info("P2P Search cancelled for ref: $reference. Refunding $p2p->amount to user $p2p->user_id");
            
            $p2p->update(['status' => 'cancelled']);
            
            // Refund wallet using atomic helper
            addwallet($p2p->user_id, $p2p->amount, "+");

            // Update transaction
            Transaction::where('transactionno', $reference)->update([
                'status' => '2', // Failed/Cancelled
                'remark' => 'P2P Cancelled by user'
            ]);

            DB::commit();
            return response()->json(['isSuccess' => true, 'message' => 'Search cancelled and wallet refunded.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Cancellation failed'], 500);
        }
    }
}
