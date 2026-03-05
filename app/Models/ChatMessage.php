<?php

namespace App\Models;

use App\Enums\ChatMessageType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'message',
        'type',
        'is_deleted',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => ChatMessageType::class,
            'is_deleted' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deletedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Scope to get visible (non-deleted) messages.
     */
    public function scopeVisible($query)
    {
        return $query->where('is_deleted', false);
    }

    /**
     * Get the formatted message for broadcast.
     */
    public function toBroadcastArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'username' => $this->user->username,
            'avatar' => $this->user->avatar_url,
            'message' => $this->message,
            'type' => $this->type->value,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
