<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuperAgentChallenge extends Model
{
    const TYPE_SIGNUPS = 'team_signups';
    const TYPE_DEPOSITS = 'team_deposits';
    const TYPE_GAMES = 'team_games_played';

    const TYPES = [
        self::TYPE_SIGNUPS => 'Team Signups',
        self::TYPE_DEPOSITS => 'Team Deposits',
        self::TYPE_GAMES => 'Team Games Played',
    ];

    protected $fillable = [
        'title',
        'description',
        'challenge_type',
        'target_value',
        'reward_amount',
        'starts_at',
        'ends_at',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'target_value' => 'integer',
        'reward_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the admin who created this challenge
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all challenge entries for this challenge
     */
    public function entries(): HasMany
    {
        return $this->hasMany(SuperAgentChallengeEntry::class, 'challenge_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('starts_at', '>', now());
    }

    public function scopeEnded($query)
    {
        return $query->where('ends_at', '<', now());
    }

    public function scopeByType($query, $type)
    {
        return $query->where('challenge_type', $type);
    }

    public function scopeRunning($query)
    {
        return $query->where('starts_at', '<=', now())
                     ->where('ends_at', '>=', now());
    }

    /**
     * Accessors & Mutators
     */
    public function getTypeLabel()
    {
        return self::TYPES[$this->challenge_type] ?? $this->challenge_type;
    }

    public function isRunning(): bool
    {
        return now()->isBetween($this->starts_at, $this->ends_at);
    }

    public function isUpcoming(): bool
    {
        return now()->isBefore($this->starts_at);
    }

    public function isEnded(): bool
    {
        return now()->isAfter($this->ends_at);
    }

    public function entriesCount(): int
    {
        return $this->entries()->count();
    }

    public function completedCount(): int
    {
        return $this->entries()->whereNotNull('completed_at')->count();
    }

    public function getTimeRemainingAttribute()
    {
        if ($this->isEnded()) {
            return 'Ended';
        }
        return $this->ends_at->diffForHumans(now(), true);
    }

    public function getFormattedRewardAttribute()
    {
        return 'Rs. ' . number_format($this->reward_amount, 2);
    }
}
