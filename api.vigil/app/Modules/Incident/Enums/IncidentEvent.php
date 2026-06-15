<?php

namespace App\Modules\Incident\Enums;

enum IncidentEvent: string
{
    case DOWN = 'down';
    case STILL_DOWN = 'still_down';
    case RECOVERED = 'recovered';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
