<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChickenFightRound extends Model
{
    protected $fillable = [
        'round_index',
        'winner',
        'winner_hp',
        'settled',
    ];
}
