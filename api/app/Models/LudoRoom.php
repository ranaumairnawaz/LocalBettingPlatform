<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LudoRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_code', 'max_players', 'entry_fee', 'status', 'game_state', 'last_activity_at'
    ];

    protected $casts = [
        'game_state' => 'array',
        'last_activity_at' => 'datetime',
    ];

    public function players()
    {
        return $this->hasMany(LudoRoomPlayer::class);
    }

    public function logs()
    {
        return $this->hasMany(LudoGameLog::class);
    }
}
