<?php

namespace App\Modules\Monitor\Services;

use App\Modules\Monitor\DTOs\MonitorStoreDTO;
use App\Modules\Monitor\DTOs\MonitorUpdateDTO;
use App\Modules\Monitor\Enums\MonitorStatus;
use App\Modules\Monitor\Enums\MonitorType;
use App\Modules\Monitor\Models\Monitor;
use App\Modules\Monitor\Repositories\MonitorRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Spatie\LaravelData\Optional;

class MonitorService
{
    public function __construct(
        private MonitorRepository $monitorRepository,
    ) {}

    /**
     * @return Monitor[]
     */
    public function list(?int $projectId = null): array
    {
        return $this->monitorRepository->findAllMonitors($projectId);
    }

    public function find(int $id): ?Monitor
    {
        return $this->monitorRepository->findMonitorById($id);
    }

    public function create(MonitorStoreDTO $dto): Monitor
    {
        $isHeartbeat = $dto->type === MonitorType::HEARTBEAT->value;

        $data = [
            'project_id' => $dto->project_id,
            'name' => $dto->name,
            'type' => $dto->type,
            'target' => $dto->target,
            'config' => $dto->config,
            'interval_seconds' => $dto->interval_seconds,
            'timeout_ms' => $dto->timeout_ms,
            'confirmation_threshold' => $dto->confirmation_threshold,
            'recovery_threshold' => $dto->recovery_threshold,
            'status' => MonitorStatus::PENDING->value,
            // Heartbeat monitors are push-based: no scheduled next check.
            'next_check_at' => $isHeartbeat ? null : now(),
            'heartbeat_token' => $isHeartbeat ? $this->generateHeartbeatToken() : null,
        ];

        $monitor = $this->monitorRepository->createMonitor($data);

        return $this->findOrFail($monitor->id);
    }

    public function update(int $id, MonitorUpdateDTO $dto): Monitor
    {
        $monitor = $this->findOrFail($id);
        $data = $this->provided($dto);

        if (array_key_exists('status', $data)) {
            $data = array_merge($data, $this->nextCheckForStatus($monitor, $data['status']));
        }

        if (! empty($data)) {
            $this->monitorRepository->updateMonitor($id, $data);
        }

        return $this->findOrFail($id);
    }

    public function delete(int $id): bool
    {
        return $this->monitorRepository->deleteMonitor($id);
    }

    /**
     * Collect only the fields the caller actually supplied (skipping Optional).
     *
     * @return array<string, mixed>
     */
    private function provided(MonitorUpdateDTO $dto): array
    {
        $fields = [
            'name' => $dto->name,
            'target' => $dto->target,
            'config' => $dto->config,
            'interval_seconds' => $dto->interval_seconds,
            'timeout_ms' => $dto->timeout_ms,
            'confirmation_threshold' => $dto->confirmation_threshold,
            'recovery_threshold' => $dto->recovery_threshold,
            'status' => $dto->status,
        ];

        return array_filter($fields, fn ($value) => ! ($value instanceof Optional));
    }

    /**
     * Pausing clears the schedule; resuming a non-heartbeat monitor makes it due now.
     *
     * @return array<string, mixed>
     */
    private function nextCheckForStatus(Monitor $monitor, string $status): array
    {
        if ($status === MonitorStatus::PAUSED->value) {
            return ['next_check_at' => null];
        }

        $isHeartbeat = $monitor->type === MonitorType::HEARTBEAT;
        $isResuming = $monitor->status === MonitorStatus::PAUSED;

        if (! $isHeartbeat && $isResuming) {
            return ['next_check_at' => now()];
        }

        return [];
    }

    private function generateHeartbeatToken(): string
    {
        do {
            $token = Str::random(40);
        } while ($this->monitorRepository->findMonitorByHeartbeatToken($token) !== null);

        return $token;
    }

    private function findOrFail(int $id): Monitor
    {
        $monitor = $this->monitorRepository->findMonitorById($id);

        if (! $monitor) {
            throw new ModelNotFoundException('Monitor not found');
        }

        return $monitor;
    }
}
