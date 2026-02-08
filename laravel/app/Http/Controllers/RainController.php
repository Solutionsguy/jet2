<?php

namespace App\Http\Controllers;

use App\Models\RainGiveaway;
use App\Models\RainParticipant;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class RainController extends Controller
{
    /**
     * Create a new rain/giveaway (Admin or any user with sufficient balance)
     */
    public function createRain(Request $request)
    {
        $userId = user('id');
        
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'You must be logged in to create rain'
            ], 401);
        }
        
        $isAdmin = user('isadmin');

        $validator = Validator::make($request->all(), [
            'amount_per_user' => 'required|numeric|min:1|max:10000',
            'num_winners' => 'required|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $amountPerUser = floatval($request->amount_per_user);
        $numWinners = intval($request->num_winners);
        $totalAmount = $amountPerUser * $numWinners;

        // Check user's wallet balance (unless admin)
        if (!$isAdmin) {
            $wallet = \App\Models\Wallet::where('userid', $userId)->first();
            $currentBalance = $wallet ? floatval($wallet->amount) : 0;
            
            if ($currentBalance < $totalAmount) {
                return response()->json([
                    'success' => false,
                    'message' => "Insufficient balance. You have KSh " . number_format($currentBalance, 2) . " but need KSh " . number_format($totalAmount, 2)
                ], 400);
            }
            
            // Deduct from user's wallet
            addwallet($userId, $totalAmount, "-");
            
            \Log::info("User {$userId} created rain and deducted KSh {$totalAmount} from wallet");
        }

        // Create the rain giveaway
        $rain = RainGiveaway::create([
            'created_by' => $userId,
            'total_amount' => $totalAmount,
            'amount_per_user' => $amountPerUser,
            'num_winners' => $numWinners,
            'status' => 'active',
            'started_at' => now()
        ]);

        // Post rain as a special chat message so it persists in chat history
        // Get creator's username
        $creator = User::find($userId);
        $creatorUsername = $creator ? ($creator->username ?? $creator->name ?? 'User') : 'User';
        
        // Only show "SUPPORT" if admin, otherwise show masked username
        $displayUsername = $isAdmin ? 'SUPPORT' : $creatorUsername;
        
        ChatMessage::create([
            'user_id' => $userId,
            'username' => $displayUsername,
            'message' => '__RAIN_CARD__' . $rain->id, // Special marker for rain cards
            'avatar' => $creator ? ($creator->image ?? null) : null,
            'is_admin' => $isAdmin ? true : false
        ]);

        // Get updated wallet balance
        $wallet = \App\Models\Wallet::where('userid', $userId)->first();
        $newBalance = $wallet ? floatval($wallet->amount) : 0;
        
        return response()->json([
            'success' => true,
            'message' => 'Rain created successfully',
            'data' => $rain,
            'wallet_balance' => $newBalance
        ]);
    }

    /**
     * Get active rain giveaway
     */
    public function getActiveRain()
    {
        $rain = RainGiveaway::where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$rain) {
            return response()->json([
                'success' => false,
                'message' => 'No active rain'
            ]);
        }

        // Get all participants with user details
        $participants = RainParticipant::where('rain_id', $rain->id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function($participant) use ($rain) {
                $user = User::find($participant->user_id);
                return [
                    'user_id' => $participant->user_id,
                    'username' => $user ? ($user->username ?? $user->name ?? 'User') : 'User',
                    'avatar' => $user ? ($user->image ?? null) : null,
                    'amount' => $participant->amount_won > 0 ? $participant->amount_won : $rain->amount_per_user,
                    'is_winner' => $participant->is_winner
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'rain' => $rain,
                'participant_count' => $participants->count(),
                'participants' => $participants,
                'slots_available' => $rain->num_winners - $participants->count()
            ]
        ]);
    }

    /**
     * User joins the rain giveaway
     */
    public function joinRain(Request $request)
    {
        $userId = user('id');
        
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'You must be logged in to join'
            ], 401);
        }

        // Get active rain
        $rain = RainGiveaway::where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$rain) {
            return response()->json([
                'success' => false,
                'message' => 'No active rain'
            ], 404);
        }

        // Check if user already joined (strict one claim per user per rain)
        $existingParticipant = RainParticipant::where('rain_id', $rain->id)
            ->where('user_id', $userId)
            ->first();

        if ($existingParticipant) {
            return response()->json([
                'success' => false,
                'message' => 'You have already claimed this rain. Each user can only claim once per rain.'
            ], 400);
        }
        
        // Additional check: Ensure rain still has available slots
        $currentParticipants = RainParticipant::where('rain_id', $rain->id)->count();
        
        if ($currentParticipants >= $rain->num_winners) {
            return response()->json([
                'success' => false,
                'message' => 'This rain is fully claimed. All slots have been taken.'
            ], 400);
        }

        // Use DB transaction to prevent race conditions
        DB::beginTransaction();
        
        try {
            // Double-check within transaction
            $exists = RainParticipant::where('rain_id', $rain->id)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->exists();
            
            if ($exists) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'You have already claimed this rain.'
                ], 400);
            }
            
            // Add user to participants
            $participant = RainParticipant::create([
                'rain_id' => $rain->id,
                'user_id' => $userId,
                'is_winner' => false,
                'amount_won' => 0
            ]);
            
            DB::commit();

            $participantCount = RainParticipant::where('rain_id', $rain->id)->count();
            
            // Get user info for response
            $user = User::find($userId);
            $username = $user ? ($user->username ?? $user->name ?? 'User') : 'User';
            $avatar = $user ? ($user->image ?? null) : null;
            
            // Get rain creator to check if admin
            $creator = User::find($rain->created_by);
            $isAdminRain = $creator && $creator->isadmin;
            
            // USER RAIN: Instant credit to cash wallet (first-come, first-served)
            // ADMIN RAIN: Instant credit to freebet wallet (first-come, first-served)
            if (!$isAdminRain) {
                // User rain - Add money immediately to cash wallet
                addwallet($userId, $rain->amount_per_user, "+");
                \Log::info("User rain instant credit: Added KSh {$rain->amount_per_user} to cash wallet for user {$userId}");
            } else {
                // Admin rain - Add money immediately to freebet wallet
                $this->addFreebetWallet($userId, $rain->amount_per_user);
                \Log::info("Admin rain instant credit: Added KSh {$rain->amount_per_user} to freebet wallet for user {$userId}");
            }
            
            // Mark this participant as winner immediately (for both types)
            $participant->update([
                'is_winner' => true,
                'amount_won' => $rain->amount_per_user
            ]);
            
            // If all slots filled, mark rain as completed
            if ($participantCount >= $rain->num_winners) {
                $rain->update([
                    'status' => 'completed',
                    'completed_at' => now()
                ]);
                \Log::info("Rain {$rain->id} completed - all {$participantCount} slots claimed");
            }

            // Get updated wallet balances after instant credit
            $wallet = \App\Models\Wallet::where('userid', $userId)->first();
            $updatedBalance = $wallet ? floatval($wallet->amount) : 0;
            $updatedFreebetBalance = $wallet ? floatval($wallet->freebet_amount) : 0;
            
            return response()->json([
                'success' => true,
                'message' => 'Successfully claimed your rain slot!',
                'data' => [
                    'participant_count' => $participantCount,
                    'user' => [
                        'username' => $username,
                        'avatar' => $avatar
                    ],
                    'wallet_balance' => $updatedBalance,
                    'freebet_balance' => $updatedFreebetBalance,
                    'amount_won' => $rain->amount_per_user,
                    'is_admin_rain' => $isAdminRain
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Rain join error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to join rain. Please try again.'
            ], 500);
        }
    }

    /**
     * Distribute rain to random winners (Admin only)
     */
    public function distributeRain(Request $request, $rainId)
    {
        $userId = user('id');
        $isAdmin = user('isadmin');
        
        if (!$userId || !$isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

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
                'message' => 'Rain is not active'
            ], 400);
        }

        // Get all participants
        $participants = RainParticipant::where('rain_id', $rainId)->get();

        if ($participants->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No participants'
            ], 400);
        }

        // Select random winners
        $numWinners = min($rain->num_winners, $participants->count());
        $winners = $participants->random($numWinners);

        $winnerIds = [];
        $winnerNames = [];

        DB::beginTransaction();
        try {
            // Check if rain creator is admin
            $rainCreator = RainGiveaway::find($rainId);
            $isAdminRain = false;
            if ($rainCreator) {
                $creator = User::find($rainCreator->created_by);
                $isAdminRain = $creator && $creator->isadmin;
            }
            
            foreach ($winners as $winner) {
                // Mark as winner
                $winner->update([
                    'is_winner' => true,
                    'amount_won' => $rain->amount_per_user
                ]);

                // Add money to winner's wallet based on rain type
                if ($isAdminRain) {
                    // Admin/Support rain → Add to FREEBET wallet (with wagering requirements)
                    $this->addFreebetWallet($winner->user_id, $rain->amount_per_user);
                    \Log::info("Admin rain: Added KSh {$rain->amount_per_user} to freebet wallet for user {$winner->user_id}");
                } else {
                    // User rain → Add to REAL CASH wallet (no wagering)
                    addwallet($winner->user_id, $rain->amount_per_user, "+");
                    \Log::info("User rain: Added KSh {$rain->amount_per_user} to cash wallet for user {$winner->user_id}");
                }

                $winnerUser = User::find($winner->user_id);
                $winnerIds[] = $winner->user_id;
                $winnerNames[] = $winnerUser ? ($winnerUser->username ?? $winnerUser->name ?? 'User') : 'User';
            }

            // Update rain status
            $rain->update([
                'status' => 'completed',
                'completed_at' => now(),
                'winners' => $winnerIds
            ]);

            // Winners will be displayed in the rain card itself

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Rain distributed successfully',
                'data' => [
                    'num_winners' => $numWinners,
                    'amount_per_winner' => $rain->amount_per_user,
                    'total_distributed' => $rain->amount_per_user * $numWinners
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to distribute rain: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Auto-distribute rain when all slots are filled
     */
    private function autoDistributeRain($rainId)
    {
        $rain = RainGiveaway::find($rainId);
        
        if (!$rain || $rain->status !== 'active') {
            return;
        }
        
        DB::beginTransaction();
        
        try {
            $participants = RainParticipant::where('rain_id', $rainId)->get();
            
            if ($participants->count() == 0) {
                DB::rollBack();
                return;
            }
            
            // Randomly select winners
            $numWinners = min($rain->num_winners, $participants->count());
            $winners = $participants->random($numWinners);
            
            $winnerIds = [];
            $winnerNames = [];
            
            foreach ($winners as $winner) {
                // Mark as winner
                $winner->update([
                    'is_winner' => true,
                    'amount_won' => $rain->amount_per_user
                ]);
                
                // Check if rain creator is admin
                $creator = User::find($rain->created_by);
                $isAdminRain = $creator && $creator->isadmin;
                
                // Add money to winner's wallet
                if ($isAdminRain) {
                    // Admin rain → Freebet wallet
                    $this->addFreebetWallet($winner->user_id, $rain->amount_per_user);
                    \Log::info("Auto-distribute: Admin rain KSh {$rain->amount_per_user} to freebet wallet for user {$winner->user_id}");
                } else {
                    // User rain → Money wallet
                    addwallet($winner->user_id, $rain->amount_per_user, "+");
                    \Log::info("Auto-distribute: User rain KSh {$rain->amount_per_user} to cash wallet for user {$winner->user_id}");
                }
                
                $winnerUser = User::find($winner->user_id);
                $winnerIds[] = $winner->user_id;
                $winnerNames[] = $winnerUser ? ($winnerUser->username ?? $winnerUser->name ?? 'User') : 'User';
            }
            
            // Update rain status
            $rain->update([
                'status' => 'completed',
                'completed_at' => now(),
                'winners' => $winnerIds
            ]);
            
            DB::commit();
            
            \Log::info("Auto-distributed rain {$rainId} to {$numWinners} winners");
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Auto-distribute error for rain {$rainId}: " . $e->getMessage());
        }
    }
    
    /**
     * Add amount to user's freebet wallet
     */
    private function addFreebetWallet($userId, $amount)
    {
        $wallet = \App\Models\Wallet::where('userid', $userId)->first();
        
        if ($wallet) {
            $currentFreebetBalance = floatval($wallet->freebet_amount);
            $newFreebetBalance = $currentFreebetBalance + floatval($amount);
            
            \App\Models\Wallet::where('userid', $userId)->update([
                'freebet_amount' => $newFreebetBalance
            ]);
            
            return $newFreebetBalance;
        }
        
        return 0;
    }
}



