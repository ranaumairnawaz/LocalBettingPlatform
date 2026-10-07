<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TournamentBet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tournament_match_id',
        'predicted_winner_id',
        'amount',
        'odds',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function match()
    {
        return $this->belongsTo(TournamentMatch::class, 'tournament_match_id');
    }

    public function predictedWinner()
    {
        return $this->belongsTo(User::class, 'predicted_winner_id');
    }
}
