<?php

namespace App\Modules\Notification\Listeners;

use App\Modules\Check\Events\MonitorRecovered;
use App\Modules\Incident\Enums\IncidentEvent;
use App\Modules\Incident\Services\IncidentService;
use App\Modules\Notification\Services\AlertDispatchService;

class SendAlertOnMonitorRecovered
{
    public function __construct(
        private AlertDispatchService $alertDispatchService,
        private IncidentService $incidentService,
    ) {}

    public function handle(MonitorRecovered $event): void
    {
        // The incident listener runs first and has resolved the incident.
        $incident = $this->incidentService->latestForMonitor($event->monitor->id);

        $this->alertDispatchService->send($event->monitor, IncidentEvent::RECOVERED, $incident);
    }
}
