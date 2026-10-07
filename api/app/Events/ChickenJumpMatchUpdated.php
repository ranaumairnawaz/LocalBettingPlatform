<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChickenJumpMatchUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $matchId;
    public $gameState;

    /**
     * Create a new event instance.
     */
    public function __construct($matchId, $gameState)
    {
        $this->matchId   = $matchId;
        $this->gameState = $gameState;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chicken-jump.' . $this->matchId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chicken-jump.updated';
    }
}
