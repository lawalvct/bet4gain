<?php

namespace App\Enums;

enum ChatMessageType: string
{
    case Text = 'text';
    case System = 'system';
    case Gif = 'gif';
    case Emoji = 'emoji';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Text',
            self::System => 'System',
            self::Gif => 'GIF',
            self::Emoji => 'Emoji',
        };
    }
}
