<?php

namespace App\Modules\Monitor\DTOs;

use Spatie\LaravelData\Data;

class MonitorStoreDTO extends Data
{
    public function __construct(
        public int $project_id,
        public string $name,
        public string $type,
        public string $target,
        public ?array $config = null,
        public int $interval_seconds = 60,
        public int $timeout_ms = 10000,
        public int $confirmation_threshold = 2,
        public int $recovery_threshold = 1,
    ) {}
}
