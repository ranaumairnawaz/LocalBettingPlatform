<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentBonusRequest extends Model
{
    const PENDING = 'pending';
    const APPROVED = 'approved';
    const REJECTED = 'rejected';

    const PLATFORMS = [
        'youtube' => 'YouTube',
        'instagram' => 'Instagram',
        'tiktok' => 'TikTok',
    ];

    protected $fillable = [
        'user_id',
        'platform',
        'video_url',
        'view_count',
        'auto_detected_views',
        'screenshot_path',
        'status',
        'bonus_amount',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'view_count' => 'integer',
        'auto_detected_views' => 'integer',
        'bonus_amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the Super Agent who submitted this request
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who reviewed this request
     */
    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::REJECTED);
    }

    public function scopeByPlatform($query, $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }

    /**
     * Accessors & Mutators
     */
    public function getPlatformIcon()
    {
        return match ($this->platform) {
            'youtube' => '▶️',
            'instagram' => '📷',
            'tiktok' => '🎵',
            default => '📱',
        };
    }

    public function getPlatformLabel()
    {
        return self::PLATFORMS[$this->platform] ?? $this->platform;
    }

    public function getStatusBadgeClass()
    {
        return match ($this->status) {
            self::PENDING => 'badge-warning',
            self::APPROVED => 'badge-success',
            self::REJECTED => 'badge-danger',
            default => 'badge-secondary',
        };
    }

    public function getStatusBadgeText()
    {
        return match ($this->status) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            default => $this->status,
        };
    }

    /**
     * Estimate bonus based on view count and platform rates from settings
     */
    public function getEstimatedBonus()
    {
        $viewCount = $this->view_count;
        
        // Define milestone thresholds and their bonus amounts
        // These should be retrieved from SystemSetting in production
        $milestones = [
            500000 => 500,  // 500K+ views
            100000 => 300,  // 100K+ views
            50000 => 200,   // 50K+ views
            10000 => 100,   // 10K+ views
        ];

        $bonusPercentByPlatform = [
            'youtube' => 1.0,      // 100% of milestone
            'instagram' => 0.8,    // 80% of milestone
            'tiktok' => 0.9,       // 90% of milestone
        ];

        $platformMultiplier = $bonusPercentByPlatform[$this->platform] ?? 1.0;

        foreach ($milestones as $views => $bonus) {
            if ($viewCount >= $views) {
                return $bonus * $platformMultiplier;
            }
        }

        return 0; // Below minimum threshold
    }

    public function getFormattedBonusAttribute()
    {
        return $this->bonus_amount ? 'Rs. ' . number_format($this->bonus_amount, 2) : 'Pending';
    }
}
