<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class AdminWalletController extends Controller
{
    /**
     * Display freebet management page
     */
    public function freebetIndex()
    {
        // Get total freebet in circulation
        $totalFreebet = Wallet::sum('freebet_amount');
        
        // Get users with freebet
        $usersWithFreebet = Wallet::where('freebet_amount', '>', 0)->count();
        
        // Get today's freebet additions
        $todayAdded = DB::table('freebet_transactions')
            ->whereDate('created_at', today())
            ->where('type', 'added')
            ->sum('amount');
        
        $todayAdded = $todayAdded ? $todayAdded : 0;
        
        // Get recent freebet transactions
        $recentTransactions = DB::table('freebet_transactions')
            ->join('users', 'freebet_transactions.user_id', '=', 'users.id')
            ->select('freebet_transactions.*', 'users.name')
            ->orderBy('freebet_transactions.created_at', 'desc')
            ->limit(20)
            ->get();
        
        return view('admin.freebet-management', compact(
            'totalFreebet',
            'usersWithFreebet',
            'todayAdded',
            'recentTransactions'
        ));
    }
    
    /**
     * Add freebet to user
     */
    public function addFreebet(Request $request)
    {
        $request->validate([
            'user_identifier' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'reason' => 'nullable|string|max:500'
        ]);
        
        $amount = floatval($request->amount);
        $reason = $request->reason ? $request->reason : 'Admin bonus';
        $adminId = user('id');
        
        // Find user by ID or name
        $user = User::where('id', $request->user_identifier)
            ->orWhere('name', $request->user_identifier)
            ->first();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
        
        DB::beginTransaction();
        
        try {
            // Get user's wallet
            $wallet = Wallet::where('userid', $user->id)->first();
            
            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'User wallet not found'
                ], 404);
            }
            
            // Add freebet
            $currentFreebet = floatval($wallet->freebet_amount);
            $newFreebet = $currentFreebet + $amount;
            
            $wallet->update(['freebet_amount' => $newFreebet]);
            
            // Record transaction
            DB::table('freebet_transactions')->insert([
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => 'added',
                'reason' => $reason,
                'admin_id' => $adminId,
                'previous_balance' => $currentFreebet,
                'new_balance' => $newFreebet,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            DB::commit();
            
            \Log::info("Admin {$adminId} added KSh {$amount} freebet to user {$user->id}. Reason: {$reason}");
            
            return response()->json([
                'success' => true,
                'message' => "Successfully added KSh {$amount} freebet to " . $user->name,
                'data' => [
                    'user_id' => $user->id,
                    'username' => $user->name,
                    'previous_balance' => $currentFreebet,
                    'new_balance' => $newFreebet,
                    'amount_added' => $amount
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Freebet addition failed: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to add freebet: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Remove freebet from user
     */
    public function removeFreebet(Request $request)
    {
        $request->validate([
            'user_identifier' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'reason' => 'nullable|string|max:500'
        ]);
        
        $amount = floatval($request->amount);
        $reason = $request->reason ? $request->reason : 'Admin adjustment';
        $adminId = user('id');
        
        // Find user
        $user = User::where('id', $request->user_identifier)
            ->orWhere('name', $request->user_identifier)
            ->first();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
        
        DB::beginTransaction();
        
        try {
            $wallet = Wallet::where('userid', $user->id)->first();
            
            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'User wallet not found'
                ], 404);
            }
            
            $currentFreebet = floatval($wallet->freebet_amount);
            
            if ($currentFreebet < $amount) {
                return response()->json([
                    'success' => false,
                    'message' => "Insufficient freebet. User has KSh {$currentFreebet}, trying to remove KSh {$amount}"
                ], 400);
            }
            
            // Remove freebet
            $newFreebet = $currentFreebet - $amount;
            $wallet->update(['freebet_amount' => $newFreebet]);
            
            // Record transaction
            DB::table('freebet_transactions')->insert([
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => 'removed',
                'reason' => $reason,
                'admin_id' => $adminId,
                'previous_balance' => $currentFreebet,
                'new_balance' => $newFreebet,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            DB::commit();
            
            \Log::info("Admin {$adminId} removed KSh {$amount} freebet from user {$user->id}. Reason: {$reason}");
            
            return response()->json([
                'success' => true,
                'message' => "Successfully removed KSh {$amount} freebet from " . $user->name,
                'data' => [
                    'user_id' => $user->id,
                    'username' => $user->name,
                    'previous_balance' => $currentFreebet,
                    'new_balance' => $newFreebet,
                    'amount_removed' => $amount
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Freebet removal failed: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove freebet: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Bulk add freebet to multiple users
     */
    public function bulkAddFreebet(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'reason' => 'nullable|string|max:500',
            'method' => 'required|string|in:manual,all,active'
        ]);
        
        $amount = floatval($request->amount);
        $reason = $request->reason ? $request->reason : 'Bulk admin bonus';
        $adminId = user('id');
        $method = $request->method;
        
        // Get user IDs based on method
        if ($method === 'all') {
            // All users
            $userIds = User::pluck('id')->toArray();
        } elseif ($method === 'active') {
            // Active users (users who placed bets in last 7 days)
            $userIds = DB::table('userbits')
                ->where('created_at', '>=', now()->subDays(7))
                ->distinct()
                ->pluck('userid')
                ->toArray();
        } else {
            // Manual selection
            $request->validate([
                'user_ids' => 'required|array',
                'user_ids.*' => 'integer|exists:users,id'
            ]);
            $userIds = $request->user_ids;
        }
        
        $successCount = 0;
        $failedUsers = [];
        
        DB::beginTransaction();
        
        try {
            foreach ($userIds as $userId) {
                try {
                    $wallet = Wallet::where('userid', $userId)->first();
                    
                    if (!$wallet) {
                        $failedUsers[] = $userId;
                        continue;
                    }
                    
                    $currentFreebet = floatval($wallet->freebet_amount);
                    $newFreebet = $currentFreebet + $amount;
                    
                    $wallet->update(['freebet_amount' => $newFreebet]);
                    
                    // Record transaction
                    DB::table('freebet_transactions')->insert([
                        'user_id' => $userId,
                        'amount' => $amount,
                        'type' => 'added',
                        'reason' => $reason,
                        'admin_id' => $adminId,
                        'previous_balance' => $currentFreebet,
                        'new_balance' => $newFreebet,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    $successCount++;
                    
                } catch (\Exception $e) {
                    $failedUsers[] = $userId;
                    \Log::error("Bulk freebet failed for user {$userId}: " . $e->getMessage());
                }
            }
            
            DB::commit();
            
            $totalAmount = $successCount * $amount;
            \Log::info("Admin {$adminId} bulk added KSh {$amount} freebet to {$successCount} users. Total: KSh {$totalAmount}");
            
            return response()->json([
                'success' => true,
                'message' => "Successfully added freebet to {$successCount} users",
                'data' => [
                    'success_count' => $successCount,
                    'failed_count' => count($failedUsers),
                    'failed_users' => $failedUsers,
                    'amount_per_user' => $amount,
                    'total_amount' => $totalAmount
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Bulk freebet addition failed: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Bulk operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get freebet statistics
     */
    public function getFreebetStats(Request $request)
    {
        $dateFrom = $request->date_from ? $request->date_from : now()->subDays(30)->format('Y-m-d');
        $dateTo = $request->date_to ? $request->date_to : now()->format('Y-m-d');
        
        // Total freebet in circulation
        $totalFreebet = Wallet::sum('freebet_amount');
        
        // Users with freebet
        $usersWithFreebet = Wallet::where('freebet_amount', '>', 0)->count();
        
        // Total added in period
        $totalAdded = DB::table('freebet_transactions')
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->where('type', 'added')
            ->sum('amount');
        
        // Total removed in period
        $totalRemoved = DB::table('freebet_transactions')
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->where('type', 'removed')
            ->sum('amount');
        
        // Top users by freebet balance
        $topUsers = Wallet::where('freebet_amount', '>', 0)
            ->orderBy('freebet_amount', 'desc')
            ->limit(10)
            ->get();
        
        foreach ($topUsers as $wallet) {
            $user = User::find($wallet->userid);
            $wallet->username = $user ? $user->name : 'Unknown';
        }
        
        // Daily distribution (last 7 days)
        $dailyDistribution = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $added = DB::table('freebet_transactions')
                ->whereDate('created_at', $date)
                ->where('type', 'added')
                ->sum('amount');
            $dailyDistribution[] = [
                'date' => $date,
                'amount' => floatval($added)
            ];
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_freebet' => floatval($totalFreebet),
                    'users_with_freebet' => $usersWithFreebet,
                    'total_added' => floatval($totalAdded),
                    'total_removed' => floatval($totalRemoved),
                    'net_change' => floatval($totalAdded - $totalRemoved)
                ],
                'top_users' => $topUsers,
                'daily_distribution' => $dailyDistribution
            ]
        ]);
    }
    
    /**
     * Get user freebet history
     */
    public function getUserFreebetHistory($userId)
    {
        $user = User::find($userId);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
        
        $wallet = Wallet::where('userid', $userId)->first();
        $currentBalance = $wallet ? floatval($wallet->freebet_amount) : 0;
        
        $transactions = DB::table('freebet_transactions')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Add admin info
        foreach ($transactions as $transaction) {
            $admin = User::find($transaction->admin_id);
            $transaction->admin_name = $admin ? $admin->name : 'System';
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'username' => $user->name,
                    'current_freebet' => $currentBalance
                ],
                'transactions' => $transactions
            ]
        ]);
    }
}
