<?php

namespace App\Modules\Notification\Enums;

enum ChannelType: string
{
    case WHATSAPP = 'whatsapp';
    case SLACK = 'slack';
    case EMAIL = 'email';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
