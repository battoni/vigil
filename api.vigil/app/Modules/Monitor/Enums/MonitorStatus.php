<?php

namespace App\Modules\Monitor\Enums;

enum MonitorStatus: string
{
    case UP = 'up';
    case DOWN = 'down';
    case PENDING = 'pending';
    case PAUSED = 'paused';
    case MAINTENANCE = 'maintenance';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
