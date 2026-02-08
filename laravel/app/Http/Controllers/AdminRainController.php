<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RainGiveaway;
use App\Models\RainParticipant;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AdminRainController extends Controller
{
    /**
     * Display admin rain management page
     */
    public function index()
    {
        $activeRains = RainGiveaway::where('status', 'active')
            ->with(['creator'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        $todayRains = RainGiveaway::whereDate('created_at', today())
            ->count();
        
        $todayDistributed = RainGiveaway::whereDate('created_at', today())
            ->where('status', 'completed')
            ->sum('total_amount');
        
        return view('admin.rain-management', compact('activeRains', 'todayRains', 'todayDistributed'));
    }
    
    /**
     * Create support rain (admin rain)
     */
    public function createSupportRain(Request $request)
    {
        $request->validate([
            'amount_per_user' => 'required|numeric|min:10',
            'num_winners' => 'required|integer|min:2|max:100',
            'message' => 'nullable|string|max:500'
        ]);
        
        $userId = user('id');
        $amountPerUser = floatval($request->amount_per_user);
        $numWinners = intval($request->num_winners);
        $totalAmount = $amountPerUser * $numWinners;
        $message = $request->message ?? 'Support Rain! Claim your freebet now! 🎁';
        
        DB::beginTransaction();
        
        try {
            // Create rain (no wallet deduction for admin)
            $rain = RainGiveaway::create([
                'created_by' => $userId,
                'total_amount' => $totalAmount,
                'amount_per_user' => $amountPerUser,
                'num_winners' => $numWinners,
                'message' => $message,
                'status' => 'active',
                'created_at' => now()
            ]);
            
            DB::commit();
            
            \Log::info("Admin created support rain: ID {$rain->id}, KSh {$totalAmount} total ({$numWinners} x {$amountPerUser})");
            
            return response()->json([
                'success' => true,
                'message' => 'Support rain created successfully!',
                'data' => [
                    'rain_id' => $rain->id,
                    'total_amount' => $totalAmount,
                    'amount_per_user' => $amountPerUser,
                    'num_winners' => $numWinners
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Admin rain creation failed: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create rain: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get rain history with filters
     */
    public function getRainHistory(Request $request)
    {
        $query = RainGiveaway::with(['creator']);
        
        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Filter by type (admin vs user)
        if ($request->has('type')) {
            if ($request->type === 'admin') {
                $query->whereHas('creator', function($q) {
                    $q->where('isadmin', 1);
                });
            } elseif ($request->type === 'user') {
                $query->whereHas('creator', function($q) {
                    $q->where('isadmin', 0);
                });
            }
        }
        
        $rains = $query->orderBy('created_at', 'desc')
            ->paginate(20);
        
        // Add additional info to each rain
        foreach ($rains as $rain) {
            $rain->participants_count = RainParticipant::where('rain_id', $rain->id)->count();
            $rain->winners_count = RainParticipant::where('rain_id', $rain->id)
                ->where('is_winner', true)
                ->count();
            $rain->creator_name = $rain->creator && $rain->creator->isadmin ? 'SUPPORT' : ($rain->creator->username ?? $rain->creator->name ?? 'Unknown');
        }
        
        return response()->json([
            'success' => true,
            'data' => $rains
        ]);
    }
    
    /**
     * Get rain analytics
     */
    public function getRainAnalytics(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->subDays(30)->format('Y-m-d');
        $dateTo = $request->date_to ?? now()->format('Y-m-d');
        
        // Total rains created
        $totalRains = RainGiveaway::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->count();
        
        // Admin rains
        $adminRains = RainGiveaway::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->whereHas('creator', function($q) {
                $q->where('isadmin', 1);
            })
            ->count();
        
        // User rains
        $userRains = RainGiveaway::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->whereHas('creator', function($q) {
                $q->where('isadmin', 0);
            })
            ->count();
        
        // Total distributed
        $totalDistributed = RainGiveaway::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->where('status', 'completed')
            ->sum('total_amount');
        
        // Admin distributed
        $adminDistributed = RainGiveaway::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->where('status', 'completed')
            ->whereHas('creator', function($q) {
                $q->where('isadmin', 1);
            })
            ->sum('total_amount');
        
        // User distributed
        $userDistributed = RainGiveaway::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->where('status', 'completed')
            ->whereHas('creator', function($q) {
                $q->where('isadmin', 0);
            })
            ->sum('total_amount');
        
        // Total participants
        $totalParticipants = RainParticipant::whereHas('rain', function($q) use ($dateFrom, $dateTo) {
            $q->whereDate('created_at', '>=', $dateFrom)
              ->whereDate('created_at', '<=', $dateTo);
        })->count();
        
        // Average participants per rain
        $avgParticipants = $totalRains > 0 ? round($totalParticipants / $totalRains, 2) : 0;
        
        // Most active rain users
        $mostActiveUsers = RainParticipant::select('user_id', DB::raw('COUNT(*) as participation_count'))
            ->whereHas('rain', function($q) use ($dateFrom, $dateTo) {
                $q->whereDate('created_at', '>=', $dateFrom)
                  ->whereDate('created_at', '<=', $dateTo);
            })
            ->groupBy('user_id')
            ->orderBy('participation_count', 'desc')
            ->limit(10)
            ->get();
        
        // Add user info
        foreach ($mostActiveUsers as $participant) {
            $user = User::find($participant->user_id);
            $participant->username = $user ? ($user->username ?? $user->name) : 'Unknown';
            $participant->total_won = RainParticipant::where('user_id', $participant->user_id)
                ->where('is_winner', true)
                ->sum('amount_won');
        }
        
        // Daily rain distribution (last 7 days)
        $dailyDistribution = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $amount = RainGiveaway::whereDate('created_at', $date)
                ->where('status', 'completed')
                ->sum('total_amount');
            $dailyDistribution[] = [
                'date' => $date,
                'amount' => floatval($amount)
            ];
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_rains' => $totalRains,
                    'admin_rains' => $adminRains,
                    'user_rains' => $userRains,
                    'total_distributed' => floatval($totalDistributed),
                    'admin_distributed' => floatval($adminDistributed),
                    'user_distributed' => floatval($userDistributed),
                    'total_participants' => $totalParticipants,
                    'avg_participants' => $avgParticipants
                ],
                'most_active_users' => $mostActiveUsers,
                'daily_distribution' => $dailyDistribution
            ]
        ]);
    }
    
    /**
     * Cancel active rain (emergency)
     */
    public function cancelRain($rainId)
    {
        try {
            $rain = RainGiveaway::find($rainId);
            
            if (!$rain) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rain not found'
                ], 404);
            }
            
            if ($rain->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Can only cancel active rains'
                ], 400);
            }
            
            DB::beginTransaction();
            
            // Mark rain as cancelled
            $rain->update([
                'status' => 'cancelled',
                'completed_at' => now()
            ]);
            
            // If user-created rain, refund the creator
            $creator = User::find($rain->created_by);
            if ($creator && !$creator->isadmin) {
                addwallet($rain->created_by, $rain->total_amount, "+");
                \Log::info("Refunded KSh {$rain->total_amount} to user {$rain->created_by} for cancelled rain {$rainId}");
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Rain cancelled successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Rain cancellation failed: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel rain: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get rain participants
     */
    public function getRainParticipants($rainId)
    {
        $rain = RainGiveaway::find($rainId);
        
        if (!$rain) {
            return response()->json([
                'success' => false,
                'message' => 'Rain not found'
            ], 404);
        }
        
        $participants = RainParticipant::where('rain_id', $rainId)
            ->orderBy('created_at', 'asc')
            ->get();
        
        // Add user info
        foreach ($participants as $participant) {
            $user = User::find($participant->user_id);
            $participant->username = $user ? ($user->username ?? $user->name) : 'Unknown';
            $participant->user_image = $user ? ($user->image ?? null) : null;
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'rain' => $rain,
                'participants' => $participants
            ]
        ]);
    }
}
