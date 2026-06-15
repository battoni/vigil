<?php

namespace App\Modules\Monitor\Enums;

enum CheckOutcome: string
{
    case UP = 'up';
    case DOWN = 'down';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
