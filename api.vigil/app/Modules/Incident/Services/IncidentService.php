<?php

namespace App\Modules\Incident\Services;

use App\Modules\Incident\Models\Incident;
use App\Modules\Incident\Repositories\IncidentRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class IncidentService
{
    public function __construct(
        private IncidentRepository $incidentRepository,
    ) {}

    /**
     * Idempotent: a monitor has at most one open incident at a time, so a
     * repeated DOWN event returns the existing incident instead of duplicating.
     */
    public function open(int $monitorId, ?string $cause = null): Incident
    {
        $existing = $this->incidentRepository->findOpenIncidentForMonitor($monitorId);

        if ($existing !== null) {
            return $existing;
        }

        return $this->incidentRepository->openIncident($monitorId, $cause);
    }

    public function resolve(int $monitorId): ?Incident
    {
        $open = $this->incidentRepository->findOpenIncidentForMonitor($monitorId);

        if ($open === null) {
            return null;
        }

        return $this->incidentRepository->resolveIncident($open->id);
    }

    public function acknowledge(int $incidentId, int $userId): Incident
    {
        $incident = $this->incidentRepository->findIncidentById($incidentId);

        if ($incident === null) {
            throw new ModelNotFoundException('Incident not found');
        }

        $this->incidentRepository->acknowledgeIncident($incidentId, $userId);

        return $this->findOrFail($incidentId);
    }

    /**
     * @return Incident[]
     */
    public function list(?int $monitorId = null, ?bool $onlyOpen = null): array
    {
        return $this->incidentRepository->findAllIncidents($monitorId, $onlyOpen);
    }

    public function find(int $id): ?Incident
    {
        return $this->incidentRepository->findIncidentById($id);
    }

    public function latestForMonitor(int $monitorId): ?Incident
    {
        return $this->incidentRepository->findLatestForMonitor($monitorId);
    }

    private function findOrFail(int $id): Incident
    {
        $incident = $this->incidentRepository->findIncidentById($id);

        if ($incident === null) {
            throw new ModelNotFoundException('Incident not found');
        }

        return $incident;
    }
}
