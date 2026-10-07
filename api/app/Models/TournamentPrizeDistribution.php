<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentPrizeDistribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'user_id',
        'tournament_registration_id',
        'rank',
        'rank_label',
        'prize_amount',
        'percentage_of_pool',
        'transaction_id',
        'distributed_at',
    ];

    protected $casts = [
        'prize_amount' => 'decimal:2',
        'percentage_of_pool' => 'decimal:2',
        'distributed_at' => 'datetime',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(TournamentRegistration::class, 'tournament_registration_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
