<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    /**
     * Get recent chat messages
     */
    public function getMessages(Request $request)
    {
        $limit = $request->input('limit', 50);
        
        $messages = ChatMessage::where('is_deleted', false)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
        
        // Include rain data for rain card messages
        $rainData = [];
        foreach ($messages as $message) {
            if (strpos($message->message, '__RAIN_CARD__') === 0) {
                $rainId = str_replace('__RAIN_CARD__', '', $message->message);
                
                // Fetch rain data
                $rain = \App\Models\RainGiveaway::find($rainId);
                if ($rain) {
                    $participants = \App\Models\RainParticipant::where('rain_id', $rainId)
                        ->orderBy('created_at', 'asc')
                        ->get()
                        ->map(function($participant) use ($rain) {
                            $user = \App\Models\User::find($participant->user_id);
                            return [
                                'user_id' => $participant->user_id,
                                'username' => $user ? ($user->username ?? $user->name ?? 'User') : 'User',
                                'avatar' => $user ? ($user->image ?? null) : null,
                                'amount' => $participant->amount_won > 0 ? $participant->amount_won : $rain->amount_per_user,
                                'is_winner' => $participant->is_winner
                            ];
                        });
                    
                    $rainData[$rainId] = [
                        'rain' => $rain,
                        'participant_count' => $participants->count(),
                        'participants' => $participants,
                        'slots_available' => max(0, $rain->num_winners - $participants->count())
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'messages' => $messages,
            'rain_data' => $rainData
        ]);
    }

    /**
     * Send a new chat message
     */
    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:500|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // Get authenticated user using the same helper as other controllers
        $userId = user('id');
        
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'You must be logged in to chat'
            ], 401);
        }

        // Rate limiting: max 5 messages per minute
        $recentMessages = ChatMessage::where('user_id', $userId)
            ->where('created_at', '>', now()->subMinute())
            ->count();

        if ($recentMessages >= 5) {
            return response()->json([
                'success' => false,
                'message' => 'You are sending messages too fast. Please wait.'
            ], 429);
        }

        // Create message
        $chatMessage = ChatMessage::create([
            'user_id' => $userId,
            'username' => user('username') ?? user('name') ?? 'User',
            'message' => strip_tags($request->message), // Remove HTML tags
            'avatar' => user('image') ?? null,
            'is_admin' => user('isadmin') ?? false
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message sent',
            'data' => $chatMessage
        ]);
    }

    /**
     * Delete a message (admin only)
     */
    public function deleteMessage(Request $request, $id)
    {
        $userId = user('id');
        $isAdmin = user('isadmin');
        
        if (!$userId || !$isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $message = ChatMessage::find($id);
        
        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found'
            ], 404);
        }

        $message->update(['is_deleted' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Message deleted'
        ]);
    }
}
