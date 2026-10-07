<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'round_id',
        'round_number',
        'match_number',
        'player1_id',
        'player2_id',
        'winner_id',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'player1_ready',
        'player2_ready',
        'ready_check_started_at',
        'game_room_id',
        'player1_metrics',
        'player2_metrics',
        'result_type',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'ready_check_started_at' => 'datetime',
        'player1_ready' => 'boolean',
        'player2_ready' => 'boolean',
        'player1_metrics' => 'array',
        'player2_metrics' => 'array',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function player1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player1_id');
    }

    public function player2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player2_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function isReadyToStart(): bool
    {
        return $this->player1_ready && $this->player2_ready;
    }

    public function hasBye(): bool
    {
        return $this->player1_id === null || $this->player2_id === null;
    }

    public function getOpponent(int $userId): ?User
    {
        if ($this->player1_id === $userId) {
            return $this->player2;
        } elseif ($this->player2_id === $userId) {
            return $this->player1;
        }
        return null;
    }

    public function isPlayerReady(int $userId): bool
    {
        if ($this->player1_id === $userId) {
            return $this->player1_ready;
        } elseif ($this->player2_id === $userId) {
            return $this->player2_ready;
        }
        return false;
    }

    public function setPlayerReady(int $userId): void
    {
        if ($this->player1_id === $userId) {
            $this->player1_ready = true;
        } elseif ($this->player2_id === $userId) {
            $this->player2_ready = true;
        }
    }
}
