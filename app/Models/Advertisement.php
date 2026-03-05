<?php

namespace App\Models;

use App\Enums\AdPlacement;
use Illuminate\Database\Eloquent\Model;

class Advertisement extends Model
{
    protected $fillable = [
        'title',
        'image',
        'url',
        'placement',
        'is_active',
        'impressions',
        'clicks',
        'starts_at',
        'ends_at',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'placement' => AdPlacement::class,
            'is_active' => 'boolean',
            'impressions' => 'integer',
            'clicks' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'priority' => 'integer',
        ];
    }

    /**
     * Scope to get currently active ads.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    /**
     * Scope by placement.
     */
    public function scopeForPlacement($query, AdPlacement $placement)
    {
        return $query->where('placement', $placement)->orderByDesc('priority');
    }

    public function getImageUrlAttribute(): string
    {
        return asset('storage/' . $this->image);
    }

    public function recordImpression(): void
    {
        $this->increment('impressions');
    }

    public function recordClick(): void
    {
        $this->increment('clicks');
    }
}
