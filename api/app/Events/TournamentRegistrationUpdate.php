<?php

namespace App\Events;

use App\Models\Tournament;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TournamentRegistrationUpdate implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $tournament;

    public function __construct(Tournament $tournament)
    {
        $this->tournament = $tournament->fresh();
    }

    public function broadcastOn()
    {
        return [
            new Channel('presence-tournament.' . $this->tournament->id),
        ];
    }

    public function broadcastWith()
    {
        return [
            'tournament_id' => $this->tournament->id,
            'registered_players_count' => $this->tournament->registered_players_count,
            'max_players' => $this->tournament->max_players,
            'remaining_slots' => $this->tournament->getRemainingSlots(),
            'total_pool' => $this->tournament->calculateTotalPool(),
            'is_full' => $this->tournament->isFull(),
        ];
    }
}
