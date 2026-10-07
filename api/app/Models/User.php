<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasPushSubscriptions;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'balance',
        'referral_code',
        'referred_by',
        'is_admin',
        'avatar',
        'is_super_agent',
        'super_agent_team_name',
        'super_agent_referral_code',
        'super_agent_rank',
        'super_agent_approved_at',
        'super_agent_application_status',
        'super_agent_application_note',
        'super_agent_group_proof',
        'super_agent_referral_code_updated_at',
        'super_agent_rejected_at',
    ];

    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function gameLogs()
    {
        return $this->hasMany(GameLog::class);
    }

    public function withdrawalAccounts()
    {
        return $this->hasMany(UserWithdrawalAccount::class);
    }

    /**
     * Super Agent Relationships
     */
    public function superAgentCommissions()
    {
        return $this->hasMany(SuperAgentCommission::class, 'super_agent_id');
    }

    public function superAgentContentRequests()
    {
        return $this->hasMany(ContentBonusRequest::class, 'user_id');
    }

    public function superAgentChallengeEntries()
    {
        return $this->hasMany(SuperAgentChallengeEntry::class, 'super_agent_id');
    }

    /**
     * Super Agent Methods
     */
    public function isSuperAgent(): bool
    {
        return (bool)$this->is_super_agent;
    }

    public function superAgentRankLabel(): string
    {
        if (!$this->isSuperAgent()) {
            return '';
        }

        return match ($this->super_agent_rank) {
            'bronze' => 'Bronze',
            'silver' => 'Silver',
            'gold' => 'Gold',
            'diamond' => 'Diamond',
            default => 'Unranked',
        };
    }

    public function superAgentTeamSize(): int
    {
        if (!$this->isSuperAgent()) {
            return 0;
        }

        // Count level-1 recruits (direct referrals)
        return User::where('referred_by', $this->id)->count();
    }

    public function superAgentTotalEarnings()
    {
        if (!$this->isSuperAgent()) {
            return 0;
        }

        return $this->superAgentCommissions()->sum('commission_amount') ?? 0;
    }

    public function superAgentThisMonthEarnings()
    {
        if (!$this->isSuperAgent()) {
            return 0;
        }

        return $this->superAgentCommissions()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('commission_amount') ?? 0;
    }

    public function hasPendingApplication(): bool
    {
        return $this->super_agent_application_status === 'pending';
    }

    public function canApplyToBecomeSuperAgent(): bool
    {
        // Cannot apply if already a Super Agent
        if ($this->isSuperAgent()) {
            return false;
        }

        // Cannot apply if application is pending
        if ($this->hasPendingApplication()) {
            return false;
        }

        // Cannot apply if recently rejected (optional: add cooldown logic)
        // if ($this->super_agent_rejected_at && $this->super_agent_rejected_at->addDays(30)->isFuture()) {
        //     return false;
        // }

        return true;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'super_agent_approved_at' => 'datetime',
            'super_agent_rejected_at' => 'datetime',
            'super_agent_referral_code_updated_at' => 'datetime',
        ];
    }
}
