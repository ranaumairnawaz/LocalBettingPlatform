<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LudoGameLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'ludo_room_id', 'user_id', 'bet_amount', 'payout_amount', 'platform_fee_taken', 'result'
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
