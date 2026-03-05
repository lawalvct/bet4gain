<?php

namespace App\Enums;

enum AdPlacement: string
{
    case Sidebar = 'sidebar';
    case Banner = 'banner';
    case Popup = 'popup';
    case BetweenRounds = 'between_rounds';

    public function label(): string
    {
        return match ($this) {
            self::Sidebar => 'Sidebar',
            self::Banner => 'Banner',
            self::Popup => 'Popup',
            self::BetweenRounds => 'Between Rounds',
        };
    }
}
