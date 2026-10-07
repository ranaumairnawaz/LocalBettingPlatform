<?php

namespace App\Events;

use App\Models\TournamentMatch;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TournamentMatchReady implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $match;

    public function __construct(TournamentMatch $match)
    {
        $this->match = $match->load(['tournament', 'player1', 'player2']);
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel('tournament.' . $this->match->tournament_id),
            new PrivateChannel('user.' . $this->match->player1_id),
            new PrivateChannel('user.' . $this->match->player2_id),
        ];
    }

    public function broadcastWith()
    {
        return [
            'match_id' => $this->match->id,
            'tournament_id' => $this->match->tournament_id,
            'tournament_title' => $this->match->tournament->title,
            'round_number' => $this->match->round_number,
            'match_number' => $this->match->match_number,
            'player1' => [
                'id' => $this->match->player1->id,
                'name' => $this->match->player1->name,
            ],
            'player2' => [
                'id' => $this->match->player2->id,
                'name' => $this->match->player2->name,
            ],
            'ready_check_started_at' => $this->match->ready_check_started_at,
            'ready_check_expires_at' => $this->match->ready_check_started_at->addSeconds(30),
        ];
    }
}
