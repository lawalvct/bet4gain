<?php

namespace App\Enums;

enum UserRole: string
{
    case User = 'user';
    case Admin = 'admin';
    case Moderator = 'moderator';

    public function label(): string
    {
        return match ($this) {
            self::User => 'User',
            self::Admin => 'Admin',
            self::Moderator => 'Moderator',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    public function isModerator(): bool
    {
        return $this === self::Moderator || $this === self::Admin;
    }
}
