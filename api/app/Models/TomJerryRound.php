<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TomJerryRound extends Model
{
    protected $table = 'tom_jerry_rounds';

    protected $fillable = [
        'round_index',
        'winner',
        'winner_hp',
        'settled',
    ];

    protected $casts = [
        'round_index' => 'integer',
        'settled' => 'boolean',
    ];
}
