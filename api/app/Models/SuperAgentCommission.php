<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuperAgentCommission extends Model
{
    protected $fillable = [
        'super_agent_id',
        'source_user_id',
        'commission_type',
        'referral_level',
        'commission_amount',
        'transaction_id',
        'is_paid',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'commission_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the Super Agent who earned this commission
     */
    public function superAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'super_agent_id');
    }

    /**
     * Get the source user who generated this commission
     */
    public function sourceUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'source_user_id');
    }

    /**
     * Get the transaction record if paid
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Scopes
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('referral_level', $level);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }

    public function scopeDepositCommissions($query)
    {
        return $query->where('commission_type', 'deposit_commission');
    }

    public function scopeGameCommissions($query)
    {
        return $query->where('commission_type', 'game_commission');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
    }

    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    /**
     * Accessors & Mutators
     */
    public function getFormattedAmountAttribute()
    {
        return 'Rs. ' . number_format($this->commission_amount, 2);
    }

    public function getCommissionTypeLabel()
    {
        return match ($this->commission_type) {
            'deposit_commission' => 'Deposit Commission',
            'game_commission' => 'Game Commission',
            default => $this->commission_type,
        };
    }
}
