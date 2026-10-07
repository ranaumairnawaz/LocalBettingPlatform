<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameMatchPlayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_match_id',
        'user_id',
        'bet_amount',
        'color',
        'status',
    ];

    protected $casts = [
        'game_match_id' => 'integer',
        'user_id' => 'integer',
        'bet_amount' => 'decimal:2',
    ];

    public function match()
    {
        return $this->belongsTo(GameMatch::class, 'game_match_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
