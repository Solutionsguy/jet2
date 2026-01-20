<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameCrashed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $gameId;
    public $crashMultiplier;
    public $results;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($gameId, $crashMultiplier, $results = [])
    {
        $this->gameId = $gameId;
        $this->crashMultiplier = $crashMultiplier;
        $this->results = $results;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('aviator-game');
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs()
    {
        return 'gameCrashed';
    }
}
