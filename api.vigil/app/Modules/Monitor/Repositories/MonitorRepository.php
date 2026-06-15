<?php

namespace App\Modules\Monitor\Repositories;

use App\Modules\Monitor\Enums\MonitorStatus;
use App\Modules\Monitor\Enums\MonitorType;
use App\Modules\Monitor\Models\Monitor;

class MonitorRepository
{
    public function __construct(
        private Monitor $model
    ) {}

    /**
     * Schedulable monitors that are due: not paused, not push-based heartbeats.
     *
     * @return Monitor[]
     */
    public function findDueMonitors(): array
    {
        return $this->model
            ->whereNotNull('next_check_at')
            ->where('next_check_at', '<=', now())
            ->where('status', '!=', MonitorStatus::PAUSED->value)
            ->where('type', '!=', MonitorType::HEARTBEAT->value)
            ->orderBy('next_check_at')
            ->get()
            ->all();
    }

    /**
     * @return Monitor[]
     */
    public function findDueHeartbeats(): array
    {
        return $this->model
            ->where('type', MonitorType::HEARTBEAT->value)
            ->where('status', '!=', MonitorStatus::PAUSED->value)
            ->get()
            ->all();
    }

    /**
     * Push next_check_at out by a short lease so the dispatcher does not
     * re-enqueue a monitor while its RunCheckJob is in flight. The job commits
     * the real next_check_at on completion. See PLAN.md §5.
     */
    public function leaseForCheck(int $id, int $leaseSeconds): void
    {
        $leaseUntil = now()->addSeconds($leaseSeconds);

        $this->model->where('id', $id)->update([
            'next_check_at' => $leaseUntil,
            'leased_until' => $leaseUntil,
        ]);
    }

    public function updateMonitorState(int $id, array $state): bool
    {
        return $this->model->where('id', $id)->update($state);
    }

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
