<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AviatorRound extends Model
{
    protected $fillable = [
        'round_index',
        'crash_multiplier',
        'settled',
    ];

    protected $casts = [
        'round_index' => 'integer',
        'crash_multiplier' => 'float',
        'settled' => 'boolean',
    ];
}
