<?php

namespace App\Modules\Project\DTOs;

use Spatie\LaravelData\Data;

class ProjectUpdateDTO extends Data
{
    public function __construct(
        public ?string $name = null,
    ) {}
}
