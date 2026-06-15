<?php

namespace App\Modules\Notification\Repositories;

use App\Modules\Incident\Enums\IncidentEvent;
use App\Modules\Notification\Enums\NotificationStatus;
use App\Modules\Notification\Models\NotificationLog;

class NotificationLogRepository
{
    public function __construct(
        private NotificationLog $model
    ) {}

    public function recordLog(
        int $incidentId,
        ?int $channelId,
        IncidentEvent $event,
        NotificationStatus $status,
        ?string $error = null,
    ): NotificationLog {
        return $this->model->create([
            'incident_id' => $incidentId,
            'channel_id' => $channelId,
            'event' => $event->value,
            'status' => $status->value,
            'error' => $error,
            'sent_at' => $status === NotificationStatus::SENT ? now() : null,
        ]);
    }

    /**
     * Dedup guard: has this incident's event already been delivered?
     */
    public function hasDelivered(int $incidentId, IncidentEvent $event): bool
    {
        return $this->model
            ->where('incident_id', $incidentId)
            ->where('event', $event->value)
            ->where('status', NotificationStatus::SENT->value)
            ->exists();
    }

    /**
     * @return NotificationLog[]
     */
    public function findForIncident(int $incidentId): array
    {
        return $this->model
            ->where('incident_id', $incidentId)
            ->latest('id')
            ->get()
            ->all();
    }
}
