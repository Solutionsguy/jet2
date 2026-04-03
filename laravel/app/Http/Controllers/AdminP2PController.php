<?php

namespace App\Http\Controllers;

use App\Models\P2PPeer;
use App\Models\P2PWithdrawal;
use Illuminate\Http\Request;

class AdminP2PController extends Controller
{
    /**
     * Display a listing of peers.
     */
    public function index()
    {
        $peers = P2PPeer::orderBy('id', 'desc')->get();
        return view('admin.p2p.peers', compact('peers'));
    }

    /**
     * Store a newly created peer in storage.
     */
    public function storePeer(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'min_limit' => 'required|numeric|min:0',
            'max_limit' => 'required|numeric|gte:min_limit',
        ]);

        P2PPeer::create($request->all());

        return redirect()->back()->with('success', 'Peer added successfully.');
    }

    /**
     * Update the specified peer in storage.
     */
    public function updatePeer(Request $request, $id)
    {
        $peer = P2PPeer::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'min_limit' => 'required|numeric|min:0',
            'max_limit' => 'required|numeric|gte:min_limit',
        ]);

        $peer->update($request->all());

        return redirect()->back()->with('success', 'Peer updated successfully.');
    }

    /**
     * Remove the specified peer from storage.
     */
    public function deletePeer($id)
    {
        $peer = P2PPeer::findOrFail($id);
        $peer->delete();

        return redirect()->back()->with('success', 'Peer deleted successfully.');
    }

    /**
     * Toggle peer status.
     */
    public function toggleStatus($id)
    {
        $peer = P2PPeer::findOrFail($id);
        $peer->status = !$peer->status;
        $peer->save();

        return redirect()->back()->with('success', 'Status updated.');
    }

    /**
     * Display P2P withdrawal history.
     */
    public function withdrawalHistory()
    {
        $history = P2PWithdrawal::with(['user', 'peer'])->orderBy('id', 'desc')->get();
        return view('admin.p2p.withdrawals', compact('history'));
    }

    /**
     * Approve P2P withdrawal.
     */
    public function approveWithdrawal($id)
    {
        $p2p = P2PWithdrawal::findOrFail($id);
        
        if ($p2p->status !== 'matched' && $p2p->status !== 'searching') {
            return redirect()->back()->with('error', 'Only matched or searching withdrawals can be approved.');
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $p2p->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);

            // Update the main transaction record
            \App\Models\Transaction::where('transactionno', $p2p->reference)->update([
                'status' => '1', // Success
                'remark' => 'P2P Withdrawal Approved by Admin'
            ]);

            \Illuminate\Support\Facades\DB::commit();
            return redirect()->back()->with('success', 'Withdrawal marked as completed.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->back()->with('error', 'Failed to approve: ' . $e->getMessage());
        }
    }

    /**
     * Reject P2P withdrawal.
     */
    public function rejectWithdrawal($id)
    {
        $p2p = P2PWithdrawal::findOrFail($id);

        if ($p2p->status !== 'matched' && $p2p->status !== 'searching') {
            return redirect()->back()->with('error', 'Only matched or searching withdrawals can be rejected.');
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $p2p->update(['status' => 'failed']);

            // Refund user wallet
            $wallet = \App\Models\Wallet::where('userid', $p2p->user_id)->first();
            if ($wallet) {
                $wallet->amount += $p2p->amount;
                $wallet->save();
            }

            // Update main transaction
            \App\Models\Transaction::where('transactionno', $p2p->reference)->update([
                'status' => '2', // Cancel/Failed
                'remark' => 'P2P Withdrawal Rejected by Admin - Wallet Refunded'
            ]);

            \Illuminate\Support\Facades\DB::commit();
            return redirect()->back()->with('success', 'Withdrawal rejected and funds refunded to user.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->back()->with('error', 'Failed to reject: ' . $e->getMessage());
        }
    }
}
