<?php

namespace App\Modules\StatusPage\DTOs;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class StatusPageUpdateDTO extends Data
{
    public function __construct(
        public string|Optional $name,
        public string|null|Optional $custom_domain,
        public array|null|Optional $branding,
        public bool|Optional $is_public,
    ) {}
}
