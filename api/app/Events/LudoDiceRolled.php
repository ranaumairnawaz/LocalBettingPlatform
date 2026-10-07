<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LudoDiceRolled implements \Illuminate\Contracts\Broadcasting\ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;
    public $diceValue;
    public $turnColor;

    public function __construct($roomId, $diceValue, $turnColor)
    {
        $this->roomId = $roomId;
        $this->diceValue = $diceValue;
        $this->turnColor = $turnColor;
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
        return 'ludo.dice.rolled';
    }
}
