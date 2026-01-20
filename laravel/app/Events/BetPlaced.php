<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BetPlaced implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $username;
    public $amount;
    public $betId;
    public $avatar;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($userId, $username, $amount, $betId, $avatar = null)
    {
        $this->userId = $userId;
        $this->username = $username;
        $this->amount = $amount;
        $this->betId = $betId;
        $this->avatar = $avatar;
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
        return 'betPlaced';
    }
}
