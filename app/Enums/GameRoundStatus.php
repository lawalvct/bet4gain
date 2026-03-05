<?php

namespace App\Enums;

enum GameRoundStatus: string
{
    case Waiting = 'waiting';
    case Betting = 'betting';
    case Running = 'running';
    case Crashed = 'crashed';

    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'Waiting',
            self::Betting => 'Betting',
            self::Running => 'Running',
            self::Crashed => 'Crashed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Waiting => 'gray',
            self::Betting => 'warning',
            self::Running => 'success',
            self::Crashed => 'danger',
        };
    }
}
