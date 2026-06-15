<?php

namespace App\Modules\Notification\DTOs;

use Spatie\LaravelData\Data;

class ChannelStoreDTO extends Data
{
    public function __construct(
        public string $name,
        public string $type,
        public ?array $config = null,
        public bool $is_active = true,
    ) {}
}
