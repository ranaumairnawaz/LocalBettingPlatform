<?php

namespace App\Events;

use App\Models\Tournament;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TournamentBracketGenerated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $tournament;

    public function __construct(Tournament $tournament)
    {
        $this->tournament = $tournament->load(['matches']);
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
            'status' => $this->tournament->status,
            'total_matches' => $this->tournament->matches->count(),
            'rounds' => $this->tournament->matches->groupBy('round_number')->map(function ($matches) {
                return [
                    'round_number' => $matches->first()->round_number,
                    'match_count' => $matches->count(),
                ];
            }),
        ];
    }
}
