<?php

namespace App\Modules\Check\Events;

use App\Modules\Monitor\Models\Monitor;
use Illuminate\Foundation\Events\Dispatchable;

class MonitorRecovered
{
    use Dispatchable;

    public function __construct(
        public readonly Monitor $monitor,
    ) {}
}
