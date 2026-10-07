<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuperAgentChallengeEntry extends Model
{
    protected $fillable = [
        'challenge_id',
        'super_agent_id',
        'progress',
        'completed_at',
        'rewarded_at',
    ];

    protected $casts = [
        'progress' => 'integer',
        'completed_at' => 'datetime',
        'rewarded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the challenge for this entry
     */
    public function challenge(): BelongsTo
    {
        return $this->belongsTo(SuperAgentChallenge::class, 'challenge_id');
    }

    /**
     * Get the Super Agent for this entry
     */
    public function superAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'super_agent_id');
    }

    /**
     * Scopes
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }

    public function scopeNotRewarded($query)
    {
        return $query->whereNull('rewarded_at');
    }

    public function scopeRewarded($query)
    {
        return $query->whereNotNull('rewarded_at');
    }

    public function scopeInProgress($query)
    {
        return $query->whereNull('completed_at');
    }

    /**
     * Check if this entry is complete
     */
    public function isComplete(): bool
    {
        return $this->progress >= $this->challenge->target_value;
    }

    /**
     * Get progress as percentage
     */
    public function getProgressPercent(): int
    {
        if ($this->challenge->target_value == 0) {
            return 0;
        }
        return min(100, (int)(($this->progress / $this->challenge->target_value) * 100));
    }

    /**
     * Mark as completed if progress reaches target
     */
    public function checkAndMarkComplete()
    {
        if ($this->isComplete() && !$this->completed_at) {
            $this->update(['completed_at' => now()]);
        }
    }

    /**
     * Format progress for display
     */
    public function getProgressDisplay()
    {
        return "{$this->progress} / {$this->challenge->target_value}";
    }
}
