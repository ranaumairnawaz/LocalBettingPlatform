<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TournamentLeaderboard extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'season_id',
        'points',
        'matches_won',
        'tournaments_won',
        'total_earnings',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
