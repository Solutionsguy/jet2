<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Bridge between Laravel and Socket.IO Server
 * This controller communicates with the Node.js socket server
 */
class SocketBridge extends Controller
{
    private $socketServerUrl;

    public function __construct()
    {
        $this->socketServerUrl = env('SOCKET_SERVER_URL', 'http://localhost:3000');
    }

    /**
     * Get current game state from socket server
     */
    public function getGameState()
    {
        try {
            $response = Http::get($this->socketServerUrl . '/health');
            
            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'gameStatus' => $data['gameStatus'],
                    'currentMultiplier' => $data['currentMultiplier'],
                    'connections' => $data['connections']
                ]);
            }
            
            return response()->json(['success' => false, 'message' => 'Socket server not responding']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Notify socket server about game events
     */
    public function notifySocketServer($event, $data)
    {
        try {
            // This would require adding an HTTP endpoint to the socket server
            // For now, we'll rely on client-side socket emissions
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
