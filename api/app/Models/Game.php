<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'type',
        'min_bet',
        'max_bet',
        'commission_rate',
        'is_active',
        'image',
        'thumbnail',
        'is_featured',
        'guidelines',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function logs()
    {
        return $this->hasMany(GameLog::class);
    }
}
