<?php

namespace App\Modules\Incident\Repositories;

use App\Modules\Incident\Models\Incident;

class IncidentRepository
{
    public function __construct(
        private Incident $model
    ) {}

    public function openIncident(int $monitorId, ?string $cause): Incident
    {
        return $this->model->create([
            'monitor_id' => $monitorId,
            'started_at' => now(),
            'cause' => $cause,
        ]);
    }

    public function resolveIncident(int $incidentId): ?Incident
    {
        $incident = $this->model->find($incidentId);

        if ($incident === null) {
            return null;
        }

        $resolvedAt = now();
        $incident->resolved_at = $resolvedAt;
        $incident->duration_seconds = (int) $incident->started_at->diffInSeconds($resolvedAt, absolute: true);
        $incident->save();

        return $incident;
    }

    public function findOpenIncidentForMonitor(int $monitorId): ?Incident
    {
        return $this->model
            ->where('monitor_id', $monitorId)
            ->whereNull('resolved_at')
            ->latest('started_at')
            ->first();
    }

    public function findIncidentById(int $id): ?Incident
    {
        return $this->model->with('monitor')->find($id);
    }

    public function findLatestForMonitor(int $monitorId): ?Incident
    {
        return $this->model
            ->where('monitor_id', $monitorId)
            ->latest('started_at')
            ->first();
    }

    /**
     * @return Incident[]
     */
    public function findAllIncidents(?int $monitorId = null, ?bool $onlyOpen = null): array
    {
        return $this->model
            ->with('monitor')
            ->when($monitorId, fn ($query) => $query->where('monitor_id', $monitorId))
            ->when($onlyOpen === true, fn ($query) => $query->whereNull('resolved_at'))
            ->when($onlyOpen === false, fn ($query) => $query->whereNotNull('resolved_at'))
            ->latest('started_at')
            ->get()
            ->all();
    }

    public function acknowledgeIncident(int $incidentId, int $userId): bool
    {
        return $this->model->where('id', $incidentId)->update([
            'acknowledged_by' => $userId,
            'acknowledged_at' => now(),
        ]);
    }
}
