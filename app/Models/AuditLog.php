<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'resource_type',
        'resource_id',
        'old_values',
        'new_values',
        'ip_address',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'json',
            'new_values' => 'json',
        ];
    }

    // ─── Relationships ─────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resource(): MorphTo
    {
        return $this->morphTo('resource', 'resource_type', 'resource_id');
    }

    // ─── Helpers ────────────────────────────────────────────

    /**
     * Log an admin action.
     */
    public static function log(
        string $action,
        ?Model $resource = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $notes = null,
    ): static {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'resource_type' => $resource ? get_class($resource) : null,
            'resource_id' => $resource?->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'notes' => $notes,
        ]);
    }

    /**
     * Get a human-readable description of the action.
     */
    public function getDescriptionAttribute(): string
    {
        $user = $this->user?->name ?? 'System';
        $resourceName = $this->resource_type ? class_basename($this->resource_type) : '';
        $resourceId = $this->resource_id ? " #{$this->resource_id}" : '';

        return match (true) {
            str_contains($this->action, 'created') => "{$user} created {$resourceName}{$resourceId}",
            str_contains($this->action, 'updated') => "{$user} updated {$resourceName}{$resourceId}",
            str_contains($this->action, 'deleted') => "{$user} deleted {$resourceName}{$resourceId}",
            str_contains($this->action, 'banned') => "{$user} banned {$resourceName}{$resourceId}",
            str_contains($this->action, 'unbanned') => "{$user} unbanned {$resourceName}{$resourceId}",
            str_contains($this->action, 'balance') => "{$user} adjusted balance for {$resourceName}{$resourceId}",
            str_contains($this->action, 'setting') => "{$user} changed settings",
            default => "{$user}: {$this->action}",
        };
    }
}
