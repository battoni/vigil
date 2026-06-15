<?php

namespace App\Modules\Monitor\Enums;

enum MonitorType: string
{
    case HTTP = 'http';
    case TCP = 'tcp';
    case PING = 'ping';
    case SSL = 'ssl';
    case KEYWORD = 'keyword';
    case HEARTBEAT = 'heartbeat';
    case DNS = 'dns';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
