<?php

namespace App\Modules\Notification\Listeners;

use App\Modules\Check\Events\MonitorWentDown;
use App\Modules\Incident\Enums\IncidentEvent;
use App\Modules\Incident\Services\IncidentService;
use App\Modules\Notification\Services\AlertDispatchService;

class SendAlertOnMonitorWentDown
{
    public function __construct(
        private AlertDispatchService $alertDispatchService,
        private IncidentService $incidentService,
    ) {}

    public function handle(MonitorWentDown $event): void
    {
        // The incident listener runs first, so an open incident already exists.
        $incident = $this->incidentService->latestForMonitor($event->monitor->id);

        $this->alertDispatchService->send($event->monitor, IncidentEvent::DOWN, $incident, $event->cause);
    }
}
