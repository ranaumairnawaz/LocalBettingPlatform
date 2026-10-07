<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tournament extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'game_id',
        'title',
        'description',
        'entry_fee',
        'min_players',
        'max_players',
        'registration_opens_at',
        'registration_closes_at',
        'starts_at',
        'ends_at',
        'winner_prize_percentage',
        'runner_up_prize_percentage',
        'include_semifinalist_prizes',
        'semifinalist_prize_percentage',
        'platform_commission_percentage',
        'status',
        'registered_players_count',
        'total_pool',
        'total_distributed_prizes',
        'house_profit',
        'is_dynamic_fee',
        'base_entry_fee',
        'fee_increment_amount',
        'fee_increment_every_n_players',
        'bracket_type',
    ];

    protected $casts = [
        'entry_fee' => 'decimal:2',
        'winner_prize_percentage' => 'decimal:2',
        'runner_up_prize_percentage' => 'decimal:2',
        'semifinalist_prize_percentage' => 'decimal:2',
        'platform_commission_percentage' => 'decimal:2',
        'total_pool' => 'decimal:2',
        'total_distributed_prizes' => 'decimal:2',
        'house_profit' => 'decimal:2',
        'registration_opens_at' => 'datetime',
        'registration_closes_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_dynamic_fee' => 'boolean',
        'base_entry_fee' => 'decimal:2',
        'fee_increment_amount' => 'decimal:2',
        'fee_increment_every_n_players' => 'integer',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(TournamentRegistration::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class);
    }

    public function prizeDistributions(): HasMany
    {
        return $this->hasMany(TournamentPrizeDistribution::class);
    }

    public function isOpenForRegistration(): bool
    {
        // If status is not 'open', registration is definitely closed
        if ($this->status !== 'open') {
            return false;
        }

        // If registration_closes_at is set, ensure we haven't passed it
        if ($this->registration_closes_at && now()->isAfter($this->registration_closes_at)) {
            return false;
        }

        // If registration_opens_at is set, ensure we've passed it
        if ($this->registration_opens_at && now()->isBefore($this->registration_opens_at)) {
            return false;
        }

        return true;
    }

    public function isFull(): bool
    {
        return $this->registered_players_count >= $this->max_players;
    }

    public function getRemainingSlots(): int
    {
        return max(0, $this->max_players - $this->registered_players_count);
    }

    public function getDynamicEntryFeeAttribute(): float
    {
        if (!$this->is_dynamic_fee || $this->fee_increment_every_n_players <= 0) {
            return (float) $this->entry_fee;
        }

        $increments = floor($this->registered_players_count / $this->fee_increment_every_n_players);
        return (float) $this->base_entry_fee + ($increments * $this->fee_increment_amount);
    }

    public function calculateTotalPool(): float
    {
        // In a dynamic fee system, calculating the total pool accurately requires summing
        // the actual entry_fee_paid from registrations rather than multiplying.
        if ($this->is_dynamic_fee) {
            return (float) $this->registrations()->sum('entry_fee_paid');
        }
        return $this->entry_fee * $this->registered_players_count;
    }

    public function calculatePlatformCommission(): float
    {
        return $this->calculateTotalPool() * ($this->platform_commission_percentage / 100);
    }

    public function calculatePrizePool(): float
    {
        return $this->calculateTotalPool() - $this->calculatePlatformCommission();
    }

    public function getTimeRemainingAttribute(): string
    {
        if ($this->status === 'open' || $this->status === 'draft') {
            $target = $this->registration_closes_at;
        } elseif ($this->status === 'registration_closed') {
            $target = $this->starts_at;
        } else {
            return 'Started';
        }

        if ($target->isPast()) {
            return 'Ended';
        }

        $diff = now()->diff($target);
        
        if ($diff->days > 0) {
            return $diff->days . 'd ' . $diff->h . 'h';
        } elseif ($diff->h > 0) {
            return $diff->h . 'h ' . $diff->i . 'm';
        } else {
            return $diff->i . 'm ' . $diff->s . 's';
        }
    }
}
