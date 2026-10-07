<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'user_id',
        'entry_fee_paid',
        'registered_at',
        'bracket_position',
        'received_bye',
        'final_rank',
        'prize_won',
        'status',
    ];

    protected $casts = [
        'entry_fee_paid' => 'decimal:2',
        'prize_won' => 'decimal:2',
        'registered_at' => 'datetime',
        'received_bye' => 'boolean',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class, 'player1_id')
            ->union($this->hasMany(TournamentMatch::class, 'player2_id'));
    }

    public function prizeDistributions(): HasMany
    {
        return $this->hasMany(TournamentPrizeDistribution::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['registered', 'winner', 'runner_up', 'semifinalist']);
    }

    public function isEliminated(): bool
    {
        return in_array($this->status, ['eliminated', 'forfeited', 'disqualified']);
    }
}
