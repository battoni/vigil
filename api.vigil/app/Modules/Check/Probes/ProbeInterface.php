<?php

namespace App\Modules\Check\Probes;

use App\Modules\Check\Support\ProbeResult;
use App\Modules\Monitor\Models\Monitor;

interface ProbeInterface
{
    /**
     * Run a single check against the monitor's target within the given timeout.
     * Implementations must never throw for an unhealthy target — they return a
     * down ProbeResult with a cause. They self-enforce SSRF via SsrfGuard.
     */
    public function run(Monitor $monitor, int $timeoutMs): ProbeResult;
}
