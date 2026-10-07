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

class TournamentMatchUpdate implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $match;

    public function __construct(TournamentMatch $match)
    {
        $this->match = $match->load(['player1', 'player2', 'winner']);
    }

    public function broadcastOn()
    {
        return [
            new Channel('presence-tournament.' . $this->match->tournament_id),
            new PrivateChannel('tournament.' . $this->match->tournament_id),
        ];
    }

    public function broadcastWith()
    {
        return [
            'match_id' => $this->match->id,
            'tournament_id' => $this->match->tournament_id,
            'round_number' => $this->match->round_number,
            'match_number' => $this->match->match_number,
            'player1' => $this->match->player1 ? [
                'id' => $this->match->player1->id,
                'name' => $this->match->player1->name,
            ] : null,
            'player2' => $this->match->player2 ? [
                'id' => $this->match->player2->id,
                'name' => $this->match->player2->name,
            ] : null,
            'winner' => $this->match->winner ? [
                'id' => $this->match->winner->id,
                'name' => $this->match->winner->name,
            ] : null,
            'status' => $this->match->status,
            'player1_ready' => $this->match->player1_ready,
            'player2_ready' => $this->match->player2_ready,
            'game_room_id' => $this->match->game_room_id,
        ];
    }
}
