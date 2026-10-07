<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LudoRoomPlayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'ludo_room_id', 'user_id', 'color', 'is_ready', 'status', 'rank'
    ];

    public function room()
    {
        return $this->belongsTo(LudoRoom::class, 'ludo_room_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
