<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameLog extends Model
{
    protected $fillable = [
        'user_id',
        'game_id',
        'bet_amount',
        'payout_amount',
        'commission_amount',
        'result',
        'game_data',
        'status',
    ];

    protected $casts = [
        'game_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }
}
