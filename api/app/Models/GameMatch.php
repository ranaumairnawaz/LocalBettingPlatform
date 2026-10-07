<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_code',
        'game_type',
        'total_pot',
        'status',
        'game_state',
        'winner_id',
    ];

    protected $casts = [
        'game_state' => 'array',
        'total_pot' => 'decimal:2',
        'winner_id' => 'integer',
    ];

    public function players()
    {
        return $this->hasMany(GameMatchPlayer::class);
    }

    public function winner()
    {
        return $this->belongsTo(User::class, 'winner_id');
    }
}
