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
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements MustVerifyEmail, FilamentUser
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
        ];
    }

    /**
     * Determine if the user can access the Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === UserRole::Admin;
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
            return asset('storage/' . $this->avatar);
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode($this->username) . '&background=random&color=fff';
    }

    public function getDisplayBalanceAttribute(): string
    {
        $coins = $this->coinBalance?->balance ?? 0;
        return number_format($coins, 2);
    }
}
