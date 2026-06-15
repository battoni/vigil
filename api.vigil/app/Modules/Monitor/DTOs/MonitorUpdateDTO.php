<?php

namespace App\Modules\Monitor\DTOs;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class MonitorUpdateDTO extends Data
{
    public function __construct(
        public string|Optional $name,
        public string|Optional $target,
        public array|null|Optional $config,
        public int|Optional $interval_seconds,
        public int|Optional $timeout_ms,
        public int|Optional $confirmation_threshold,
        public int|Optional $recovery_threshold,
        public string|Optional $status,
    ) {}
}
