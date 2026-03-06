<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements MustVerifyEmail, FilamentUser, HasName
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

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
        'avatar',
        'provider',
        'provider_id',
        'is_guest',
        'guest_token',
        'is_banned',
        'muted_until',
        'role',
        'last_seen_at',
        'settings',
        // Security & Responsible Gaming (Phase 10)
        'self_excluded',
        'self_excluded_until',
        'daily_deposit_limit',
        'weekly_deposit_limit',
        'monthly_deposit_limit',
        'daily_bet_limit',
        'cooldown_until',
        'last_login_ip',
        'registration_ip',
        'browser_fingerprint',
        'is_flagged',
        'flag_reason',
        'flagged_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'guest_token',
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
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
            'is_guest' => 'boolean',
            'is_banned' => 'boolean',
            'muted_until' => 'datetime',
            'settings' => 'json',
            'role' => UserRole::class,
            // Security & Responsible Gaming (Phase 10)
            'self_excluded' => 'boolean',
            'self_excluded_until' => 'datetime',
            'daily_deposit_limit' => 'decimal:2',
            'weekly_deposit_limit' => 'decimal:2',
            'monthly_deposit_limit' => 'decimal:2',
            'daily_bet_limit' => 'decimal:2',
            'cooldown_until' => 'datetime',
            'is_flagged' => 'boolean',
            'flagged_at' => 'datetime',
        ];
    }

    /**
     * Determine if the user can access the Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Filament display name (must always be a non-null string).
     */
    public function getFilamentName(): string
    {
        return $this->username
            ?? $this->name
            ?? $this->email
            ?? ('User #' . $this->id);
    }

    // ─── Relationships ─────────────────────────────────────

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function coinBalance(): HasOne
    {
        return $this->hasOne(CoinBalance::class);
    }

    public function bets(): HasMany
    {
        return $this->hasMany(Bet::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function autoBetConfig(): HasOne
    {
        return $this->hasOne(AutoBetConfig::class);
    }

    public function provablyFairSeeds(): HasMany
    {
        return $this->hasMany(ProvablyFairSeed::class);
    }

    public function activeProvablyFairSeed(): HasOne
    {
        return $this->hasOne(ProvablyFairSeed::class)->where('is_active', true);
    }

    public function loginLogs(): HasMany
    {
        return $this->hasMany(LoginLog::class);
    }

    public function suspiciousActivities(): HasMany
    {
        return $this->hasMany(SuspiciousActivity::class);
    }

    // ─── Helpers ────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isModerator(): bool
    {
        return $this->role === UserRole::Moderator || $this->role === UserRole::Admin;
    }

    public function isGuest(): bool
    {
        return $this->is_guest;
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            $path = ltrim($this->avatar, '/');

            if (str_starts_with($path, 'storage/')) {
                $path = substr($path, 8);
            }

            if (str_starts_with($path, 'public/')) {
                $path = substr($path, 7);
            }

            return asset('storage/' . $path);
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode($this->username) . '&background=random&color=fff';
    }

    public function getDisplayBalanceAttribute(): string
    {
        $coins = $this->coinBalance?->balance ?? 0;
        return number_format($coins, 2);
    }
}
