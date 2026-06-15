<?php

namespace App\Modules\Monitor\Repositories;

use App\Modules\Monitor\Models\Monitor;

class MonitorRepository
{
    public function __construct(
        private Monitor $model
    ) {}

    /**
     * @return Monitor[]
     */
    public function findAllMonitors(?int $projectId = null): array
    {
        return $this->model
            ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
            ->orderBy('name')
            ->get()
            ->all();
    }

    public function findMonitorById(int $id): ?Monitor
    {
        return $this->model->find($id);
    }

    public function findMonitorByHeartbeatToken(string $token): ?Monitor
    {
        return $this->model->where('heartbeat_token', $token)->first();
    }

    public function createMonitor(array $data): Monitor
    {
        return $this->model->create($data);
    }

    public function updateMonitor(int $id, array $data): bool
    {
        return $this->model->where('id', $id)->update($data);
    }

    public function deleteMonitor(int $id): bool
    {
        return $this->model->where('id', $id)->delete();
    }
}
