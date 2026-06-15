<?php

namespace App\Modules\Notification\Repositories;

use App\Modules\Notification\Models\NotificationChannel;

class ChannelRepository
{
    public function __construct(
        private NotificationChannel $model
    ) {}

    /**
     * @return NotificationChannel[]
     */
    public function findAllChannels(): array
    {
        return $this->model->orderBy('name')->get()->all();
    }

    public function findChannelById(int $id): ?NotificationChannel
    {
        return $this->model->find($id);
    }

    public function createChannel(array $data): NotificationChannel
    {
        return $this->model->create($data);
    }

    public function updateChannel(int $id, array $data): bool
    {
        return $this->model->where('id', $id)->update($data);
    }

    public function deleteChannel(int $id): bool
    {
        return $this->model->where('id', $id)->delete();
    }

    /**
     * Active channels routed to a monitor, with pivot routing settings.
     *
     * @return NotificationChannel[]
     */
    public function findActiveForMonitor(int $monitorId): array
    {
        return $this->model
            ->where('is_active', true)
            ->whereHas('monitors', fn ($query) => $query->where('monitors.id', $monitorId))
            ->with(['monitors' => fn ($query) => $query->where('monitors.id', $monitorId)])
            ->get()
            ->all();
    }
}
