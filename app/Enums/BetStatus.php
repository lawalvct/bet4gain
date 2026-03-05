<?php

namespace App\Enums;

enum BetStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Won = 'won';
    case Lost = 'lost';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Active => 'Active',
            self::Won => 'Won',
            self::Lost => 'Lost',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Active => 'info',
            self::Won => 'success',
            self::Lost => 'danger',
            self::Cancelled => 'gray',
        };
    }
}
