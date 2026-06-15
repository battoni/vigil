<?php

namespace App\Modules\StatusPage\DTOs;

use Spatie\LaravelData\Data;

class StatusPageStoreDTO extends Data
{
    public function __construct(
        public string $name,
        public ?string $custom_domain = null,
        public ?array $branding = null,
        public bool $is_public = true,
    ) {}
}
