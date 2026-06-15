<?php

namespace App\Modules\Check\Probes;

use App\Modules\Monitor\Enums\MonitorType;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class ProbeFactory
{
    public function __construct(
        private Container $container,
    ) {}

    public function for(MonitorType $type): ProbeInterface
    {
        return match ($type) {
            MonitorType::HTTP, MonitorType::KEYWORD => $this->container->make(HttpProbe::class),
            MonitorType::TCP => $this->container->make(TcpProbe::class),
            MonitorType::SSL => $this->container->make(SslProbe::class),
            default => throw new InvalidArgumentException("No probe registered for monitor type '{$type->value}'."),
        };
    }

    public function supports(MonitorType $type): bool
    {
        return in_array($type, [MonitorType::HTTP, MonitorType::KEYWORD, MonitorType::TCP, MonitorType::SSL], true);
    }
}
