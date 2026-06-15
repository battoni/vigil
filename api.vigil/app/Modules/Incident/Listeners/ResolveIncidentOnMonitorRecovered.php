<?php

namespace App\Modules\Incident\Listeners;

use App\Modules\Check\Events\MonitorRecovered;
use App\Modules\Incident\Services\IncidentService;

class ResolveIncidentOnMonitorRecovered
{
    public function __construct(
        private IncidentService $incidentService,
    ) {}

    public function handle(MonitorRecovered $event): void
    {
        $this->incidentService->resolve($event->monitor->id);
    }
}
