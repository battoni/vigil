<?php

namespace App\Modules\Notification\Enums;

enum NotificationStatus: string
{
    case SENT = 'sent';
    case FAILED = 'failed';
    case FELL_BACK = 'fell_back';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
