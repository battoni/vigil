<?php

namespace App\Modules\Notification\DTOs;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class ChannelUpdateDTO extends Data
{
    public function __construct(
        public string|Optional $name,
        public array|null|Optional $config,
        public bool|Optional $is_active,
    ) {}
}
