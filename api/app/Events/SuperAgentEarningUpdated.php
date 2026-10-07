<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SuperAgentEarningUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $superAgentId;
    public $amount;
    public $source;

    /**
     * Create a new event instance.
     */
    public function __construct($superAgentId, $amount, $source = 'deposit')
    {
        $this->superAgentId = $superAgentId;
        $this->amount = $amount;
        $this->source = $source;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("super-agent.{$this->superAgentId}"),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'super_agent_id' => $this->superAgentId,
            'amount' => (float)$this->amount,
            'source' => $this->source,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'earning.updated';
    }
}
