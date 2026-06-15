<?php

namespace App\Modules\Incident\Listeners;

use App\Modules\Check\Events\MonitorWentDown;
use App\Modules\Incident\Services\IncidentService;

class OpenIncidentOnMonitorWentDown
{
    public function __construct(
        private IncidentService $incidentService,
    ) {}

    public function handle(MonitorWentDown $event): void
    {
        $this->incidentService->open($event->monitor->id, $event->cause);
    }
}
