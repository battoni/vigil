<?php

namespace App\Modules\Project\DTOs;

use Spatie\LaravelData\Data;

class ProjectStoreDTO extends Data
{
    public function __construct(
        public string $name,
    ) {}
}
