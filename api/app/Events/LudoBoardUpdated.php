<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LudoBoardUpdated implements \Illuminate\Contracts\Broadcasting\ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;
    public $gameState;

    public function __construct($roomId, $gameState)
    {
        $this->roomId = $roomId;
        $this->gameState = $gameState;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('ludo-room.' . $this->roomId),
        ];
    }

    public function broadcastAs()
    {
        return 'ludo.updated';
    }
}
